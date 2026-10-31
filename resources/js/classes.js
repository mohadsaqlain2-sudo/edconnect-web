/**
 * Live preview on the "create / edit a class" form (Phase 3C).
 *
 * While the teacher types, the card on the right mirrors the title, subject,
 * date, time and duration. Only text is updated (textContent), so nothing the
 * teacher writes can become markup. Without JavaScript the preview simply
 * shows the server-rendered starting values.
 */

function formatDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function bootClassPreview() {
    const form = document.querySelector('[data-class-form]');
    const preview = document.querySelector('[data-class-preview]');

    if (!form || !preview) {
        return;
    }

    const title = form.querySelector('#title');
    const subject = form.querySelector('#subject');
    const date = form.querySelector('#date');
    const time = form.querySelector('#time');
    const duration = form.querySelector('#duration');

    const set = (selector, value) => {
        const target = preview.querySelector(selector);

        if (target) {
            target.textContent = value;
        }
    };

    const update = () => {
        set('[data-preview-title]', title?.value.trim() || preview.dataset.emptyTitle || '');
        set('[data-preview-subject]', subject?.value.trim() || preview.dataset.emptySubject || '');
        set('[data-preview-date]', formatDate(date?.value) || preview.dataset.emptyDate || '');
        set('[data-preview-time]', time?.value || preview.dataset.emptyTime || '');
        set(
            '[data-preview-duration]',
            duration?.value ? `${duration.value} ${preview.dataset.durationLabel || 'min'}` : '',
        );

        const subjectChip = preview.querySelector('[data-preview-subject-chip]');
        if (subjectChip) {
            subjectChip.hidden = !subject?.value.trim();
        }
    };

    [title, subject, date, time, duration].forEach((field) => {
        field?.addEventListener('input', update);
        field?.addEventListener('change', update);
    });

    update();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootClassPreview);
} else {
    bootClassPreview();
}
