/**
 * Lightweight toasts for AJAX errors and client-side notices.
 * Server-rendered flash banners in <x-flash /> stay as they are.
 */

function stack() {
    let element = document.querySelector('[data-edu-toast-stack]');

    if (!element) {
        element = document.createElement('div');
        element.className = 'edu-toast-stack';
        element.dataset.eduToastStack = '1';
        element.setAttribute('role', 'status');
        element.setAttribute('aria-live', 'polite');
        document.body.append(element);
    }

    return element;
}

export function showToast(message, type = 'info') {
    if (!message) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = `edu-toast is-${type}`;
    toast.innerHTML = `
        <span>${escapeHtml(String(message))}</span>
        <button type="button" class="btn-close" aria-label="Close"></button>
    `;

    toast.querySelector('.btn-close')?.addEventListener('click', () => toast.remove());
    stack().append(toast);

    window.setTimeout(() => toast.remove(), 5000);
}

function escapeHtml(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

window.eduToast = showToast;
