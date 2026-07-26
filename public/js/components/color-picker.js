/**
 * Shared hex color picker: syncs text input with native color swatch.
 * Marks: [data-ems-color-picker]
 */
(function (global) {
    'use strict';

    function normalizeHex(value, fallback) {
        const raw = String(value || '').trim();
        if (/^#[0-9a-fA-F]{6}$/.test(raw)) return raw.toLowerCase();
        if (/^[0-9a-fA-F]{6}$/.test(raw)) return ('#' + raw).toLowerCase();
        if (/^#[0-9a-fA-F]{3}$/.test(raw)) {
            return ('#' + raw[1] + raw[1] + raw[2] + raw[2] + raw[3] + raw[3]).toLowerCase();
        }
        return fallback || '#0f766e';
    }

    function mountPicker(root) {
        if (!root || root._emsColorMounted) return;
        const text = root.querySelector('[data-ems-color-input]');
        const swatch = root.querySelector('[data-ems-color-swatch]');
        if (!text || !swatch) return;

        const syncFromText = () => {
            const hex = normalizeHex(text.value, swatch.value || '#0f766e');
            swatch.value = hex;
            if (/^#?[0-9a-fA-F]{3,6}$/.test(String(text.value || '').trim()) && text.value !== hex) {
                // Keep user typing freely; only update swatch.
            }
        };

        const syncFromSwatch = () => {
            text.value = swatch.value.toLowerCase();
            text.dispatchEvent(new Event('input', { bubbles: true }));
            text.dispatchEvent(new Event('change', { bubbles: true }));
        };

        text.addEventListener('input', syncFromText);
        text.addEventListener('change', syncFromText);
        text.addEventListener('blur', () => {
            if (!String(text.value || '').trim()) return;
            text.value = normalizeHex(text.value, text.value);
            syncFromText();
        });
        swatch.addEventListener('input', syncFromSwatch);
        swatch.addEventListener('change', syncFromSwatch);

        syncFromText();
        root._emsColorMounted = true;
    }

    function initAll(root) {
        const scope = root || document;
        scope.querySelectorAll('[data-ems-color-picker]').forEach(mountPicker);
    }

    global.EmsColorPicker = { initAll, normalizeHex };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initAll());
    } else {
        initAll();
    }
})(window);
