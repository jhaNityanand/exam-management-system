/**
 * Blur Tom Select / native selects after a real value is chosen (single-select),
 * so controls don't keep looking like focused text inputs.
 */
(function (global) {
    function hasSelectedValue(value) {
        if (Array.isArray(value)) {
            return value.some((v) => v !== '' && v !== null && v !== undefined);
        }
        return value !== '' && value !== null && value !== undefined;
    }

    function isSingleSelect(instance) {
        const maxItems = instance?.settings?.maxItems;
        if (instance?.input?.multiple) return false;
        if (maxItems == null) return !instance?.input?.multiple;
        return maxItems === 1;
    }

    function blurTomSelect(instance) {
        if (!instance || typeof instance.blur !== 'function') return;
        window.setTimeout(() => {
            try {
                instance.blur();
            } catch (_) {
                /* ignore */
            }
        }, 0);
    }

    /**
     * @param {object} instance TomSelect instance
     * @param {{ onlyWhenSelected?: boolean }} [options]
     */
    function attachBlurOnChange(instance, options = {}) {
        if (!instance || typeof instance.on !== 'function') return instance;

        const onlyWhenSelected = options.onlyWhenSelected !== false;

        instance.on('change', (value) => {
            if (!isSingleSelect(instance)) return;
            if (onlyWhenSelected && !hasSelectedValue(value)) return;
            if (typeof instance.close === 'function') instance.close();
            blurTomSelect(instance);
        });

        return instance;
    }

    function blurNativeSelectsOnChange(root = document, selector = 'select:not([multiple])') {
        root.querySelectorAll(selector).forEach((select) => {
            if (select.dataset.blurOnChangeBound === '1') return;
            select.dataset.blurOnChangeBound = '1';
            select.addEventListener('change', () => {
                if (!hasSelectedValue(select.value)) return;
                select.blur();
            });
        });
    }

    global.EmsTomSelectBlur = {
        attach: attachBlurOnChange,
        blurNativeSelects: blurNativeSelectsOnChange,
        hasSelectedValue,
    };
}(window));
