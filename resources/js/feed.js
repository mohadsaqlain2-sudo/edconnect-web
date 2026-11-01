/**
 * EduConnect feed interactions (FR-11, FR-13, FR-17).
 *
 * Everything here is progressive enhancement: the pages already contain real
 * <form> elements, so with JavaScript switched off the browser simply posts
 * them and the controllers redirect back. With JavaScript we intercept the
 * submit, call fetch() and update the page in place.
 */

/** The CSRF token from the <meta name="csrf-token"> tag in the layout. */
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/**
 * Post a form with fetch() and return the JSON answer.
 * Always sends POST + the form data, so Laravel's @method('DELETE') spoofing
 * keeps working exactly like it does for a normal browser submit.
 */
async function postForm(form) {
    const response = await fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        body: new FormData(form),
    });

    if (!response.ok) {
        if (response.status === 429) {
            throw new Error('Too many requests. Please wait a moment and try again.');
        }

        if (response.status === 403) {
            throw new Error('You are not allowed to do that.');
        }

        throw new Error('Something went wrong. Please try again.');
    }

    return response.json();
}

/* -------------------------------------------------------------------------
 | Likes
 * ---------------------------------------------------------------------- */

/** Keep the "flip to the other action" bookkeeping inside the form. */
function setLikeState(form, button, counter, liked, count) {
    form.dataset.liked = liked ? '1' : '0';
    form.action = liked ? form.dataset.unlikeUrl : form.dataset.likeUrl;

    let spoof = form.querySelector('input[name="_method"]');
    if (liked) {
        if (!spoof) {
            spoof = document.createElement('input');
            spoof.type = 'hidden';
            spoof.name = '_method';
            form.append(spoof);
        }
        spoof.value = 'DELETE';
    } else if (spoof) {
        spoof.remove();
    }

    if (counter) {
        counter.textContent = count;
    }

    if (button) {
        button.classList.toggle('btn-danger', liked);
        button.classList.toggle('btn-outline-secondary', !liked);
        button.setAttribute('aria-pressed', liked ? 'true' : 'false');

        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-heart-fill', liked);
            icon.classList.toggle('bi-heart', !liked);
            // A short pop, disabled automatically by prefers-reduced-motion.
            icon.classList.remove('edu-like-pop');
            void icon.offsetWidth;
            icon.classList.add('edu-like-pop');
        }
    }
}

async function handleLike(form) {
    const card = form.closest('[data-post-card]');
    const button = form.querySelector('[data-like-button]');
    const counter = card?.querySelector('[data-like-count]');

    const wasLiked = form.dataset.liked === '1';
    const previousCount = Number(counter?.textContent ?? 0);

    // Optimistic UI: show the change before the server has answered.
    setLikeState(form, button, counter, !wasLiked, previousCount + (wasLiked ? -1 : 1));

    // A second click must not race the first request.
    if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    }

    try {
        const data = await postForm(form);
        // Trust the server numbers, they are the truth.
        setLikeState(form, button, counter, data.liked, data.count);
    } catch (error) {
        console.error('[EduConnect] liking the post failed', error);
        setLikeState(form, button, counter, wasLiked, previousCount);
        window.eduToast?.(error.message, 'error');
    } finally {
        if (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }
}

/* -------------------------------------------------------------------------
 | Follow / unfollow
 * ---------------------------------------------------------------------- */

function setFollowState(form, button, following) {
    form.dataset.following = following ? '1' : '0';
    form.action = following ? form.dataset.unfollowUrl : form.dataset.followUrl;

    let spoof = form.querySelector('input[name="_method"]');
    if (following) {
        if (!spoof) {
            spoof = document.createElement('input');
            spoof.type = 'hidden';
            spoof.name = '_method';
            form.append(spoof);
        }
        spoof.value = 'DELETE';
    } else if (spoof) {
        spoof.remove();
    }

    if (!button) {
        return;
    }

    button.classList.toggle('btn-primary', !following);
    button.classList.toggle('btn-outline-secondary', following);
    button.setAttribute('aria-pressed', following ? 'true' : 'false');

    const icon = button.querySelector('i');
    if (icon) {
        icon.classList.toggle('bi-person-check', following);
        icon.classList.toggle('bi-person-plus', !following);
    }

    // Both labels arrive from Blade, so they stay translated.
    const label = button.querySelector('[data-follow-label]');
    if (label) {
        label.textContent = following ? form.dataset.labelFollowing : form.dataset.labelFollow;
    }
}

