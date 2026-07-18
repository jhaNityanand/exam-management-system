/**
 * Shared real-time slug generation with backend uniqueness resolution.
 *
 * Usage:
 *   EmsSlugField.bind({
 *     module: 'exam',
 *     sourceSelector: '#exam_title',
 *     slugSelector: '#meta-slug',
 *     resolveUrl: '/admin/slug/resolve',
 *     ignoreId: 12,          // optional (edit)
 *     sourceIsHtml: false,   // true for rich-text question body
 *   });
 */
(function (global) {
    'use strict';

    const MAX_LENGTH = 80;

    const stripHtml = (html) => {
        const tmp = document.createElement('div');
        tmp.innerHTML = html || '';
        return (tmp.textContent || tmp.innerText || '').trim();
    };

    const slugify = (text) => {
        let base = String(text || '')
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');

        if (base.length > MAX_LENGTH) {
            base = base.slice(0, MAX_LENGTH).replace(/-+$/g, '');
        }

        return base;
    };

    const getSourceText = (el, isHtml) => {
        if (!el) return '';
        if (isHtml) {
            const editor = global.EmsRichTextEditor?.get?.(el.id);
            const html = editor?.getData?.() || el.value || '';
            return stripHtml(html);
        }
        return String(el.value || '').trim();
    };

    const bind = (config = {}) => {
        const slugInput = typeof config.slugSelector === 'string'
            ? document.querySelector(config.slugSelector)
            : config.slugSelector;
        const sourceEl = typeof config.sourceSelector === 'string'
            ? document.querySelector(config.sourceSelector)
            : config.sourceSelector;

        if (!slugInput) return null;

        const resolveUrl = config.resolveUrl || global.slugResolveUrl;
        const moduleName = config.module;
        const ignoreId = config.ignoreId || null;
        const sourceIsHtml = Boolean(config.sourceIsHtml);
        const statusEl = config.statusSelector
            ? document.querySelector(config.statusSelector)
            : slugInput.parentElement?.querySelector('.ems-slug-status');

        let manual = config.startManual ?? Boolean(slugInput.value?.trim());
        let debounceTimer = null;
        let requestId = 0;

        const setStatus = (text, kind = '') => {
            if (!statusEl) return;
            statusEl.textContent = text || '';
            statusEl.dataset.kind = kind;
        };

        const resolve = async (preferred) => {
            if (!resolveUrl || !moduleName) {
                slugInput.value = slugify(preferred);
                return slugInput.value;
            }

            const currentRequest = ++requestId;
            setStatus('Checking uniqueness…', 'pending');

            const params = new URLSearchParams();
            params.set('module', moduleName);
            params.set('source', preferred);
            if (ignoreId) params.set('ignore_id', String(ignoreId));

            try {
                const res = await fetch(`${resolveUrl}?${params.toString()}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Slug resolve failed');
                const payload = await res.json();
                if (currentRequest !== requestId) return slugInput.value;

                const next = payload.slug || slugify(preferred);
                slugInput.value = next;
                setStatus(next && next !== slugify(preferred) ? 'Adjusted for uniqueness' : 'Available', 'ok');
                return next;
            } catch {
                if (currentRequest !== requestId) return slugInput.value;
                slugInput.value = slugify(preferred);
                setStatus('Could not verify uniqueness', 'warn');
                return slugInput.value;
            }
        };

        const scheduleFromSource = () => {
            if (manual) return;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const source = getSourceText(sourceEl, sourceIsHtml);
                resolve(source);
            }, 350);
        };

        const scheduleFromSlug = () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const value = slugInput.value.trim();
                if (!value) {
                    manual = false;
                    scheduleFromSource();
                    return;
                }
                resolve(value);
            }, 350);
        };

        slugInput.addEventListener('input', () => {
            manual = slugInput.value.trim() !== '';
            slugInput.dataset.manual = manual ? '1' : '0';
            scheduleFromSlug();
        });

        if (sourceEl) {
            sourceEl.addEventListener('input', scheduleFromSource);
            sourceEl.addEventListener('change', scheduleFromSource);
        }

        // Rich-text body updates often fire custom events / CKEditor change.
        if (sourceIsHtml && sourceEl) {
            const bindEditor = () => {
                const editor = global.EmsRichTextEditor?.get?.(sourceEl.id);
                if (!editor?.model?.document) return false;
                editor.model.document.on('change:data', scheduleFromSource);
                return true;
            };
            if (!bindEditor()) {
                document.addEventListener('ems:rich-text-ready', bindEditor, { once: true });
                setTimeout(bindEditor, 800);
            }
        }

        if (!slugInput.value.trim() && getSourceText(sourceEl, sourceIsHtml)) {
            scheduleFromSource();
        }

        return {
            slugify,
            resolve,
            isManual: () => manual,
        };
    };

    global.EmsSlugField = { bind, slugify, stripHtml };
}(window));
