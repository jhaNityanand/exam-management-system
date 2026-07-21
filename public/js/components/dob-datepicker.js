/**
 * Shared DOB date picker (Flatpickr) — max date = today.
 * Theme-aware light/dark calendar (body-appended calendars included).
 * Usage: mark inputs with [data-dob-picker]
 */
(function (window, document) {
    'use strict';

    var FLATPICKR_CSS = 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css';
    var FLATPICKR_JS = 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js';
    var loading = null;
    var themeWatching = false;
    var instances = [];

    function resolveThemeCssHref() {
        var tagged = document.querySelector('link[data-dob-theme]');
        if (tagged && tagged.href) return tagged.href;

        var selfScript = document.querySelector('script[src*="dob-datepicker.js"]');
        if (selfScript && selfScript.src) {
            var href = selfScript.src
                .replace(/\/js\/components\/dob-datepicker\.js(\?.*)?$/, '/css/components/dob-datepicker.css$1');
            if (href !== selfScript.src) return href;
        }

        return '/css/components/dob-datepicker.css';
    }

    function ensureStyles() {
        if (!document.querySelector('link[data-dob-flatpickr]')) {
            var fp = document.createElement('link');
            fp.rel = 'stylesheet';
            fp.href = FLATPICKR_CSS;
            fp.setAttribute('data-dob-flatpickr', '1');
            document.head.appendChild(fp);
        }

        if (!document.querySelector('link[data-dob-theme]')) {
            var theme = document.createElement('link');
            theme.rel = 'stylesheet';
            theme.href = resolveThemeCssHref();
            theme.setAttribute('data-dob-theme', '1');
            document.head.appendChild(theme);
        }
    }

    function loadScript(src) {
        if (window.flatpickr) return Promise.resolve(window.flatpickr);
        if (loading) return loading;

        loading = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = function () { resolve(window.flatpickr); };
            script.onerror = function () {
                loading = null;
                reject(new Error('Unable to load date picker.'));
            };
            document.head.appendChild(script);
        });

        return loading;
    }

    function isDarkMode() {
        var html = document.documentElement;
        var body = document.body;
        if (html.classList.contains('dark') || (body && body.classList.contains('dark'))) return true;
        if ((html.dataset.theme || (body && body.dataset.theme)) === 'dark') return true;
        try {
            var stored = localStorage.getItem('examtube-theme')
                || localStorage.getItem('ems.theme')
                || localStorage.getItem('theme');
            if (stored === 'dark') return true;
            if (stored === 'light') return false;
            if (stored === 'system' && window.matchMedia) {
                return window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
        } catch (e) { /* ignore */ }
        return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    }

    function syncCalendarTheme(instance) {
        if (!instance || !instance.calendarContainer) return;
        var dark = isDarkMode();
        instance.calendarContainer.classList.toggle('dob-calendar--dark', dark);
        instance.calendarContainer.classList.toggle('ems-dtp-calendar--dark', dark);
    }

    function syncAllCalendars() {
        instances.forEach(syncCalendarTheme);
        document.querySelectorAll('.flatpickr-calendar').forEach(function (cal) {
            var dark = isDarkMode();
            cal.classList.toggle('dob-calendar--dark', dark);
            cal.classList.toggle('ems-dtp-calendar--dark', dark);
        });
    }

    function watchTheme() {
        if (themeWatching) return;
        themeWatching = true;

        var observer = new MutationObserver(function () {
            syncAllCalendars();
        });
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class', 'data-theme'],
        });
        if (document.body) {
            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class', 'data-theme'],
            });
        }

        window.addEventListener('storage', function (event) {
            if (event.key === 'examtube-theme' || event.key === 'ems.theme' || event.key === 'theme') {
                syncAllCalendars();
            }
        });

        window.addEventListener('ems:themechange', function () {
            syncAllCalendars();
        });

        document.addEventListener('click', function (event) {
            var toggle = event.target && event.target.closest
                ? event.target.closest('[data-theme-toggle], #theme-toggle-btn, [data-theme-option]')
                : null;
            if (toggle) {
                window.setTimeout(syncAllCalendars, 30);
            }
        });
    }

    function todayYmd() {
        var now = new Date();
        var m = String(now.getMonth() + 1).padStart(2, '0');
        var d = String(now.getDate()).padStart(2, '0');
        return now.getFullYear() + '-' + m + '-' + d;
    }

    function parseYmd(value) {
        if (!value) return null;
        var match = String(value).trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return null;
        var year = Number(match[1]);
        var month = Number(match[2]);
        var day = Number(match[3]);
        var date = new Date(year, month - 1, day);
        if (
            date.getFullYear() !== year
            || date.getMonth() !== month - 1
            || date.getDate() !== day
        ) {
            return null;
        }
        return date;
    }

    function isFutureDate(value) {
        var date = parseYmd(value);
        if (!date) return false;
        var today = parseYmd(todayYmd());
        return date.getTime() > today.getTime();
    }

    function validateDobValue(value) {
        var cleaned = String(value || '').trim();
        if (!cleaned) return '';
        if (!parseYmd(cleaned)) {
            return 'Enter a valid date in YYYY-MM-DD format.';
        }
        if (isFutureDate(cleaned)) {
            return 'Date of birth cannot be later than today.';
        }
        return '';
    }

    function enhanceNativeFallback(input) {
        input.setAttribute('max', todayYmd());
        input.addEventListener('change', function () {
            if (isFutureDate(input.value)) {
                input.value = todayYmd();
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    }

    function initInput(input, flatpickr) {
        if (!input || input._dobPicker) return input._dobPicker || null;

        if (input.type === 'date') {
            input.type = 'text';
        }

        input.setAttribute('placeholder', input.getAttribute('placeholder') || 'Select date of birth');
        input.setAttribute('autocomplete', 'bday');
        input.setAttribute('inputmode', 'numeric');

        var instance = flatpickr(input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            allowInput: true,
            maxDate: 'today',
            disableMobile: false,
            onReady: function (selectedDates, dateStr, fp) {
                syncCalendarTheme(fp);
            },
            onOpen: function (selectedDates, dateStr, fp) {
                syncCalendarTheme(fp);
            },
            onChange: function (selectedDates, dateStr) {
                if (isFutureDate(dateStr)) {
                    instance.setDate(todayYmd(), true);
                }
            },
            onClose: function (selectedDates, dateStr) {
                var message = validateDobValue(dateStr);
                if (message && dateStr) {
                    instance.clear();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            },
        });

        input._dobPicker = instance;
        instances.push(instance);
        syncCalendarTheme(instance);
        return instance;
    }

    function initAll(root) {
        var scope = root || document;
        var inputs = scope.querySelectorAll('[data-dob-picker]');
        if (!inputs.length) return Promise.resolve([]);

        ensureStyles();
        watchTheme();

        return loadScript(FLATPICKR_JS)
            .then(function (flatpickr) {
                return Array.prototype.map.call(inputs, function (input) {
                    return initInput(input, flatpickr);
                });
            })
            .catch(function () {
                Array.prototype.forEach.call(inputs, enhanceNativeFallback);
                return [];
            });
    }

    window.DobDatePicker = {
        initAll: initAll,
        validate: validateDobValue,
        isFuture: isFutureDate,
        today: todayYmd,
        syncTheme: syncAllCalendars,
    };
})(window, document);
