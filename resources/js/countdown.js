/**
 * Live countdown timers (Phase 3B).
 *
 * Markup is produced by <x-countdown target="ISO-8601" />:
 *   <div data-countdown="2026-11-02T18:00:00+00:00">
 *       <span data-countdown-days>00</span> ...
 *   </div>
 *
 * The server renders a static value, so the timer is useful even before this
 * script runs. Once it runs, the numbers update every second.
 */

const pad = (value) => String(Math.max(0, value)).padStart(2, '0');

function updateCountdown(element) {
    const target = new Date(element.dataset.countdown);
    if (Number.isNaN(target.getTime())) {
        return;
    }

    const difference = target.getTime() - Date.now();

    if (difference <= 0) {
        element.querySelectorAll('[data-countdown-days], [data-countdown-hours], [data-countdown-minutes], [data-countdown-seconds]')
            .forEach((node) => {
                node.textContent = '00';
            });
        element.classList.add('is-finished');
        element.dataset.countdownFinished = '1';

        return;
    }

    const seconds = Math.floor(difference / 1000);
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remaining = seconds % 60;

    const values = {
        days,
        hours,
        minutes,
        seconds: remaining,
    };

    Object.entries(values).forEach(([key, value]) => {
        const node = element.querySelector(`[data-countdown-${key}]`);
        if (node) {
            node.textContent = pad(value);
        }
    });

    // Screen readers get a single readable sentence, not four changing boxes.
    const readable = element.querySelector('[data-countdown-readable]');
    if (readable) {
        const parts = [];
        if (days > 0) parts.push(`${days} day${days === 1 ? '' : 's'}`);
        if (hours > 0) parts.push(`${hours} hour${hours === 1 ? '' : 's'}`);
        if (minutes > 0) parts.push(`${minutes} minute${minutes === 1 ? '' : 's'}`);
        parts.push(`${remaining} second${remaining === 1 ? '' : 's'}`);
        readable.textContent = parts.join(', ');
    }
}

function bootCountdowns() {
    const timers = Array.from(document.querySelectorAll('[data-countdown]'));

    if (timers.length === 0) {
        return;
    }

    timers.forEach(updateCountdown);
    window.setInterval(() => timers.forEach(updateCountdown), 1000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCountdowns);
} else {
    bootCountdowns();
}
