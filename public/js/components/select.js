(function registerEmsSelect(global) {
    const instances = new Map();
    const changeHandlers = new Map();

    function safeTrim(value) {
        return String(value || '')
            .replace(/^[\s\u00A0\u2007\u202F\uFEFF]+/u, '')
            .replace(/[\s\u00A0\u2007\u202F\uFEFF]+$/u, '');
    }

    function getPlaceholder(select) {
        if (select.dataset.placeholder) {
            return safeTrim(select.dataset.placeholder);
        }
        if (select.getAttribute('aria-label')) {
            return safeTrim(select.getAttribute('aria-label'));
        }

        const firstOption = select.options[0];
        if (firstOption && firstOption.disabled && firstOption.value === '') {
            return safeTrim(firstOption.textContent) || (global.EmsSelectConfig?.placeholder || 'Select an option');
        }

        return global.EmsSelectConfig?.placeholder || 'Select an option';
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
        const searchMin = Number(global.EmsSelectConfig?.searchMinOptions) || 8;
        const optionCount = Array.prototype.filter.call(select.options || [], (option) => {
            if (option.disabled && option.value === '') return false;
            return true;
        }).length;
        const forceSearch = select.dataset.forceSearch != null || select.hasAttribute('data-force-search');
        const disableSearch = select.dataset.disableSearch != null
            || select.hasAttribute('data-disable-search')
            || select.dataset.noSearch != null
            || select.hasAttribute('data-no-search');
        const includeSearch = forceSearch || (!disableSearch && optionCount >= searchMin);

        const config = {
            create: false,
            allowEmptyOption: true,
            maxItems: isMultiple ? (maxItems ?? null) : 1,
            maxOptions: 500,
            persist: false,
            hideSelected: isMultiple,
            closeAfterSelect: !isMultiple,
            dropdownParent: 'body',
            plugins: isMultiple
                ? (includeSearch ? ['remove_button', 'dropdown_input'] : ['remove_button'])
                : (includeSearch ? ['dropdown_input'] : []),
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
                this.dropdown.style.setProperty('z-index', '12000', 'important');
                if (includeSearch && typeof this.setTextboxValue === 'function') {
                    this.setTextboxValue('');
                    this.lastQuery = '';
                }
                try {
                    this.refreshOptions(false);
                } catch (_) {
                    /* ignore */
                }
                const self = this;
                const reposition = () => {
                    if (!self.isOpen) return;
                    if (typeof global.EmsFilterDrawer?.positionTomSelectDropdown === 'function') {
                        global.EmsFilterDrawer.positionTomSelectDropdown(self);
                    } else if (typeof global.EmsSearchableSelect?.positionDropdown === 'function') {
                        global.EmsSearchableSelect.positionDropdown(self);
                    }
                };
                // Position after Tom Select finishes its own layout pass.
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(reposition);
                });
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
            onChange(value) {
                if (isMultiple) {
                    return;
                }

                // Only clear focus after a real value is chosen (keep focus when cleared)
                if (!safeTrim(value)) {
                    return;
                }

                window.setTimeout(() => {
                    this.blur();
                }, 0);
            },
        };

        if (!includeSearch) {
            config.plugins = (config.plugins || []).filter((plugin) => plugin !== 'dropdown_input');
            // Keep default Tom Select search; with no textbox it won't filter on open.
            delete config.score;
        }

        return config;
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

        // Replace generic auto-enhanced instance with EmsSelect config
        if (select.tomselect) {
            try {
                select.tomselect.destroy();
            } catch (_) {
                /* ignore */
            }
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

    function destroy(selectId) {
        const select = document.getElementById(selectId);
        const instance = instances.get(selectId);
        if (instance) {
            try {
                instance.destroy();
            } catch (error) {
                console.warn(`Select destroy failed for "${selectId}"`, error);
            }
            instances.delete(selectId);
            return;
        }

        // Orphan Tom Select (e.g. searchable-select auto-init) must be cleared too.
        if (select?.tomselect) {
            try {
                select.tomselect.destroy();
            } catch (error) {
                console.warn(`Orphan Tom Select destroy failed for "${selectId}"`, error);
            }
        }
    }

    /**
     * Safely replace <option> HTML on a select that may already be Tom Select–enhanced.
     * Always destroy first: Tom Select destroy() restores the HTML captured at init time,
     * so setting innerHTML while an instance is live (or destroying after) wipes options.
     */
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
        select.classList.remove('tomselected', 'is-searchable', 'ts-hidden-accessible');

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
