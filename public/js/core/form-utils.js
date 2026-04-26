(function bootstrapFormUtils(window) {
    if (window.EmsFormUtils) {
        return;
    }

    const clean = (value) => String(value ?? '').trim();

    const toNumber = (value) => {
        const parsed = Number.parseFloat(clean(value));
        return Number.isFinite(parsed) ? parsed : NaN;
    };

    const parseDateTime = (value, format = 'Y-m-d H:i') => {
        const normalized = clean(value);
        if (!normalized) {
            return null;
        }

        if (window.flatpickr?.parseDate) {
            return window.flatpickr.parseDate(normalized, format) || null;
        }

        const fallback = new Date(normalized.replace(' ', 'T'));
        return Number.isNaN(fallback.getTime()) ? null : fallback;
    };

    const formatDateTime = (date, format = 'Y-m-d H:i') => {
        if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
            return '';
        }

        if (window.flatpickr?.formatDate) {
            return window.flatpickr.formatDate(date, format);
        }

        const pad = (num) => String(num).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
    };

    const formatHumanDateTime = (value) => {
        const parsed = value instanceof Date ? value : parseDateTime(value);
        if (!parsed) {
            return '';
        }

        return new Intl.DateTimeFormat(undefined, {
            month: 'short',
            day: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(parsed);
    };

    const createErrorManager = (form) => {
        const bag = new Map();

        form.querySelectorAll('[data-error-for]').forEach((node) => {
            bag.set(node.dataset.errorFor, node);
        });

        const toggleFieldState = (fieldName, isInvalid) => {
            const field = form.querySelector(`[name="${fieldName}"]`) || document.getElementById(fieldName);
            if (!field) {
                return;
            }

            field.classList.toggle('is-invalid', isInvalid);

            if (field.tomselect?.control) {
                field.tomselect.control.classList.toggle('is-invalid', isInvalid);
            }
        };

        const clear = (fieldName) => {
            const target = bag.get(fieldName);
            if (!target) {
                return;
            }

            target.textContent = '';
            target.classList.remove('is-visible');
            toggleFieldState(fieldName, false);
        };

        const set = (fieldName, message) => {
            const target = bag.get(fieldName);
            if (!target) {
                return;
            }

            target.textContent = message;
            target.classList.add('is-visible');
            toggleFieldState(fieldName, true);
        };

        const clearAll = () => {
            bag.forEach((_, key) => clear(key));
        };

        return { clear, set, clearAll };
    };

    const initDateTimePicker = (input, options = {}) => {
        if (!input || typeof window.flatpickr === 'undefined') {
            return null;
        }

        return window.flatpickr(input, {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            time_24hr: false,
            minuteIncrement: 5,
            ...options,
        });
    };

    const bindAutoEndDateTime = ({ startInput, endInput, durationInput, onSync }) => {
        const sync = () => {
            const startDate = parseDateTime(startInput?.value);
            const duration = toNumber(durationInput?.value);

            if (!startDate || !Number.isFinite(duration) || duration <= 0) {
                if (endInput) {
                    endInput.value = '';
                }
                onSync?.(null);
                return null;
            }

            const endDate = new Date(startDate.getTime() + duration * 60000);
            if (endInput) {
                endInput.value = formatDateTime(endDate);
            }
            onSync?.(endDate);
            return endDate;
        };

        ['input', 'change'].forEach((eventName) => {
            startInput?.addEventListener(eventName, sync);
            durationInput?.addEventListener(eventName, sync);
        });

        return { sync };
    };

    const initTomSelect = (selector, options = {}) => {
        if (typeof window.TomSelect === 'undefined') {
            return null;
        }

        const target = document.querySelector(selector);
        if (!target) {
            return null;
        }

        return new window.TomSelect(target, {
            create: false,
            ...options,
        });
    };

    window.EmsFormUtils = {
        clean,
        toNumber,
        parseDateTime,
        formatDateTime,
        formatHumanDateTime,
        createErrorManager,
        initDateTimePicker,
        bindAutoEndDateTime,
        initTomSelect,
    };
})(window);
