/**
 * Profile photo preview (FR-4).
 *
 * Shows the picked image immediately, and lets the user remove the current
 * photo without opening a file picker. The server still validates the real
 * MIME type, extension and size; this is only a friendly preview.
 */

function bootPhotoPreview() {
    document.querySelectorAll('[data-photo-form]').forEach((form) => {
        const input = form.querySelector('[data-photo-input]');
        const preview = form.querySelector('[data-photo-preview]');
        const remove = form.querySelector('[data-photo-remove]');

        if (! input || ! preview) {
            return;
        }

        const defaultSrc = preview.dataset.defaultSrc || preview.src;
        let objectUrl = null;

        const setPreview = (src) => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            preview.src = src;
        };

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file) {
                setPreview(remove?.checked ? defaultSrc : preview.dataset.currentSrc || defaultSrc);
                return;
            }

            // A new file wins over a pending "remove" request.
            if (remove) {
                remove.checked = false;
            }

            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
        });

        remove?.addEventListener('change', () => {
            if (! remove.checked) {
                return;
            }

            // Clear the file picker and go back to the default avatar.
            input.value = '';
            setPreview(defaultSrc);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPhotoPreview);
} else {
    bootPhotoPreview();
}
