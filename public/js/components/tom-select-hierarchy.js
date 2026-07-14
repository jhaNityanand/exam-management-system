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

        const instance = new global.TomSelect(select, createConfig(select, extra));
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
