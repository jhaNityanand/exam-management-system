/**
 * Shared Flatpickr-based date / time / datetime picker.
 * Marks: [data-ems-datetime] wrappers with [data-ems-datetime-input].
 * Supports light/dark themes and a custom month menu (no native <select>).
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
        const html = document.documentElement;
        const body = document.body;
        if (html.classList.contains('dark') || (body && body.classList.contains('dark'))) return true;
        if ((html.dataset.theme || (body && body.dataset.theme)) === 'dark') return true;
        return false;
    }

    function applyTheme(cal) {
        if (!cal) return;
        const dark = isDark();
        cal.classList.toggle('ems-dtp-calendar--dark', dark);
        cal.style.colorScheme = dark ? 'dark' : 'light';
    }

    function scrubNativeMonthSelect(cal) {
        if (!cal) return;
        cal.querySelectorAll('select.flatpickr-monthDropdown-months').forEach((select) => {
            select.setAttribute('data-no-search', '');
            if (select.tomselect) {
                try { select.tomselect.destroy(); } catch (e) { /* ignore */ }
            }
            select.classList.remove('tomselected', 'is-searchable', 'ts-hidden-accessible');
            select.classList.add('ems-fp-month-native');
            select.setAttribute('tabindex', '-1');
            select.setAttribute('aria-hidden', 'true');
            select.style.display = 'none';
        });
        cal.querySelectorAll('.ts-wrapper').forEach((el) => el.remove());
    }

    function enhanceMonthMenu(instance) {
        const cal = instance && instance.calendarContainer;
        if (!cal || cal.dataset.emsMonthEnhanced === '1') return;
        cal.dataset.emsMonthEnhanced = '1';

        const host = cal.querySelector('.flatpickr-current-month');
        if (!host) return;

        scrubNativeMonthSelect(cal);

        let trigger = host.querySelector('.ems-fp-month-trigger');
        if (!trigger) {
            trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'ems-fp-month-trigger';
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.setAttribute('aria-label', 'Select month');

            const curMonth = host.querySelector('.cur-month');
            const nativeSelect = host.querySelector('select.flatpickr-monthDropdown-months');
            if (curMonth) {
                curMonth.replaceWith(trigger);
                instance.currentMonthElement = trigger;
            } else if (nativeSelect) {
                nativeSelect.insertAdjacentElement('beforebegin', trigger);
            } else {
                host.insertBefore(trigger, host.firstChild);
            }
        }

        let menu = cal.querySelector('.ems-fp-month-menu');
        if (!menu) {
            menu = document.createElement('div');
            menu.className = 'ems-fp-month-menu';
            menu.setAttribute('role', 'listbox');
            menu.setAttribute('aria-label', 'Months');
            menu.hidden = true;

            const labels = (instance.l10n && instance.l10n.months && instance.l10n.months.shorthand)
                || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            labels.forEach((label, idx) => {
                const opt = document.createElement('button');
                opt.type = 'button';
                opt.className = 'ems-fp-month-option';
                opt.setAttribute('role', 'option');
                opt.dataset.month = String(idx);
                opt.textContent = label;
                opt.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    instance.changeMonth(idx, false);
                    closeMenu();
                });
                menu.appendChild(opt);
            });

            cal.appendChild(menu);
        } else if (menu.parentElement !== cal) {
            cal.appendChild(menu);
        }

        function syncLabel() {
            const m = instance.currentMonth;
            const longhand = (instance.l10n && instance.l10n.months && instance.l10n.months.longhand)
                || ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            trigger.textContent = longhand[m] || '';
            menu.querySelectorAll('.ems-fp-month-option').forEach((btn, i) => {
                const active = i === m;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

        function openMenu() {
            syncLabel();
            menu.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            cal.classList.add('ems-fp-month-open');
        }

        function closeMenu() {
            if (menu.hidden) return;
            menu.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            cal.classList.remove('ems-fp-month-open');
        }

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (menu.hidden) openMenu();
            else closeMenu();
        });

        cal.addEventListener('click', (e) => {
            if (!menu.hidden && !trigger.contains(e.target) && !menu.contains(e.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMenu();
        });

        const pushHook = (name, fn) => {
            const existing = instance.config[name];
            if (Array.isArray(existing)) existing.push(fn);
            else if (typeof existing === 'function') instance.config[name] = [existing, fn];
            else instance.config[name] = [fn];
        };

        pushHook('onMonthChange', syncLabel);
        pushHook('onYearChange', syncLabel);
        pushHook('onClose', closeMenu);

        syncLabel();
        instance._emsCloseMonthMenu = closeMenu;
    }

    function enhanceCalendar(instance) {
        if (!instance || !instance.calendarContainer) return;
        applyTheme(instance.calendarContainer);
        scrubNativeMonthSelect(instance.calendarContainer);
        enhanceMonthMenu(instance);
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
            disableMobile: true,
            animate: true,
            monthSelectorType: 'static',
            appendTo: document.body,
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
                enhanceCalendar(instance);
                if (instance.altInput) {
                    input.classList.remove('panel-input', 'ems-dtp__input');
                    input.classList.add('ems-dtp__raw');
                    instance.altInput.classList.add('panel-input', 'ems-dtp__input');
                }
            },
            onOpen(_, __, instance) {
                enhanceCalendar(instance);
                if (instance._emsCloseMonthMenu) instance._emsCloseMonthMenu();
            },
        });

        const wrap = input.closest('[data-ems-datetime]');
        wrap?.querySelector('[data-ems-datetime-toggle]')?.addEventListener('click', () => fp.open());

        return fp;
    }

    function setValue(inputOrId, value) {
        const input = typeof inputOrId === 'string'
            ? document.getElementById(inputOrId)
            : inputOrId;
        if (!input) return;
        if (input._flatpickr) {
            if (value) input._flatpickr.setDate(String(value).replace('T', ' '), true);
            else input._flatpickr.clear();
            return;
        }
        input.value = value || '';
    }

    async function initAll(root = document) {
        const inputs = [...root.querySelectorAll('[data-ems-datetime-input]')];
        if (!inputs.length) return [];

        await ensureAssets();
        return inputs.map((input) => mountInput(input)).filter(Boolean);
    }

    const themeObserver = new MutationObserver(() => {
        document.querySelectorAll('.flatpickr-calendar').forEach((cal) => applyTheme(cal));
    });
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class', 'data-theme'],
    });

    global.EmsDateTimePicker = {
        initAll,
        mountInput,
        ensureAssets,
        setValue,
        enhanceCalendar,
        applyTheme,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initAll());
    } else {
        initAll();
    }
})(window);
