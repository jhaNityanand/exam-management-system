/**
 * Shared Flatpickr-based date / time / datetime picker.
 * Marks: [data-ems-datetime] wrappers with [data-ems-datetime-input].
 */
(function (global) {
    'use strict';

    const loaded = { css: false, js: null };

    function ensureAssets() {
        if (!loaded.css) {
            const href = 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css';
            if (![...document.styleSheets].some((s) => String(s.href || '').includes('flatpickr'))) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                document.head.appendChild(link);
            }
            loaded.css = true;
        }

        if (global.flatpickr) return Promise.resolve(global.flatpickr);

        if (!loaded.js) {
            loaded.js = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js';
                script.async = true;
                script.onload = () => resolve(global.flatpickr);
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        return loaded.js;
    }

    function isDark() {
        return document.documentElement.classList.contains('dark')
            || document.body.classList.contains('dark');
    }

    function mountInput(input) {
        if (!input || input._flatpickr) return input._flatpickr || null;

        const enableTime = input.dataset.enableTime === '1';
        const noCalendar = input.dataset.noCalendar === '1';
        const dateFormat = input.dataset.dateFormat || (enableTime ? 'Y-m-d H:i' : 'Y-m-d');
        const altFormat = input.dataset.altFormat || (enableTime ? 'M j, Y h:i K' : 'M j, Y');

        const fp = global.flatpickr(input, {
            enableTime,
            noCalendar,
            dateFormat,
            altInput: true,
            altFormat,
            time_24hr: false,
            allowInput: true,
            clickOpens: true,
            disableMobile: false,
            animate: true,
            minDate: (() => {
                if (input.dataset.minDate !== 'future' && input.dataset.minDate !== 'now') {
                    return undefined;
                }
                const initial = input.dataset.initialValue || input.value || '';
                const initialDate = initial ? new Date(String(initial).replace(' ', 'T')) : null;
                const now = new Date();
                if (initialDate && !Number.isNaN(initialDate.getTime()) && initialDate < now) {
                    return initialDate;
                }
                return now;
            })(),
            onReady(_, __, instance) {
                instance.calendarContainer?.classList.toggle('ems-dtp-calendar--dark', isDark());
            },
            onOpen(_, __, instance) {
                instance.calendarContainer?.classList.toggle('ems-dtp-calendar--dark', isDark());
            },
        });

        const wrap = input.closest('[data-ems-datetime]');
        wrap?.querySelector('[data-ems-datetime-toggle]')?.addEventListener('click', () => fp.open());

        return fp;
    }

    async function initAll(root = document) {
        const inputs = [...root.querySelectorAll('[data-ems-datetime-input]')];
        if (!inputs.length) return [];

        await ensureAssets();
        return inputs.map((input) => mountInput(input)).filter(Boolean);
    }

    const themeObserver = new MutationObserver(() => {
        document.querySelectorAll('.flatpickr-calendar').forEach((cal) => {
            cal.classList.toggle('ems-dtp-calendar--dark', isDark());
        });
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    global.EmsDateTimePicker = { initAll, mountInput, ensureAssets };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initAll());
    } else {
        initAll();
    }
})(window);