/**
 * Refresh the follower counter of the profile that was followed and the
 * viewer's own "following" total (used by the feed sidebar).
 */
function updateFollowCounts(data) {
    if (data.user_id !== undefined) {
        document.querySelectorAll(`[data-followers-count][data-user-id="${data.user_id}"]`).forEach((element) => {
            const count = Number(data.followers);

            element.textContent = count;

            // The word after the number has to follow the plural rules too
            // (0 followers, 1 follower, 2 followers).
            const label = element.parentElement?.querySelector('[data-follower-label]');

            if (label) {
                label.textContent = count === 1
                    ? element.dataset.labelSingular
                    : element.dataset.labelPlural;
            }
        });
    }

    if (data.following_count !== undefined) {
        document.querySelectorAll('[data-viewer-following-count]').forEach((element) => {
            element.textContent = data.following_count;
        });
    }
}

async function handleFollow(form) {
    const button = form.querySelector('[data-follow-button]');
    const wasFollowing = form.dataset.following === '1';

    setFollowState(form, button, !wasFollowing);

    if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    }

    try {
        const data = await postForm(form);
        setFollowState(form, button, data.following);
        updateFollowCounts(data);
    } catch (error) {
        console.error('[EduConnect] follow failed', error);
        setFollowState(form, button, wasFollowing);
        window.eduToast?.(error.message, 'error');
    } finally {
        if (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }
}

/* -------------------------------------------------------------------------
 | Comments
 * ---------------------------------------------------------------------- */

/**
 * The detail page wraps the post and its comments in [data-comment-scope];
 * a feed card is its own scope. Counters outside that scope never change.
 */
function commentScope(form) {
    return form.closest('[data-comment-scope]')
        || form.closest('[data-post-card]')
        || document;
}

function updateCommentCounts(scope, count) {
    scope.querySelectorAll('[data-comment-count]').forEach((element) => {
        element.textContent = count;
    });
}

async function handleComment(form) {
    const scope = commentScope(form);
    const region = form.closest('[data-comment-region]');
    const list = region?.querySelector('[data-comment-list]');

    try {
        const data = await postForm(form);

        if (list && data.html) {
            // The "no comments yet" hint disappears with the first comment.
            list.querySelector('[data-comment-empty]')?.remove();
            list.insertAdjacentHTML('beforeend', data.html);
        }

        form.reset();
        updateCommentCounts(scope, data.count);
    } catch (error) {
        console.error('[EduConnect] posting the comment failed', error);
        window.eduToast?.(error.message, 'error');
    }
}

async function handleDeleteComment(form) {
    const scope = commentScope(form);

    try {
        const data = await postForm(form);

        if (data.deleted) {
            form.closest('[data-comment-id]')?.remove();
            updateCommentCounts(scope, data.count);
        }
    } catch (error) {
        console.error('[EduConnect] deleting the comment failed', error);
        window.eduToast?.(error.message, 'error');
    }
}

/* -------------------------------------------------------------------------
 | "Load more" in the feed
 * ---------------------------------------------------------------------- */

async function handleLoadMore(button) {
    const list = document.querySelector('[data-feed-list]');
    const url = button.dataset.url;

    if (!list || !url) {
        return;
    }

    button.disabled = true;

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const data = await response.json();
        list.insertAdjacentHTML('beforeend', data.html);

        if (data.has_more && data.next_page) {
            button.dataset.url = data.next_page;
        } else {
            // Last page reached: the button has done its job.
            button.hidden = true;
        }
    } catch (error) {
        console.error('[EduConnect] loading more posts failed', error);
        window.eduToast?.(error.message || 'Could not load more posts.', 'error');
    } finally {
        button.disabled = false;
    }
}

