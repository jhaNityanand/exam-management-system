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

    // ==========================================
    // Automatic Form Auto-Save Engine (localStorage)
    // Storage Key: exam_create_form / question_create_form / etc.
    // ==========================================
    const isBlacklistedField = (input) => {
        if (!input || !input.name) return true;
        const type = (input.type || '').toLowerCase();
        const name = (input.name || '').toLowerCase();

        if (type === 'password' || type === 'file') return true;
        if (name === '_token' || name === '_method' || name.includes('password') || name.includes('secret')) return true;
        if (input.dataset.draftIgnore === 'true') return true;
        return false;
    };

    const getFormStorageKey = (form) => {
        if (form.dataset.autoDraft) {
            return form.dataset.autoDraft;
        }
        if (form.id) {
            return form.id.replace(/-/g, '_');
        }
        return 'exam_create_form';
    };

    const serializeForm = (form) => {
        const fields = {};
        const elements = form.querySelectorAll('input, select, textarea');

        elements.forEach((el) => {
            if (isBlacklistedField(el)) return;

            const name = el.name;
            const type = (el.type || '').toLowerCase();

            if (type === 'checkbox') {
                if (name.endsWith('[]')) {
                    if (!fields[name]) fields[name] = [];
                    if (el.checked && !fields[name].includes(el.value)) {
                        fields[name].push(el.value);
                    }
                } else {
                    fields[name] = el.checked;
                }
            } else if (type === 'radio') {
                if (el.checked) {
                    fields[name] = el.value;
                }
            } else if (el.tagName === 'SELECT' && el.multiple) {
                fields[name] = Array.from(el.selectedOptions).map((opt) => opt.value);
            } else {
                fields[name] = el.value;
            }
        });

        return fields;
    };

    const restoreFormValues = (form, fields) => {
        if (!fields || typeof fields !== 'object') return false;

        Object.entries(fields).forEach(([name, value]) => {
            if (value === null || value === undefined) return;

            if (Array.isArray(value)) {
                const inputs = form.querySelectorAll(`[name="${name}"], [name="${name.replace('[]', '')}"]`);
                inputs.forEach((input) => {
                    const type = (input.type || '').toLowerCase();
                    if (type === 'checkbox') {
                        input.checked = value.includes(input.value);
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    } else if (input.tagName === 'SELECT') {
                        Array.from(input.options).forEach((opt) => {
                            opt.selected = value.includes(opt.value);
                        });
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        if (input.tomselect) {
                            try { input.tomselect.setValue(value, true); } catch (e) {}
                        }
                    }
                });
            } else {
                const input = form.querySelector(`[name="${name}"]`);
                if (input) {
                    const type = (input.type || '').toLowerCase();
                    if (type === 'checkbox') {
                        input.checked = Boolean(value);
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    } else if (type === 'radio') {
                        const targetRadio = form.querySelector(`[name="${name}"][value="${String(value).replace(/"/g, '\\"')}"]`);
                        if (targetRadio) {
                            targetRadio.checked = true;
                            targetRadio.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    } else {
                        input.value = value;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        if (input.tomselect) {
                            try { input.tomselect.setValue(value, true); } catch (e) {}
                        }
                        if (input._flatpickr) {
                            try { input._flatpickr.setDate(value, true); } catch (e) {}
                        }
                    }
                }
            }
        });

        form.dispatchEvent(new CustomEvent('ems:draft-restored', { detail: { fields } }));
        return true;
    };

    const initFormAutoSave = (formElement) => {
        const form = typeof formElement === 'string' ? document.querySelector(formElement) : formElement;
        if (!form) return null;

        const storageKey = getFormStorageKey(form);

        // Auto-restore saved values immediately on page load if no server validation errors
        const hasServerValidationErrors = Boolean(form.querySelector('.is-invalid, .has-error'));
        if (!hasServerValidationErrors) {
            try {
                const raw = localStorage.getItem(storageKey);
                if (raw) {
                    const parsed = JSON.parse(raw);
                    const fieldsData = parsed.fields || parsed;
                    restoreFormValues(form, fieldsData);
                }
            } catch (e) {
                console.warn('Failed to restore draft from storage key:', storageKey, e);
            }
        }

        // Auto-save on input or change
        let timer = null;
        const save = () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                try {
                    const fields = serializeForm(form);
                    localStorage.setItem(storageKey, JSON.stringify({
                        storage_key: storageKey,
                        saved_at: Date.now(),
                        fields: fields,
                    }));
                } catch (e) {
                    console.warn('Failed to auto-save storage key:', storageKey, e);
                }
            }, 300);
        };

        form.addEventListener('input', save);
        form.addEventListener('change', save);

        // Clear storage key on successful submission
        form.addEventListener('submit', () => {
            try {
                localStorage.removeItem(storageKey);
            } catch (e) {}
        });

        return {
            storageKey,
            save: () => save(),
            clear: () => localStorage.removeItem(storageKey),
            restore: () => {
                const raw = localStorage.getItem(storageKey);
                if (raw) restoreFormValues(form, JSON.parse(raw).fields || JSON.parse(raw));
            }
        };
    };

    const autoInitAllForms = () => {
        document.querySelectorAll('form[data-auto-draft], form#exam-create-form, form#question-form').forEach((form) => {
            initFormAutoSave(form);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInitAllForms);
    } else {
        autoInitAllForms();
    }

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
        initFormAutoSave,
        restoreFormValues,
    };
})(window);
