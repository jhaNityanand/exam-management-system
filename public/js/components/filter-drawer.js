(function (global) {
    'use strict';

    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const startOfWeek = (date) => {
        const result = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const day = result.getDay() || 7;
        result.setDate(result.getDate() - day + 1);
        return result;
    };

    const endOfWeek = (date) => {
        const result = startOfWeek(date);
        result.setDate(result.getDate() + 6);
        return result;
    };

    const startOfQuarter = (date) => {
        const quarterStartMonth = Math.floor(date.getMonth() / 3) * 3;
        return new Date(date.getFullYear(), quarterStartMonth, 1);
    };

    const endOfQuarter = (date) => {
        const quarterStartMonth = Math.floor(date.getMonth() / 3) * 3;
        return new Date(date.getFullYear(), quarterStartMonth + 3, 0);
    };

    const presetRange = (preset) => {
        const today = new Date();
        const current = new Date(today.getFullYear(), today.getMonth(), today.getDate());

        switch (preset) {
            case 'today':
                return [current, current];
            case 'yesterday': {
                const yesterday = new Date(current);
                yesterday.setDate(yesterday.getDate() - 1);
                return [yesterday, yesterday];
            }
            case 'this_week':
                return [startOfWeek(current), endOfWeek(current)];
            case 'last_week': {
                const previous = new Date(current);
                previous.setDate(previous.getDate() - 7);
                return [startOfWeek(previous), endOfWeek(previous)];
            }
            case 'this_month':
                return [
                    new Date(current.getFullYear(), current.getMonth(), 1),
                    new Date(current.getFullYear(), current.getMonth() + 1, 0),
                ];
            case 'last_month':
                return [
                    new Date(current.getFullYear(), current.getMonth() - 1, 1),
                    new Date(current.getFullYear(), current.getMonth(), 0),
                ];
            case 'this_quarter':
                return [startOfQuarter(current), endOfQuarter(current)];
            case 'last_quarter': {
                const previousQuarter = new Date(current.getFullYear(), current.getMonth() - 3, 1);
                return [startOfQuarter(previousQuarter), endOfQuarter(previousQuarter)];
            }
            case 'this_year':
                return [
                    new Date(current.getFullYear(), 0, 1),
                    new Date(current.getFullYear(), 11, 31),
                ];
            case 'last_year':
                return [
                    new Date(current.getFullYear() - 1, 0, 1),
                    new Date(current.getFullYear() - 1, 11, 31),
                ];
            default:
                return null;
        }
    };

    const setPickerValue = (input, value) => {
        if (!input) return;
        if (input._flatpickr) {
            if (value) {
                input._flatpickr.setDate(value, true);
            } else {
                input._flatpickr.clear();
            }
            return;
        }
        input.value = value || '';
    };

    const setRange = (container, dates) => {
        const from = container.querySelector('[data-range-from]');
        const to = container.querySelector('[data-range-to]');
        setPickerValue(from, dates?.[0] ? formatDate(dates[0]) : '');
        setPickerValue(to, dates?.[1] ? formatDate(dates[1]) : '');
    };

    const setRangeError = (container, visible) => {
        const error = container.querySelector('[data-range-error]');
        if (!error) return;
        error.hidden = !visible;
        container.classList.toggle('has-range-error', Boolean(visible));
    };

    const getInputDateValue = (input) => {
        if (!input) return '';
        if (input._flatpickr?.selectedDates?.[0]) {
            return formatDate(input._flatpickr.selectedDates[0]);
        }
        return String(input.value || '').trim();
    };

    const syncToMinDate = (fromInput, toInput) => {
        if (!toInput?._flatpickr) return;
        const fromValue = getInputDateValue(fromInput);
        toInput._flatpickr.set('minDate', fromValue || null);

        const toValue = getInputDateValue(toInput);
        if (fromValue && toValue && toValue < fromValue) {
            toInput._flatpickr.clear();
        }
    };

    const isCustomRangeValid = (container) => {
        const preset = container.querySelector('[data-date-preset-select]')?.value || '';
        if (preset !== 'custom') {
            setRangeError(container, false);
            return true;
        }

        const fromValue = getInputDateValue(container.querySelector('[data-range-from]'));
        const toValue = getInputDateValue(container.querySelector('[data-range-to]'));

        if (fromValue && toValue && toValue < fromValue) {
            setRangeError(container, true);
            return false;
        }

        setRangeError(container, false);
        return true;
    };

    const validateAll = (root = document) => {
        const ranges = [...root.querySelectorAll('[data-filter-date-range]')];
        let firstInvalid = null;

        ranges.forEach((container) => {
            if (!isCustomRangeValid(container) && !firstInvalid) {
                firstInvalid = container;
            }
        });

        if (!firstInvalid) {
            return { valid: true, message: '' };
        }

        const label = firstInvalid.querySelector('.filter-label')?.textContent?.trim() || 'Date';
        return {
            valid: false,
            message: `${label}: the To date must be greater than or equal to the From date.`,
            container: firstInvalid,
        };
    };

    const mountDateRange = async (container) => {
        if (!container || container.dataset.rangeMounted === '1') return;
        container.dataset.rangeMounted = '1';

        const presetSelect = container.querySelector('[data-date-preset-select]');
        const customWrap = container.querySelector('[data-custom-range]');
        const fromInput = container.querySelector('[data-range-from]');
        const toInput = container.querySelector('[data-range-to]');

        if (global.EmsDateTimePicker) {
            await global.EmsDateTimePicker.initAll(container);
        }

        const applyPreset = (preset) => {
            const isCustom = preset === 'custom';
            customWrap.hidden = !isCustom;
            setRangeError(container, false);

            if (!preset) {
                setRange(container, null);
                syncToMinDate(fromInput, toInput);
                return;
            }

            if (isCustom) {
                setRange(container, null);
                syncToMinDate(fromInput, toInput);
                return;
            }

            setRange(container, presetRange(preset));
            syncToMinDate(fromInput, toInput);
        };

        const onFromChange = () => {
            if (presetSelect && presetSelect.value !== 'custom' && (fromInput.value || toInput?.value)) {
                presetSelect.value = 'custom';
                customWrap.hidden = false;
            }
            syncToMinDate(fromInput, toInput);
            isCustomRangeValid(container);
        };

        const onToChange = () => {
            if (presetSelect && presetSelect.value !== 'custom' && (fromInput?.value || toInput.value)) {
                presetSelect.value = 'custom';
                customWrap.hidden = false;
            }
            isCustomRangeValid(container);
        };

        if (fromInput?._flatpickr) {
            fromInput._flatpickr.config.onChange.push(onFromChange);
        } else {
            fromInput?.addEventListener('change', onFromChange);
        }

        if (toInput?._flatpickr) {
            toInput._flatpickr.config.onChange.push(onToChange);
        } else {
            toInput?.addEventListener('change', onToChange);
        }

        fromInput?.addEventListener('input', onFromChange);
        toInput?.addEventListener('input', onToChange);

        presetSelect?.addEventListener('change', () => {
            applyPreset(presetSelect.value || '');
        });

        container._resetFilterDateRange = () => {
            if (presetSelect) presetSelect.value = '';
            customWrap.hidden = true;
            setRangeError(container, false);
            setRange(container, null);
            syncToMinDate(fromInput, toInput);
        };

        container._validateFilterDateRange = () => isCustomRangeValid(container);
        syncToMinDate(fromInput, toInput);
    };

    const mountMultiSelect = (select) => {
        if (!select || select.tomselect || !global.TomSelect) return null;

        const config = {
            create: false,
            plugins: ['remove_button'],
            placeholder: select.dataset.placeholder || 'Select one or more…',
            maxItems: null,
            maxOptions: select.dataset.maxOptions ? Number(select.dataset.maxOptions) : null,
            closeAfterSelect: false,
            hideSelected: true,
        };

        if (select.dataset.filterHierarchy === '1' && global.EmsTomSelectHierarchy) {
            return global.EmsTomSelectHierarchy.create(select, config);
        }

        return new global.TomSelect(select, config);
    };

    const initAll = (root = document) => {
        root.querySelectorAll('select[data-filter-multiple]').forEach(mountMultiSelect);
        root.querySelectorAll('[data-filter-date-range]').forEach(mountDateRange);

        const form = root.querySelector('#filter-drawer-form');
        form?.addEventListener('reset', () => {
            setTimeout(() => {
                form.querySelectorAll('[data-filter-date-range]').forEach((container) => {
                    container._resetFilterDateRange?.();
                });
            }, 0);
        });
    };

    global.EmsFilterDrawer = {
        initAll,
        mountMultiSelect,
        mountDateRange,
        presetRange,
        validateAll,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initAll());
    } else {
        initAll();
    }
})(window);
