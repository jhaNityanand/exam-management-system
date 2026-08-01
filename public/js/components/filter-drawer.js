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
            case 'last_7_days': {
                const from = new Date(current);
                from.setDate(from.getDate() - 6);
                return [from, current];
            }
            case 'last_30_days': {
                const from = new Date(current);
                from.setDate(from.getDate() - 29);
                return [from, current];
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

        // Date presets use a native select so the menu always opens on-screen.
        if (presetSelect?.tomselect) {
            try {
                presetSelect.tomselect.destroy();
            } catch (err) {
                /* ignore */
            }
        }

        if (global.EmsDateTimePicker) {
            await global.EmsDateTimePicker.initAll(container);
        }

        let applyingPreset = false;

        const setPresetValue = (value) => {
            if (!presetSelect) return;
            presetSelect.value = value || '';
            // Keep Tom Select in sync if an older page still enhanced this control.
            if (presetSelect.tomselect) {
                try {
                    presetSelect.tomselect.destroy();
                } catch (err) {
                    /* ignore */
                }
            }
        };

        const applyPreset = (preset) => {
            const isCustom = preset === 'custom';
            customWrap.hidden = !isCustom;
            setRangeError(container, false);
            applyingPreset = true;

            try {
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
            } finally {
                applyingPreset = false;
            }
        };

        const onFromChange = () => {
            if (applyingPreset) {
                syncToMinDate(fromInput, toInput);
                return;
            }
            if (presetSelect && presetSelect.value !== 'custom' && (fromInput.value || toInput?.value)) {
                setPresetValue('custom');
                customWrap.hidden = false;
            }
            syncToMinDate(fromInput, toInput);
            isCustomRangeValid(container);
        };

        const onToChange = () => {
            if (applyingPreset) {
                return;
            }
            if (presetSelect && presetSelect.value !== 'custom' && (fromInput?.value || toInput.value)) {
                setPresetValue('custom');
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

        const onPresetChange = () => {
            applyPreset(presetSelect.value || '');
        };

        presetSelect?.addEventListener('change', onPresetChange);

        container._resetFilterDateRange = () => {
            setPresetValue('');
            customWrap.hidden = true;
            setRangeError(container, false);
            setRange(container, null);
            syncToMinDate(fromInput, toInput);
        };

        container._validateFilterDateRange = () => isCustomRangeValid(container);
        syncToMinDate(fromInput, toInput);
    };

    /**
     * Position a Tom Select dropdown above the control when there is not enough
     * space below (e.g. near the bottom of the filter drawer / viewport).
     * Max-height is applied to .ts-dropdown-content only so a single scrollbar appears.
     */
    function positionTomSelectDropdown(instance) {
        if (!instance?.dropdown || !instance?.control) return;

        const dropdown = instance.dropdown;
        const content = instance.dropdown_content
            || dropdown.querySelector('.ts-dropdown-content');

        dropdown.classList.remove('ts-dropdown--up');
        dropdown.style.maxHeight = '';
        dropdown.style.overflow = 'hidden';
        if (content) {
            content.style.maxHeight = '';
        }

        window.requestAnimationFrame(() => {
            const controlRect = instance.control.getBoundingClientRect();
            const viewportH = window.innerHeight || document.documentElement.clientHeight;
            const spaceBelow = Math.max(0, viewportH - controlRect.bottom - 8);
            const spaceAbove = Math.max(0, controlRect.top - 8);
            const contentNatural = content
                ? Math.max(content.scrollHeight || 0, 120)
                : Math.max(dropdown.scrollHeight || 200, 160);
            const chrome = Math.max(0, (dropdown.offsetHeight || 0) - (content?.offsetHeight || contentNatural));
            const naturalHeight = Math.min(320, contentNatural + chrome);
            const openUp = spaceBelow < Math.min(naturalHeight, 240) && spaceAbove > spaceBelow;
            const shellMax = Math.max(120, Math.min(naturalHeight, openUp ? spaceAbove : spaceBelow));
            const contentMax = Math.max(96, shellMax - chrome);

            if (content) {
                content.style.maxHeight = `${contentMax}px`;
                content.style.overflowX = 'hidden';
                content.style.overflowY = 'auto';
            } else {
                dropdown.style.maxHeight = `${shellMax}px`;
            }

            dropdown.classList.toggle('ts-dropdown--up', openUp);

            if (openUp) {
                const height = Math.min(dropdown.offsetHeight || shellMax, shellMax);
                dropdown.style.top = `${Math.max(8, controlRect.top - height - 6)}px`;
            }
        });
    }

    const mountMultiSelect = (select) => {
        if (!select || select.tomselect || !global.TomSelect) return null;

        const optionCount = Array.prototype.filter.call(select.options || [], (option) => {
            if (option.disabled && option.value === '') return false;
            return true;
        }).length;
        const searchMin = Number(global.EmsSelectConfig?.searchMinOptions) || 8;
        const forceSearch = select.dataset.forceSearch != null || select.hasAttribute('data-force-search');
        const disableSearch = select.dataset.disableSearch != null || select.hasAttribute('data-disable-search');
        const includeSearch = forceSearch || (!disableSearch && optionCount >= searchMin);

        const config = {
            create: false,
            plugins: includeSearch ? ['remove_button', 'dropdown_input'] : ['remove_button'],
            placeholder: select.dataset.placeholder
                || global.EmsSelectConfig?.placeholder
                || 'Select one or more options',
            maxItems: null,
            maxOptions: select.dataset.maxOptions ? Number(select.dataset.maxOptions) : null,
            closeAfterSelect: false,
            hideSelected: true,
            searchField: ['text'],
            // Portal out of the scrollable drawer so menus are not clipped.
            dropdownParent: 'body',
            onInitialize() {
                this.wrapper.classList.add('ems-select-wrapper');
                this.wrapper.classList.add('is-multiple');
                this.dropdown.classList.add('ems-select-dropdown');
                this.dropdown.classList.add('ems-filter-dropdown');
            },
            onDropdownOpen() {
                this.dropdown.classList.add('is-open');
                positionTomSelectDropdown(this);
            },
            onDropdownClose() {
                this.dropdown.classList.remove('is-open');
                this.dropdown.classList.remove('ts-dropdown--up');
                this.dropdown.style.top = '';
                this.dropdown.style.bottom = '';
                this.dropdown.style.maxHeight = '';
                const content = this.dropdown_content
                    || this.dropdown?.querySelector?.('.ts-dropdown-content');
                if (content) {
                    content.style.maxHeight = '';
                }
            },
        };

        if (!includeSearch) {
            config.score = () => () => 1;
        }

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
        positionTomSelectDropdown,
        presetRange,
        validateAll,
        ensureBackdrop() {
            let backdrop = document.querySelector('#offcanvas-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'offcanvas-backdrop';
                backdrop.className = 'offcanvas-backdrop';
                document.body.appendChild(backdrop);
            }
            return backdrop;
        },
        open(drawerSelector = '#filter-drawer', toggleSelector = '#btn-toggle-filters') {
            const drawer = document.querySelector(drawerSelector);
            const toggle = document.querySelector(toggleSelector);
            if (drawer) {
                drawer.classList.add('is-open');
                drawer.setAttribute('aria-hidden', 'false');
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'true');
            }
            const backdrop = this.ensureBackdrop();
            backdrop.classList.add('is-visible');
            document.body.style.overflow = 'hidden';
        },
        close(drawerSelector = '#filter-drawer', toggleSelector = '#btn-toggle-filters') {
            const drawer = document.querySelector(drawerSelector);
            const toggle = document.querySelector(toggleSelector);
            if (drawer) {
                drawer.classList.remove('is-open');
                drawer.setAttribute('aria-hidden', 'true');
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            const backdrop = document.querySelector('#offcanvas-backdrop');
            if (backdrop) {
                backdrop.classList.remove('is-visible');
            }
            document.body.style.overflow = '';
        },
        bindShell(options = {}) {
            const drawerSelector = options.drawerSelector || '#filter-drawer';
            const toggleSelector = options.toggleSelector || '#btn-toggle-filters';
            const formSelector = options.formSelector || '#filter-drawer-form';
            const onApply = typeof options.onApply === 'function' ? options.onApply : null;
            const onReset = typeof options.onReset === 'function' ? options.onReset : null;

            const drawer = document.querySelector(drawerSelector);
            const toggle = document.querySelector(toggleSelector);
            const form = document.querySelector(formSelector);
            if (!drawer || !form) {
                return null;
            }

            const backdrop = this.ensureBackdrop();
            toggle?.addEventListener('click', () => this.open(drawerSelector, toggleSelector));
            drawer.querySelectorAll('.offcanvas-close').forEach((btn) => {
                btn.addEventListener('click', () => this.close(drawerSelector, toggleSelector));
            });
            backdrop.addEventListener('click', () => this.close(drawerSelector, toggleSelector));

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const validation = validateAll(form);
                if (validation && validation.valid === false) {
                    validation.container?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    return;
                }
                onApply?.(form);
                this.close(drawerSelector, toggleSelector);
            });

            form.querySelector('[type="reset"]')?.addEventListener('click', (event) => {
                event.preventDefault();
                form.reset();
                form.querySelectorAll('[data-filter-date-range]').forEach((container) => {
                    container._resetFilterDateRange?.();
                });
                form.querySelectorAll('select').forEach((select) => {
                    if (select.tomselect) {
                        select.tomselect.clear(true);
                        select.tomselect.setValue([], true);
                    }
                });
                onReset?.(form);
            });

            return { drawer, toggle, form };
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initAll());
    } else {
        initAll();
    }
})(window);