/* -------------------------------------------------------------------------
 | Live character counter for the 280 character limit (FR-9)
 * ---------------------------------------------------------------------- */

function initCharCounters() {
    document.querySelectorAll('[data-post-body]').forEach((field) => {
        const form = field.closest('form');
        const counter = form?.querySelector('[data-char-counter]');
        const ring = form?.querySelector('[data-char-ring]');
        const limit = Number(field.getAttribute('maxlength')) || 280;

        if (!counter && !ring) {
            return;
        }

        const update = () => {
            const length = field.value.length;

            if (counter) {
                counter.textContent = length;
                counter.classList.toggle('text-danger', length >= limit);
            }

            if (ring) {
                const ratio = Math.min(length / limit, 1);
                ring.style.setProperty('--edu-ring-progress', `${Math.round(ratio * 360)}deg`);
                ring.classList.toggle('is-warning', ratio >= 0.8 && ratio < 1);
                ring.classList.toggle('is-full', ratio >= 1);
                ring.setAttribute('aria-valuenow', String(length));
                ring.setAttribute('aria-valuetext', `${length}/${limit}`);
            }
        };

        field.addEventListener('input', update);
        update();
    });
}

/**
 * Image preview in the composer: a local object URL, revoked as soon as the
 * selection changes, so nothing leaks and no upload happens before submit.
 */
function initImagePreview() {
    document.querySelectorAll('[data-image-input]').forEach((input) => {
        const preview = input.closest('form')?.querySelector('[data-image-preview]');
        const image = preview?.querySelector('img');

        if (!preview || !image) {
            return;
        }

        let objectUrl = null;

        input.addEventListener('change', () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            const file = input.files?.[0];

            if (!file || !file.type.startsWith('image/')) {
                preview.hidden = true;
                image.removeAttribute('src');
                return;
            }

            objectUrl = URL.createObjectURL(file);
            image.src = objectUrl;
            preview.hidden = false;
        });
    });
}

/** The small "add hashtag" toolbar button keeps the keyboard flow simple. */
function initComposerToolbar() {
    document.querySelectorAll('[data-insert-hashtag]').forEach((button) => {
        button.addEventListener('click', () => {
            const field = button.closest('form')?.querySelector('[data-post-body]');

            if (!field) {
                return;
            }

            const start = field.selectionStart ?? field.value.length;
            const end = field.selectionEnd ?? field.value.length;
            const before = field.value.slice(0, start);
            const after = field.value.slice(end);
            const spacer = before.length > 0 && !/\s$/.test(before) ? ' ' : '';

            field.value = `${before}${spacer}#${after}`;
            field.focus();
            field.selectionStart = field.selectionEnd = start + spacer.length + 1;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
}

/* -------------------------------------------------------------------------
 | Wiring
 * ---------------------------------------------------------------------- */

document.addEventListener('submit', (event) => {
    const form = event.target;

    // data-confirm works for cards that arrived later through "Load more" too.
    if (form.dataset.confirm && ! window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        return;
    }

    if (form.matches('[data-like-form]')) {
        event.preventDefault();
        handleLike(form);
    } else if (form.matches('[data-follow-form]')) {
        event.preventDefault();
        handleFollow(form);
    } else if (form.matches('[data-comment-form]')) {
        event.preventDefault();
        handleComment(form);
    } else if (form.matches('[data-delete-comment-form]')) {
        event.preventDefault();
        handleDeleteComment(form);
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-load-more]');
    if (button) {
        event.preventDefault();
        handleLoadMore(button);
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initCharCounters();
        initImagePreview();
        initComposerToolbar();
    });
} else {
    initCharCounters();
    initImagePreview();
    initComposerToolbar();
}
