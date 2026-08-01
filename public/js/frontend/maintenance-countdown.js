/**
 * Live countdown for the public maintenance page.
 * Updates days / hours / minutes / seconds until data-restore-at.
 */
(function () {
    'use strict';

    const root = document.querySelector('[data-maintenance-countdown]');
    if (!root) return;

    const restoreAt = Date.parse(root.getAttribute('data-restore-at') || '');
    if (!Number.isFinite(restoreAt)) return;

    const units = {
        days: root.querySelector('[data-unit="days"]'),
        hours: root.querySelector('[data-unit="hours"]'),
        minutes: root.querySelector('[data-unit="minutes"]'),
        seconds: root.querySelector('[data-unit="seconds"]'),
    };
    const doneEl = root.querySelector('[data-countdown-done]');
    const gridEl = root.querySelector('.et-maintenance__countdown-grid');
    const labelEl = root.querySelector('.et-maintenance__countdown-label');

    const pad = (n) => String(Math.max(0, n)).padStart(2, '0');

    const render = () => {
        const remainingMs = restoreAt - Date.now();

        if (remainingMs <= 0) {
            Object.values(units).forEach((el) => {
                if (el) el.textContent = '00';
            });
            if (gridEl) gridEl.hidden = true;
            if (labelEl) labelEl.hidden = true;
            if (doneEl) doneEl.hidden = false;
            root.classList.add('is-complete');
            return false;
        }

        const totalSeconds = Math.floor(remainingMs / 1000);
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        if (units.days) units.days.textContent = pad(days);
        if (units.hours) units.hours.textContent = pad(hours);
        if (units.minutes) units.minutes.textContent = pad(minutes);
        if (units.seconds) units.seconds.textContent = pad(seconds);

        return true;
    };

    if (!render()) return;

    const timer = window.setInterval(() => {
        if (!render()) {
            window.clearInterval(timer);
        }
    }, 1000);
}());
