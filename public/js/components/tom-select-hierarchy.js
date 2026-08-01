/**
 * Tom Select helpers for hierarchical options (&nbsp; / depth indent).
 * Dropdown options keep visual indent; the selected control value does not.
 */
(function (global) {
    function stripHierarchyPrefix(text) {
        return String(text || '')
            .replace(/^[\s\u00A0\u2007\u202F\uFEFF]+/u, '')
            .replace(/[\s\u00A0\u2007\u202F\uFEFF]+$/u, '');
    }

    function readCleanLabel(select, value, fallbackText) {
        if (select && value != null && value !== '') {
            try {
                const option = select.querySelector(`option[value="${CSS.escape(String(value))}"]`);
                if (option) {
                    const named = option.getAttribute('data-category-name') || option.dataset?.categoryName;
                    if (named) {
                        return stripHierarchyPrefix(named);
                    }
                    return stripHierarchyPrefix(option.textContent || '');
                }
            } catch (_) {
                /* ignore */
            }
        }
        return stripHierarchyPrefix(fallbackText);
    }

    function readLevel(select, value) {
        if (!select || value == null || value === '') {
            return 0;
        }
        try {
            const option = select.querySelector(`option[value="${CSS.escape(String(value))}"]`);
            const level = Number.parseInt(String(option?.dataset?.level ?? '0'), 10);
            return Number.isFinite(level) && level >= 0 ? level : 0;
        } catch (_) {
            return 0;
        }
    }

    /**
     * Config fragment for Tom Select / EmsSelect hierarchical category dropdowns.
     * @param {HTMLSelectElement} [select]
     * @param {object} [extra]
     */
    function createConfig(select, extra = {}) {
        const render = {
            ...(extra.render || {}),
            item(data, escape) {
                const label = readCleanLabel(select, data.value, data.text);
                return `<div class="ems-item-row ems-item-row--selected">${escape(label)}</div>`;
            },
            option(data, escape) {
                const level = readLevel(select, data.value);
                const label = readCleanLabel(select, data.value, data.text);
                return `
                    <div class="ems-category-option" data-level="${level}">
                        <span class="ems-category-label">${escape(label)}</span>
                    </div>
                `;
            },
        };

        return {
            create: false,
            closeAfterSelect: true,
            ...extra,
            render,
        };
    }

    /**
     * Create a TomSelect instance with hierarchy-aware selected-label rendering.
     */
    function create(selectOrSelector, extra = {}) {
        if (typeof global.TomSelect === 'undefined') {
            return null;
        }

        const select = typeof selectOrSelector === 'string'
            ? document.querySelector(selectOrSelector)
            : selectOrSelector;

        if (!(select instanceof HTMLSelectElement)) {
            return null;
        }

        // Prefer specialized hierarchy instance over a generic auto-init
        if (select.tomselect) {
            try {
                select.tomselect.destroy();
            } catch (_) {
                /* ignore */
            }
        }

        const userOnInitialize = extra.onInitialize;
        const userOnDropdownOpen = extra.onDropdownOpen;
        const userOnDropdownClose = extra.onDropdownClose;

        const config = createConfig(select, {
            dropdownParent: 'body',
            ...extra,
            plugins: ['dropdown_input', ...((extra && extra.plugins) || [])],
            onInitialize() {
                this.wrapper.classList.add('ems-select-wrapper');
                this.wrapper.classList.toggle('is-multiple', !!select.multiple);
                this.wrapper.classList.remove('panel-input');
                this.dropdown.classList.add('ems-select-dropdown');
                if (typeof userOnInitialize === 'function') {
                    userOnInitialize.call(this);
                }
            },
            onDropdownOpen() {
                this.dropdown.classList.add('is-open');
                if (typeof global.EmsFilterDrawer?.positionTomSelectDropdown === 'function') {
                    global.EmsFilterDrawer.positionTomSelectDropdown(this);
                } else if (typeof global.EmsSearchableSelect?.positionDropdown === 'function') {
                    global.EmsSearchableSelect.positionDropdown(this);
                } else {
                    // Lightweight flip when helpers are not on the page
                    const controlRect = this.control.getBoundingClientRect();
                    const viewportH = window.innerHeight || document.documentElement.clientHeight;
                    const spaceBelow = Math.max(0, viewportH - controlRect.bottom - 8);
                    const spaceAbove = Math.max(0, controlRect.top - 8);
                    const openUp = spaceBelow < 260 && spaceAbove > spaceBelow;
                    const content = this.dropdown_content
                        || this.dropdown.querySelector('.ts-dropdown-content');
                    this.dropdown.classList.toggle('ts-dropdown--up', openUp);
                    this.dropdown.style.overflow = 'hidden';
                    if (openUp) {
                        const height = Math.min(this.dropdown.offsetHeight || 240, spaceAbove);
                        const chrome = content
                            ? Math.max(0, (this.dropdown.offsetHeight || height) - (content.offsetHeight || 0))
                            : 0;
                        if (content) {
                            content.style.maxHeight = `${Math.max(96, height - chrome)}px`;
                        } else {
                            this.dropdown.style.maxHeight = `${Math.max(120, height)}px`;
                        }
                        this.dropdown.style.top = `${Math.max(8, controlRect.top - height - 6)}px`;
                    }
                }
                if (typeof userOnDropdownOpen === 'function') {
                    userOnDropdownOpen.call(this);
                }
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
                if (typeof userOnDropdownClose === 'function') {
                    userOnDropdownClose.call(this);
                }
            },
        });
        const instance = new global.TomSelect(select, config);
        global.EmsTomSelectBlur?.attach(instance);
        return instance;
    }

    global.EmsTomSelectHierarchy = {
        stripHierarchyPrefix,
        readCleanLabel,
        createConfig,
        create,
    };
}(window));
