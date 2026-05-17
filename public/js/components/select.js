(function registerEmsSelect(global) {
    const instances = new Map();
    const changeHandlers = new Map();

    function safeTrim(value) {
        return String(value || '').trim();
    }

    function getPlaceholder(select) {
        if (select.dataset.placeholder) {
            return safeTrim(select.dataset.placeholder);
        }

        const firstOption = select.options[0];
        return firstOption ? safeTrim(firstOption.textContent) : 'Select option';
    }

    function ensureSelectId(select, index) {
        if (select.id) {
            return select.id;
        }

        const generated = `ems-select-${index + 1}`;
        select.id = generated;
        return generated;
    }

    function resolvePositiveInt(value) {
        const parsed = Number.parseInt(String(value ?? ''), 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    }

    function isVisibleFocusable(element) {
        if (!element || element.disabled) {
            return false;
        }

        if (element.matches('input[type="hidden"]')) {
            return false;
        }

        if (element.closest('[hidden]')) {
            return false;
        }

        return element.getClientRects().length > 0;
    }

    function focusNextFieldFrom(currentInput, selectElement) {
        const form = selectElement.closest('form');
        if (!form) {
            return;
        }

        const focusables = [...form.querySelectorAll('input:not([type="hidden"]), textarea, select, button, [tabindex]:not([tabindex="-1"])')]
            .filter(isVisibleFocusable);

        const currentIndex = focusables.indexOf(currentInput);
        if (currentIndex < 0) {
            return;
        }

        const nextField = focusables.slice(currentIndex + 1).find((field) => isVisibleFocusable(field));
        if (!nextField) {
            return;
        }

        nextField.focus({ preventScroll: true });
    }

    function readOptionHierarchyMeta(select, value) {
        const option = select.querySelector(`option[value="${CSS.escape(String(value))}"]`);
        if (!option) {
            return null;
        }

        const categoryName = safeTrim(option.dataset.categoryName || option.textContent);
        const availability = safeTrim(option.dataset.availability);
        const level = Number.parseInt(String(option.dataset.level ?? '0'), 10);

        if (!categoryName) {
            return null;
        }

        return {
            categoryName,
            availability,
            level: Number.isFinite(level) && level >= 0 ? level : 0,
        };
    }

    function formatHierarchyItemLabel(meta) {
        if (!meta) {
            return '';
        }
        if (meta.availability) {
            return `${meta.categoryName} (${meta.availability})`;
        }
        return meta.categoryName;
    }

    function renderHierarchyOption(select, data, escape) {
        const meta = readOptionHierarchyMeta(select, data.value);
        if (!meta) {
            return `<div class="ems-option-row"><span class="ems-option-text">${escape(data.text)}</span></div>`;
        }

        const label = formatHierarchyItemLabel(meta);

        return `
            <div class="ems-category-option" data-level="${meta.level}">
                <span class="ems-category-label">${escape(label)}</span>
            </div>
        `;
    }

    function renderHierarchyItem(select, data, escape) {
        const meta = readOptionHierarchyMeta(select, data.value);
        if (!meta) {
            return `<div class="ems-item-row">${escape(data.text)}</div>`;
        }

        return `<div class="ems-item-row ems-item-row--selected">${escape(formatHierarchyItemLabel(meta))}</div>`;
    }

    function createTomConfig(select) {
        const isMultiple = select.multiple || select.dataset.selectMode === 'multiple';
        const maxItems = isMultiple ? resolvePositiveInt(select.dataset.maxItems) : 1;
        const hierarchyOptions = select.dataset.optionStyle === 'hierarchy';

        return {
            create: false,
            allowEmptyOption: true,
            maxItems: isMultiple ? (maxItems ?? null) : 1,
            maxOptions: 500,
            persist: false,
            hideSelected: !isMultiple,
            closeAfterSelect: !isMultiple,
            dropdownParent: 'body',
            plugins: isMultiple ? ['remove_button'] : [],
            sortField: [{ field: '$order' }],
            searchField: ['text'],
            placeholder: getPlaceholder(select),
            onItemAdd(value) {
                if (!isMultiple) {
                    return;
                }

                const limit = resolvePositiveInt(select.dataset.maxItems);
                if (limit && this.items.length > limit) {
                    this.removeItem(value, true);
                }
            },
            render: {
                option(data, escape) {
                    if (hierarchyOptions) {
                        return renderHierarchyOption(select, data, escape);
                    }
                    return `<div class="ems-option-row"><span class="ems-option-text">${escape(data.text)}</span></div>`;
                },
                item(data, escape) {
                    if (hierarchyOptions) {
                        return renderHierarchyItem(select, data, escape);
                    }
                    return `<div class="ems-item-row">${escape(data.text)}</div>`;
                },
                no_results(data, escape) {
                    return `<div class="ems-no-results">No matches for "${escape(data.input)}"</div>`;
                },
            },
            onInitialize() {
                this.wrapper.classList.add('ems-select-wrapper');
                this.wrapper.classList.toggle('is-multiple', isMultiple);
                this.wrapper.classList.remove('panel-input');
                this.dropdown.classList.add('ems-select-dropdown');
            },
            onDropdownOpen() {
                this.dropdown.classList.add('is-open');
            },
            onDropdownClose() {
                this.dropdown.classList.remove('is-open');
            },
            onChange() {
                if (isMultiple) {
                    return;
                }

                const activeElement = document.activeElement;
                if (!this.wrapper.contains(activeElement)) {
                    return;
                }

                window.setTimeout(() => {
                    this.blur();
                    focusNextFieldFrom(this.control_input, select);
                }, 0);
            },
        };
    }

    function initOne(select, index) {
        if (!(select instanceof HTMLSelectElement)) {
            return null;
        }

        const id = ensureSelectId(select, index);
        if (instances.has(id)) {
            return instances.get(id);
        }

        if (typeof global.TomSelect === 'undefined') {
            return null;
        }

        const instance = new global.TomSelect(select, createTomConfig(select));
        instances.set(id, instance);

        const changeHandler = changeHandlers.get(id);
        if (changeHandler) {
            instance.on('change', changeHandler);
        }

        return instance;
    }

    function initAll(root = document, selector = 'select.panel-input') {
        const selects = [...root.querySelectorAll(selector)];
        selects.forEach((select, index) => {
            initOne(select, index);
        });
        return instances;
    }

    function setValue(selectId, value, silent = true) {
        const instance = instances.get(selectId);
        if (instance) {
            instance.setValue(value, silent);
            return;
        }

        const select = document.getElementById(selectId);
        if (select) {
            select.value = value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function refresh(selectId) {
        const instance = instances.get(selectId);
        if (!instance) {
            return;
        }

        try {
            instance.sync();
        } catch (error) {
            console.warn(`Select refresh failed for "${selectId}"`, error);
            reinit(selectId);
        }
    }

    function replaceOptions(selectId, html, values = null, maxItems = null) {
        const select = document.getElementById(selectId);
        if (!select) {
            return null;
        }

        if (maxItems != null) {
            select.dataset.maxItems = String(maxItems);
        }

        destroy(selectId);
        select.innerHTML = html;

        const instance = initOne(select, 0);
        if (!instance) {
            return null;
        }

        if (maxItems != null) {
            setMaxItems(selectId, maxItems);
        }

        if (values != null) {
            const normalizedValues = Array.isArray(values) ? values : (values ? [values] : []);
            instance.setValue(normalizedValues, true);
        }

        return instance;
    }

    function setMaxItems(selectId, maxItems) {
        const instance = instances.get(selectId);
        if (!instance) {
            return;
        }

        const select = document.getElementById(selectId);
        const parsed = resolvePositiveInt(maxItems);
        instance.settings.maxItems = parsed ?? (select?.multiple ? null : 1);

        if (select && parsed) {
            select.dataset.maxItems = String(parsed);
        }

        if (parsed && Array.isArray(instance.items) && instance.items.length > parsed) {
            instance.setValue(instance.items.slice(0, parsed), true);
        }
    }

    function destroy(selectId) {
        const instance = instances.get(selectId);
        if (!instance) {
            return;
        }

        instance.destroy();
        instances.delete(selectId);
    }

    function reinit(selectId) {
        const select = document.getElementById(selectId);
        if (!select) {
            return null;
        }

        try {
            destroy(selectId);
        } catch (error) {
            console.warn(`Failed to destroy select "${selectId}"`, error);
            instances.delete(selectId);
        }

        return initOne(select, 0);
    }

    function getValue(selectId) {
        const instance = instances.get(selectId);
        if (instance) {
            return instance.getValue();
        }

        const select = document.getElementById(selectId);
        if (!select) {
            return '';
        }

        if (select.multiple) {
            return [...select.selectedOptions].map((option) => option.value);
        }

        return select.value;
    }

    function onChange(selectId, callback) {
        if (typeof callback !== 'function') {
            return;
        }

        changeHandlers.set(selectId, callback);

        const instance = instances.get(selectId);
        if (instance) {
            instance.on('change', callback);
        }
    }

    global.EmsSelect = {
        initAll,
        setValue,
        refresh,
        replaceOptions,
        reinit,
        destroy,
        setMaxItems,
        getValue,
        onChange,
        get(selectId) {
            return instances.get(selectId) || null;
        },
    };
}(window));
