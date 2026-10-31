/**
 * The navbar notification bell + the "mark as read" buttons (FR-24, FR-25).
 *
 * Every 30 seconds this asks /notifications/poll for the fresh unread counter
 * and the newest eight entries as HTML, then updates the badge and the dropdown
 * without a page reload. Polling pauses while the tab is in the background, so
 * it costs almost nothing.
 *
 * On the notifications page the single "mark as read" forms are upgraded to
 * fetch() calls: the row loses its unread styling and the bell counter drops,
 * with no reload. Without JavaScript the same forms still work.
 */
const POLL_INTERVAL_MS = 30000;

/** Put the server answer into the bell (badge + dropdown list). */
function updateBell(data) {
    const badge = document.querySelector('[data-notification-badge]');

    if (badge) {
        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
        // Hide the badge completely when there is nothing unread.
        badge.classList.toggle('d-none', data.unread_count === 0);
    }

    const list = document.querySelector('[data-notification-list]');
    if (list && typeof data.html === 'string') {
        list.innerHTML = data.html;
    }
}

/** One polling round. Fails silently: the bell keeps its last known state. */
async function refreshNotifications() {
    const url = document.body.dataset.notificationUrl;

    if (!url) {
        return;
    }

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

        updateBell(await response.json());
    } catch (error) {
        console.error('[EduConnect] refreshing the notifications failed', error);
    }
}

function startNotificationPolling() {
    // Nothing to do for guests or on pages without the bell.
    if (!document.querySelector('[data-notification-list]')) {
        return;
    }

    setInterval(() => {
        // No point in polling a tab nobody is looking at.
        if (!document.hidden) {
            refreshNotifications();
        }
    }, POLL_INTERVAL_MS);

    // Coming back to the tab: refresh straight away.
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshNotifications();
        }
    });

    // Opening the bell also refreshes it.
    document.querySelectorAll('[data-notification-toggle]').forEach((button) => {
        button.addEventListener('click', refreshNotifications);
    });
}

/** Mark one notification as read over fetch() instead of a full page load. */
function upgradeReadButtons() {
    document.querySelectorAll('form[data-notification-read]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = form.querySelector('button');
            if (button) {
                button.disabled = true;
            }

            try {
                // FormData carries the CSRF token hidden field with it.
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                        'X-HTTP-Method-Override': 'PATCH',
                    },
                    body: new FormData(form),
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                const data = await response.json();

                // Drop the unread look and the button itself.
                const row = form.closest('.list-group-item');
                if (row) {
                    row.classList.remove('notification-unread');
                    row.querySelectorAll('[data-notification-unread-label]')
                        .forEach((node) => node.remove());
                }

                form.remove();

                if (typeof data.unread_count === 'number') {
                    updateBell({ unread_count: data.unread_count });
                }
            } catch (error) {
                // Something went wrong: let the plain form submit instead.
                console.error('[EduConnect] marking the notification as read failed', error);
                form.submit();
            }
        });
    });
}

function bootNotifications() {
    startNotificationPolling();
    upgradeReadButtons();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootNotifications);
} else {
    bootNotifications();
}
