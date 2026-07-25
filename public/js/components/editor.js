(function registerEmsRichTextEditor(global) {
    // Document-style editor: all toolbar actions stay visible and wrap
    // across rows based on width. No floating quickbars / overflow menus.
    const DEFAULT_PLUGINS = [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
        'anchor', 'searchreplace', 'visualblocks', 'code',
        'insertdatetime', 'media', 'table',
        'codesample', 'nonbreaking', 'directionality', 'noneditable',
    ];

    // Single shared toolbar everywhere — compact/standard/full all resolve here.
    const SHARED_TOOLBAR = [
        'undo', 'redo', '|',
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'fontfamily', 'fontsize', 'emslinespace', '|',
        'blocks', '|',
        'forecolor', 'backcolor', '|',
        'alignleft', 'aligncenter', 'alignright', 'alignjustify', '|',
        'bullist', 'numlist', 'checklist', 'outdent', 'indent', '|',
        'emsquote', 'codesample', 'hr', '|',
        'link', 'emsimage', 'table', 'emstabledesign', 'emsshapes', 'emsmedia', 'attachment', '|',
        'removeformat', 'emscodeview', 'emsfullscreen',
    ].join(' ');

    const HEADER_TOOLBAR = SHARED_TOOLBAR;

    const PRESET_TOOLBARS = {
        header: SHARED_TOOLBAR,
        compact: SHARED_TOOLBAR,
        full: SHARED_TOOLBAR,
        standard: SHARED_TOOLBAR,
    };

    const FONT_FAMILY_FORMATS = [
        'Inter=Inter,system-ui,sans-serif',
        'Arial=Arial,Helvetica,sans-serif',
        'Calibri=Calibri,Candara,Segoe UI,sans-serif',
        'Helvetica=Helvetica,Arial,sans-serif',
        'Roboto=Roboto,Arial,sans-serif',
        'Open Sans="Open Sans",Arial,sans-serif',
        'Poppins=Poppins,Arial,sans-serif',
        'Verdana=Verdana,Geneva,sans-serif',
        'Tahoma=Tahoma,Geneva,sans-serif',
        'Georgia=Georgia,Times New Roman,serif',
        'Times New Roman="Times New Roman",Times,serif',
        'Courier New="Courier New",Courier,monospace',
        'Consolas=Consolas,Monaco,monospace',
        'Monospace=ui-monospace,SFMono-Regular,Menlo,Consolas,monospace',
    ].join('; ');

    const FONT_SIZE_FORMATS = '10px 11px 12px 13px 14px 15px 16px 18px 20px 22px 24px 28px 32px 36px 48px';
    const LINE_HEIGHT_FORMATS = '1 1.15 1.5 2 2.5 3';
    const CONTENT_PAD_X = '20px';
    const CONTENT_PAD_Y = '16px';

    const LINE_SPACE_OPTIONS = [
        { text: 'Single', value: '1' },
        { text: '1.15', value: '1.15' },
        { text: '1.5', value: '1.5' },
        { text: 'Double', value: '2' },
        { text: '2.5', value: '2.5' },
        { text: 'Triple', value: '3' },
    ];

    const TABLE_DESIGN_OPTIONS = [
        { text: 'Default', value: '' },
        { text: 'Bordered', value: 'ems-table-bordered' },
        { text: 'Striped', value: 'ems-table-striped' },
        { text: 'Minimal', value: 'ems-table-minimal' },
        { text: 'Modern', value: 'ems-table-modern' },
        { text: 'Compact', value: 'ems-table-compact' },
    ];

    const SHAPE_OPTIONS = [
        { text: 'Rectangle', value: 'rectangle' },
        { text: 'Rounded rectangle', value: 'rounded' },
        { text: 'Circle', value: 'circle' },
        { text: 'Ellipse', value: 'ellipse' },
        { text: 'Triangle', value: 'triangle' },
        { text: 'Line', value: 'line' },
        { text: 'Arrow', value: 'arrow' },
        { text: 'Star', value: 'star' },
    ];

    // All legacy mode names map to the single shared header UI.
    const MODE_ALIASES = {
        linear: 'header',
        bubble: 'header',
        classic: 'header',
        full: 'header',
        standard: 'header',
        compact: 'header',
    };

    const registry = new Map();
    let tinymceLoading = null;
    let cssInjected = false;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function notify(type, message) {
        const text = String(message || '').trim();
        if (!text) return;
        if (global.EmsToast && typeof global.EmsToast[type] === 'function') {
            global.EmsToast[type](text);
            return;
        }
        if (global.EmsToast?.show) {
            global.EmsToast.show({ type, message: text });
        }
    }

    function ensureEditorCss() {
        if (cssInjected) return;
        const href = '/css/components/rich-text-editor.css';
        if ([...document.styleSheets].some((sheet) => String(sheet.href || '').includes('rich-text-editor.css'))) {
            cssInjected = true;
            return;
        }
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href + '?v=18';
        document.head.appendChild(link);
        cssInjected = true;
    }

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const existing = document.querySelector(`script[src="${src}"]`);
            if (existing) {
                if (existing.dataset.loaded === '1' || global.tinymce) {
                    resolve();
                    return;
                }
                existing.addEventListener('load', () => resolve(), { once: true });
                existing.addEventListener('error', reject, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.addEventListener('load', () => {
                script.dataset.loaded = '1';
                resolve();
            }, { once: true });
            script.addEventListener('error', reject, { once: true });
            document.head.appendChild(script);
        });
    }

    async function ensureTinyMce(cdnBase) {
        if (global.tinymce) {
            return global.tinymce;
        }

        if (!tinymceLoading) {
            const base = (cdnBase || 'https://cdn.jsdelivr.net/npm/tinymce@7.6.1').replace(/\/$/, '');
            tinymceLoading = loadScript(`${base}/tinymce.min.js`).then(() => global.tinymce);
        }

        return tinymceLoading;
    }

    function showProgress(wrapper, percent, label) {
        const progress = wrapper?.querySelector('[data-editor-progress]');
        const bar = wrapper?.querySelector('[data-editor-progress-bar]');
        const text = wrapper?.querySelector('[data-editor-progress-label]');
        if (!progress || !bar) return;

        progress.hidden = false;
        progress.setAttribute('aria-hidden', 'false');
        bar.style.transform = `scaleX(${Math.max(0.05, Math.min(1, percent / 100))})`;
        if (text && label) text.textContent = label;
    }

    function hideProgress(wrapper) {
        const progress = wrapper?.querySelector('[data-editor-progress]');
        const bar = wrapper?.querySelector('[data-editor-progress-bar]');
        if (progress) {
            progress.hidden = true;
            progress.setAttribute('aria-hidden', 'true');
        }
        if (bar) bar.style.transform = 'scaleX(0.05)';
    }

    function maxKbForKind(wrapper, kind) {
        const attr = kind === 'image'
            ? 'data-editor-max-image-kb'
            : (kind === 'video' ? 'data-editor-max-video-kb' : 'data-editor-max-file-kb');
        const fallback = kind === 'image' ? 2048 : (kind === 'video' ? 20480 : 10240);
        const value = Number.parseInt(wrapper?.getAttribute(attr) || String(fallback), 10);
        return Number.isFinite(value) && value > 0 ? value : fallback;
    }

    function formatMb(kb) {
        return (Math.round((kb / 1024) * 10) / 10).toFixed(kb >= 1024 ? 1 : 0);
    }

    function parseUploadError(xhr, payload) {
        if (xhr.status === 413) {
            return 'Image is too large for the server. Please use a file under 2 MB.';
        }

        const raw = String(xhr.responseText || '');
        if (/POST data is too large/i.test(raw) || /post_max_size/i.test(raw)) {
            return 'Upload exceeds the server size limit. Please use a smaller image (under 2 MB).';
        }

        if (payload?.message) return payload.message;
        if (payload?.errors?.file?.[0]) return payload.errors.file[0];
        if (payload?.errors && typeof payload.errors === 'object') {
            const first = Object.values(payload.errors).flat()[0];
            if (first) return String(first);
        }

        return `Upload failed (${xhr.status || 'network'}).`;
    }

    function compressImageFile(file, maxEdge = 1600, quality = 0.82) {
        return new Promise((resolve) => {
            if (!file.type.startsWith('image/') || file.type === 'image/gif' || file.type === 'image/svg+xml') {
                resolve(file);
                return;
            }

            const objectUrl = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                URL.revokeObjectURL(objectUrl);
                const scale = Math.min(1, maxEdge / Math.max(img.width, img.height));
                const width = Math.max(1, Math.round(img.width * scale));
                const height = Math.max(1, Math.round(img.height * scale));

                if (scale >= 1 && file.size <= 1.5 * 1024 * 1024) {
                    resolve(file);
                    return;
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    resolve(file);
                    return;
                }
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    if (!blob) {
                        resolve(file);
                        return;
                    }
                    const name = (file.name || 'image').replace(/\.\w+$/, '') + '.jpg';
                    resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', quality);
            };
            img.onerror = () => {
                URL.revokeObjectURL(objectUrl);
                resolve(file);
            };
            img.src = objectUrl;
        });
    }

    async function prepareUploadFile(file, kind, wrapper) {
        const maxKb = maxKbForKind(wrapper, kind);
        let prepared = file;

        if (kind === 'image') {
            prepared = await compressImageFile(file, 1600, 0.82);
            if (prepared.size > maxKb * 1024) {
                prepared = await compressImageFile(prepared, 1280, 0.7);
            }
            if (prepared.size > maxKb * 1024) {
                prepared = await compressImageFile(prepared, 1024, 0.6);
            }
        }

        if (prepared.size > maxKb * 1024) {
            throw new Error(`File is too large. Maximum allowed is ${formatMb(maxKb)} MB.`);
        }

        return prepared;
    }

    function uploadFile({ file, original, kind, uploadUrl, wrapper, onProgress, filename, displayName }) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file, filename || file.name || 'upload.bin');
            formData.append('kind', kind || 'file');
            formData.append('module', wrapper?.getAttribute('data-editor-module') || 'editor');
            formData.append('source', 'editor');
            if (displayName) {
                formData.append('display_name', displayName);
            }
            if (original instanceof File) {
                formData.append('original', original, original.name || 'original.bin');
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (event) => {
                if (!event.lengthComputable) return;
                const pct = Math.round((event.loaded / event.total) * 100);
                if (typeof onProgress === 'function') onProgress(pct);
                showProgress(wrapper, pct, `Uploading ${pct}%`);
            };

            xhr.onload = () => {
                let payload = null;
                try {
                    payload = JSON.parse(xhr.responseText || '{}');
                } catch {
                    payload = null;
                }

                if (xhr.status >= 200 && xhr.status < 300 && payload?.location) {
                    hideProgress(wrapper);
                    resolve(payload);
                    return;
                }

                hideProgress(wrapper);
                reject(new Error(parseUploadError(xhr, payload)));
            };

            xhr.onerror = () => {
                hideProgress(wrapper);
                reject(new Error('Network error while uploading.'));
            };

            xhr.onabort = () => {
                hideProgress(wrapper);
                reject(new Error('Upload cancelled.'));
            };

            showProgress(wrapper, 5, 'Uploading…');
            xhr.send(formData);
        });
    }

    function resolveMediaUrl(url) {
        if (!url) return '';
        const raw = String(url).trim();
        try {
            if (/^https?:\/\//i.test(raw)) {
                const parsed = new URL(raw);
                if (parsed.pathname.includes('/storage/')) {
                    return window.location.origin + parsed.pathname + parsed.search;
                }
                return raw;
            }
            if (raw.startsWith('/')) return window.location.origin + raw;
            return window.location.origin + '/storage/' + raw.replace(/^storage\//, '');
        } catch {
            return raw;
        }
    }

    function imageHtml(payload, name) {
        const src = resolveMediaUrl(payload.location || payload.url);
        const alt = (name || payload.name || 'Image').replace(/"/g, '&quot;');
        const width = payload.width || payload.adjusted?.width;
        const height = payload.height || payload.adjusted?.height;
        // Cap display size so same-line text can sit vertically centered with the image.
        let dims = '';
        if (width && height) {
            const maxEdge = 280;
            const scale = Math.min(1, maxEdge / Math.max(Number(width), Number(height)));
            const w = Math.max(1, Math.round(Number(width) * scale));
            const h = Math.max(1, Math.round(Number(height) * scale));
            dims = ` width="${w}" height="${h}"`;
        }
        return `<img class="ems-img-inline" src="${src}" alt="${alt}"${dims} style="vertical-align:middle;display:inline;margin:0 0.4em;" />`;
    }

    function escapeHtmlText(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function plainTextToShapeHtml(text) {
        const normalized = String(text ?? '').replace(/\r\n|\r/g, '\n');
        if (!normalized.trim()) return '&nbsp;';
        return escapeHtmlText(normalized).replace(/\n/g, '<br>');
    }

    function shapeTextToPlain(el) {
        if (!el) return '';
        const clone = el.cloneNode(true);
        clone.querySelectorAll('br').forEach((br) => {
            br.replaceWith(document.createTextNode('\n'));
        });
        return String(clone.textContent || '')
            .replace(/\u00a0/g, ' ')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    function toHexColor(value, fallback = '#000000') {
        const raw = String(value || '').trim();
        if (!raw) return fallback;

        if (/^#[0-9a-f]{3}$/i.test(raw)) {
            return `#${raw[1]}${raw[1]}${raw[2]}${raw[2]}${raw[3]}${raw[3]}`.toUpperCase();
        }
        if (/^#[0-9a-f]{6}$/i.test(raw)) {
            return raw.toUpperCase();
        }
        if (/^#[0-9a-f]{8}$/i.test(raw)) {
            return raw.slice(0, 7).toUpperCase();
        }

        const rgb = raw.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
        if (rgb) {
            const hex = [rgb[1], rgb[2], rgb[3]]
                .map((part) => Number(part).toString(16).padStart(2, '0'))
                .join('');
            return `#${hex}`.toUpperCase();
        }

        try {
            const canvas = document.createElement('canvas');
            canvas.width = 1;
            canvas.height = 1;
            const ctx = canvas.getContext('2d');
            if (ctx) {
                ctx.fillStyle = '#000000';
                ctx.fillStyle = raw;
                const computed = String(ctx.fillStyle || '');
                if (/^#[0-9a-f]{6}$/i.test(computed)) return computed.toUpperCase();
                const computedRgb = computed.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
                if (computedRgb) {
                    return `#${[computedRgb[1], computedRgb[2], computedRgb[3]]
                        .map((part) => Number(part).toString(16).padStart(2, '0'))
                        .join('')}`.toUpperCase();
                }
            }
        } catch {
            // ignore
        }

        return fallback;
    }

    function shapeAlignStyles(align) {
        const safe = ['left', 'center', 'right', 'justify'].includes(align) ? align : 'center';
        const justify = safe === 'left'
            ? 'flex-start'
            : safe === 'right'
                ? 'flex-end'
                : safe === 'justify'
                    ? 'stretch'
                    : 'center';
        return {
            align: safe,
            textAlign: safe,
            justifyContent: justify,
        };
    }

    function parseShapeSize(value) {
        const raw = String(value || '').trim();
        if (!raw) return '';
        if (/^\d+(\.\d+)?px$/i.test(raw)) return raw;
        if (/^\d+(\.\d+)?$/i.test(raw)) return `${raw}px`;
        return '';
    }

    function getShapeTextTarget(box) {
        return box?.querySelector?.('.ems-shape-box__text') || box || null;
    }

    function shapeHtml(kind, options = {}) {
        const textHtml = options.textHtml != null
            ? String(options.textHtml)
            : plainTextToShapeHtml(options.text ?? 'Text');
        const fill = toHexColor(options.fill, '#0D9488');
        const border = toHexColor(options.border, '#0F766E');
        const borderWidth = String(options.borderWidth || '2').replace(/[^\d.]/g, '') || '2';
        const textColor = toHexColor(options.textColor, '#FFFFFF');
        const alignInfo = shapeAlignStyles(options.align || 'center');
        const lineHeight = options.lineHeight || '';
        const width = parseShapeSize(options.width);
        const height = parseShapeSize(options.height);
        const safeKind = SHAPE_OPTIONS.some((o) => o.value === kind) ? kind : 'rectangle';
        const sizeStyles = `${width ? `width:${width};` : ''}${height ? `height:${height};` : ''}`
            + `${width ? `min-width:${width};` : ''}${height ? `min-height:${height};` : ''}`;
        const textLineHeight = lineHeight ? `line-height:${lineHeight};` : '';

        return (
            `<span class="mceNonEditable ems-shape-box ems-shape-box--${safeKind}" contenteditable="false" data-ems-shape="${safeKind}" `
            + `data-ems-fill="${fill}" data-ems-border="${border}" data-ems-border-width="${borderWidth}" `
            + `data-ems-text-color="${textColor}" data-ems-align="${alignInfo.align}"`
            + `${lineHeight ? ` data-ems-line-height="${lineHeight}"` : ''}`
            + `${width ? ` data-ems-width="${width}"` : ''}${height ? ` data-ems-height="${height}"` : ''} `
            + `style="background-color:${fill};border:${borderWidth}px solid ${border};color:${textColor};`
            + `text-align:${alignInfo.textAlign};justify-content:${alignInfo.justifyContent};${sizeStyles}">`
            + `<span class="mceEditable ems-shape-box__text" contenteditable="true" `
            + `style="text-align:${alignInfo.textAlign};color:${textColor};${textLineHeight}white-space:pre-wrap;">`
            + `${textHtml || '&nbsp;'}</span>`
            + `</span>&nbsp;`
        );
    }

    function readShapeOptions(node) {
        if (!node) {
            return {
                text: 'Text',
                fill: '#0D9488',
                border: '#0F766E',
                borderWidth: '2',
                textColor: '#FFFFFF',
                align: 'center',
                lineHeight: '',
                width: '',
                height: '',
            };
        }
        const textEl = getShapeTextTarget(node);
        const computedFill = node.style?.backgroundColor || '';
        const computedText = textEl?.style?.color || node.style?.color || '';
        return {
            text: shapeTextToPlain(textEl) || 'Text',
            fill: toHexColor(node.getAttribute('data-ems-fill') || computedFill, '#0D9488'),
            border: toHexColor(node.getAttribute('data-ems-border') || '#0F766E', '#0F766E'),
            borderWidth: node.getAttribute('data-ems-border-width') || '2',
            textColor: toHexColor(node.getAttribute('data-ems-text-color') || computedText, '#FFFFFF'),
            align: node.getAttribute('data-ems-align') || shapeAlignStyles(node.style?.textAlign || 'center').align,
            lineHeight: node.getAttribute('data-ems-line-height')
                || textEl?.style?.lineHeight
                || '',
            width: node.getAttribute('data-ems-width') || parseShapeSize(node.style?.width) || '',
            height: node.getAttribute('data-ems-height') || parseShapeSize(node.style?.height) || '',
        };
    }

    function applyShapeBoxStyles(editor, box, options = {}) {
        if (!box) return;
        const textEl = getShapeTextTarget(box);
        const fill = toHexColor(options.fill ?? box.getAttribute('data-ems-fill'), '#0D9488');
        const border = toHexColor(options.border ?? box.getAttribute('data-ems-border'), '#0F766E');
        const borderWidth = String(options.borderWidth ?? box.getAttribute('data-ems-border-width') ?? '2').replace(/[^\d.]/g, '') || '2';
        const textColor = toHexColor(options.textColor ?? box.getAttribute('data-ems-text-color'), '#FFFFFF');
        const alignInfo = shapeAlignStyles(options.align ?? box.getAttribute('data-ems-align') ?? 'center');
        const lineHeight = options.lineHeight != null
            ? options.lineHeight
            : (box.getAttribute('data-ems-line-height') || '');
        const width = parseShapeSize(options.width != null ? options.width : box.getAttribute('data-ems-width'));
        const height = parseShapeSize(options.height != null ? options.height : box.getAttribute('data-ems-height'));

        box.setAttribute('data-ems-fill', fill);
        box.setAttribute('data-ems-border', border);
        box.setAttribute('data-ems-border-width', borderWidth);
        box.setAttribute('data-ems-text-color', textColor);
        box.setAttribute('data-ems-align', alignInfo.align);
        if (lineHeight) box.setAttribute('data-ems-line-height', lineHeight);
        else box.removeAttribute('data-ems-line-height');
        if (width) box.setAttribute('data-ems-width', width);
        else box.removeAttribute('data-ems-width');
        if (height) box.setAttribute('data-ems-height', height);
        else box.removeAttribute('data-ems-height');

        const styles = {
            'background-color': fill,
            border: `${borderWidth}px solid ${border}`,
            color: textColor,
            'text-align': alignInfo.textAlign,
            'justify-content': alignInfo.justifyContent,
        };
        if (width) {
            styles.width = width;
            styles['min-width'] = width;
        } else if (options.width != null) {
            styles.width = '';
            styles['min-width'] = '';
        }
        if (height) {
            styles.height = height;
            styles['min-height'] = height;
        } else if (options.height != null) {
            styles.height = '';
            styles['min-height'] = '';
        }
        editor.dom.setStyles(box, styles);

        if (textEl) {
            const textStyles = {
                'text-align': alignInfo.textAlign,
                color: textColor,
                'white-space': 'pre-wrap',
            };
            if (lineHeight) textStyles['line-height'] = lineHeight;
            editor.dom.setStyles(textEl, textStyles);
        }
    }

    function applyShapeAlign(editor, box, align) {
        applyShapeBoxStyles(editor, box, { align });
        editor.nodeChanged();
    }

    function applyShapeTextColor(editor, box, color) {
        applyShapeBoxStyles(editor, box, { textColor: color });
        editor.nodeChanged();
    }

    function applyShapeFillColor(editor, box, color) {
        applyShapeBoxStyles(editor, box, { fill: color });
        editor.nodeChanged();
    }

    function applyShapeLineHeight(editor, box, value) {
        applyShapeBoxStyles(editor, box, { lineHeight: value });
        editor.nodeChanged();
    }

    function applyShapeTextStyle(editor, box, styles = {}) {
        const textEl = getShapeTextTarget(box);
        if (!textEl) return;
        editor.dom.setStyles(textEl, styles);
        editor.nodeChanged();
    }

    function updateShapeNode(editor, box, kind, data) {
        if (!box) return;
        const safeKind = SHAPE_OPTIONS.some((o) => o.value === kind) ? kind : (box.getAttribute('data-ems-shape') || 'rectangle');
        box.setAttribute('data-ems-shape', safeKind);
        box.className = `mceNonEditable ems-shape-box ems-shape-box--${safeKind}`;
        applyShapeBoxStyles(editor, box, data);
        const textEl = getShapeTextTarget(box);
        if (textEl) {
            textEl.innerHTML = plainTextToShapeHtml(data.text || 'Text');
        }
        editor.nodeChanged();
    }

    function openShapeDialog(editor, kind, existingNode = null) {
        const initial = readShapeOptions(existingNode);
        const titleKind = SHAPE_OPTIONS.find((o) => o.value === kind)?.text || 'Shape';

        editor.windowManager.open({
            title: existingNode ? `Edit ${titleKind}` : `Insert ${titleKind}`,
            size: 'medium',
            body: {
                type: 'panel',
                items: [
                    { type: 'textarea', name: 'text', label: 'Text on shape (Enter for new line)', maximized: true },
                    {
                        type: 'selectbox',
                        name: 'align',
                        label: 'Text alignment',
                        items: [
                            { text: 'Left', value: 'left' },
                            { text: 'Center', value: 'center' },
                            { text: 'Right', value: 'right' },
                            { text: 'Justify', value: 'justify' },
                        ],
                    },
                    {
                        type: 'grid',
                        columns: 2,
                        items: [
                            { type: 'input', name: 'width', label: 'Width (px, optional)' },
                            { type: 'input', name: 'height', label: 'Height (px, optional)' },
                        ],
                    },
                    { type: 'colorinput', name: 'fill', label: 'Fill color' },
                    { type: 'colorinput', name: 'border', label: 'Border color' },
                    { type: 'input', name: 'borderWidth', label: 'Border width (px)' },
                    { type: 'colorinput', name: 'textColor', label: 'Text color' },
                ],
            },
            initialData: {
                text: initial.text,
                align: initial.align || 'center',
                width: String(initial.width || '').replace(/px$/i, ''),
                height: String(initial.height || '').replace(/px$/i, ''),
                fill: initial.fill,
                border: initial.border,
                borderWidth: String(initial.borderWidth || '2'),
                textColor: initial.textColor,
            },
            buttons: [
                { type: 'cancel', text: 'Cancel' },
                { type: 'submit', text: existingNode ? 'Update' : 'Insert', primary: true },
            ],
            onSubmit: (api) => {
                const data = api.getData();
                const payload = {
                    text: data.text || 'Text',
                    align: data.align || 'center',
                    width: data.width,
                    height: data.height,
                    fill: toHexColor(data.fill, '#0D9488'),
                    border: toHexColor(data.border, '#0F766E'),
                    borderWidth: data.borderWidth || '2',
                    textColor: toHexColor(data.textColor, '#FFFFFF'),
                    lineHeight: initial.lineHeight || '',
                };
                if (existingNode) {
                    updateShapeNode(editor, existingNode, kind, payload);
                } else {
                    editor.insertContent(shapeHtml(kind, payload));
                }
                api.close();
            },
        });
    }

    function openQuoteBgDialog(editor) {
        const quote = editor.dom.getParent(editor.selection.getNode(), 'blockquote');
        if (!quote) {
            editor.execCommand('mceBlockQuote');
        }
        const target = editor.dom.getParent(editor.selection.getNode(), 'blockquote');
        if (!target) {
            notify('warning', 'Place the cursor inside a quote first.');
            return;
        }

        const current = toHexColor(
            target.getAttribute('data-ems-quote-bg') || target.style.backgroundColor || '#F8FAFC',
            '#F8FAFC'
        );

        editor.windowManager.open({
            title: 'Quote background color',
            body: {
                type: 'panel',
                items: [
                    { type: 'colorinput', name: 'background', label: 'Background color' },
                    {
                        type: 'selectbox',
                        name: 'preset',
                        label: 'Quick presets',
                        items: [
                            { text: 'Keep custom color', value: '' },
                            { text: 'Soft gray', value: '#F8FAFC' },
                            { text: 'Teal wash', value: '#CCFBF1' },
                            { text: 'Sky wash', value: '#E0F2FE' },
                            { text: 'Amber wash', value: '#FEF3C7' },
                            { text: 'Rose wash', value: '#FFE4E6' },
                            { text: 'Violet wash', value: '#EDE9FE' },
                            { text: 'Clear background', value: 'clear' },
                        ],
                    },
                ],
            },
            initialData: {
                background: current,
                preset: '',
            },
            buttons: [
                { type: 'cancel', text: 'Cancel' },
                { type: 'submit', text: 'Apply', primary: true },
            ],
            onSubmit: (api) => {
                const data = api.getData();
                let color = data.preset && data.preset !== 'clear' ? data.preset : data.background;
                if (data.preset === 'clear') {
                    target.removeAttribute('data-ems-quote-bg');
                    editor.dom.setStyle(target, 'background-color', '');
                } else {
                    color = toHexColor(color, current);
                    target.setAttribute('data-ems-quote-bg', color);
                    editor.dom.setStyle(target, 'background-color', color);
                }
                editor.nodeChanged();
                api.close();
            },
        });
    }

    function applyLineSpacing(editor, value) {
        const shape = editor.dom.getParent(editor.selection.getNode(), '.ems-shape-box');
        if (shape) {
            applyShapeLineHeight(editor, shape, value);
            return;
        }

        try {
            editor.execCommand('LineHeight', false, value);
            return;
        } catch {
            // fallback below
        }
        const blocks = editor.selection.getSelectedBlocks?.() || [];
        if (!blocks.length) {
            const node = editor.selection.getNode();
            const block = editor.dom.getParent(node, 'p,h1,h2,h3,h4,h5,h6,li,td,th,div,blockquote');
            if (block) editor.dom.setStyle(block, 'line-height', value);
            return;
        }
        blocks.forEach((block) => editor.dom.setStyle(block, 'line-height', value));
    }

    function applyParagraphGap(editor, marginBottom) {
        const shape = editor.dom.getParent(editor.selection.getNode(), '.ems-shape-box');
        if (shape) {
            const textEl = getShapeTextTarget(shape);
            if (textEl) {
                if (marginBottom === null) editor.dom.setStyle(textEl, 'margin-bottom', '');
                else editor.dom.setStyle(textEl, 'margin-bottom', marginBottom);
                editor.nodeChanged();
            }
            return;
        }

        const blocks = editor.selection.getSelectedBlocks?.() || [];
        const targets = blocks.length
            ? blocks
            : [editor.dom.getParent(editor.selection.getNode(), 'p,h1,h2,h3,h4,h5,h6,li,div,blockquote')].filter(Boolean);
        targets.forEach((block) => {
            if (marginBottom === null) {
                editor.dom.setStyle(block, 'margin-bottom', '');
            } else {
                editor.dom.setStyle(block, 'margin-bottom', marginBottom);
            }
        });
    }

    function applyTableDesign(editor, className) {
        const table = editor.dom.getParent(editor.selection.getNode(), 'table');
        if (!table) {
            notify('warning', 'Place the cursor inside a table first.');
            return;
        }
        TABLE_DESIGN_OPTIONS.forEach((opt) => {
            if (opt.value) editor.dom.removeClass(table, opt.value);
        });
        if (className) editor.dom.addClass(table, className);
        editor.nodeChanged();
    }

    function exitCodeView(editor, wrapper, api) {
        const surface = wrapper?.querySelector('.ems-rich-editor__surface');
        const panelWrap = surface?.querySelector('.ems-rich-editor__codepanel');
        const textarea = panelWrap?.querySelector('.ems-rich-editor__codeview');
        const container = editor.getContainer?.();

        if (textarea) {
            try {
                editor.setContent(textarea.value);
            } catch {
                // ignore invalid HTML restore issues
            }
        }
        if (panelWrap) panelWrap.hidden = true;
        if (container) {
            container.style.display = '';
            const editWrap = container.querySelector('.tox-sidebar-wrap');
            if (editWrap) editWrap.style.display = '';
        }
        wrapper?.classList.remove('is-codeview');
        api?.setActive(false);
        editor.focus();
    }

    function toggleCodeView(editor, wrapper, api) {
        const surface = wrapper?.querySelector('.ems-rich-editor__surface');
        if (!surface) return;

        const container = editor.getContainer?.();
        const isActive = wrapper.classList.contains('is-codeview');

        if (isActive) {
            exitCodeView(editor, wrapper, api);
            return;
        }

        let panelWrap = surface.querySelector('.ems-rich-editor__codepanel');
        if (!panelWrap) {
            panelWrap = document.createElement('div');
            panelWrap.className = 'ems-rich-editor__codepanel';
            panelWrap.innerHTML = `
                <div class="ems-rich-editor__codepanel-bar">
                    <div class="ems-rich-editor__codepanel-title">
                        <strong>HTML source</strong>
                        <span>Edit raw markup, then return to the visual editor.</span>
                    </div>
                    <button type="button" class="ems-rich-editor__codepanel-back" data-ems-code-back>
                        ← Back to editor
                    </button>
                </div>
                <textarea class="ems-rich-editor__codeview" spellcheck="false" aria-label="HTML source"></textarea>
            `;
            surface.appendChild(panelWrap);
            panelWrap.querySelector('[data-ems-code-back]')?.addEventListener('click', () => {
                exitCodeView(editor, wrapper, api);
            });
        }

        const textarea = panelWrap.querySelector('.ems-rich-editor__codeview');
        textarea.value = editor.getContent({ format: 'html' });
        panelWrap.hidden = false;

        // Keep TinyMCE toolbar visible; only hide the writing surface.
        if (container) {
            const editWrap = container.querySelector('.tox-sidebar-wrap');
            if (editWrap) {
                editWrap.style.display = 'none';
            } else {
                container.style.display = 'none';
            }
        }

        wrapper.classList.add('is-codeview');
        api?.setActive(true);
        textarea.focus();
        textarea.setSelectionRange(0, 0);
    }

    let cropperLoading = null;
    let cropperCssInjected = false;

    function ensureCropperCss() {
        if (cropperCssInjected) return;
        const href = 'https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css';
        if (![...document.styleSheets].some((s) => String(s.href || '').includes('cropper.min.css'))) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            document.head.appendChild(link);
        }
        cropperCssInjected = true;
    }

    async function ensureCropper() {
        if (global.Cropper) return global.Cropper;
        ensureCropperCss();
        if (!cropperLoading) {
            cropperLoading = loadScript('https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js')
                .then(() => global.Cropper);
        }
        return cropperLoading;
    }

    function editorFromWrapper(wrapper) {
        const id = wrapper?.getAttribute('data-editor-input')
            || wrapper?.querySelector?.('textarea')?.id
            || null;
        if (id && global.tinymce?.get) {
            try {
                const ed = global.tinymce.get(id);
                if (ed) return ed;
            } catch {
                // ignore
            }
        }
        return global.tinymce?.activeEditor || null;
    }

    function closeTinyMceDialogs(editor) {
        const ed = editor || global.tinymce?.activeEditor || null;
        try {
            if (ed?.windowManager) {
                if (typeof ed.windowManager.close === 'function') {
                    ed.windowManager.close();
                }
                const windows = ed.windowManager.getWindows?.();
                if (windows?.length) {
                    [...windows].forEach((win) => {
                        try { win.close(); } catch { /* ignore */ }
                    });
                }
            }
        } catch {
            // ignore
        }

        document.querySelectorAll('.tox-dialog-wrap, .tox-dialog-wrap__backdrop').forEach((el) => {
            try { el.remove(); } catch { /* ignore */ }
        });
    }

    function mediaHtml(url, file) {
        const src = resolveMediaUrl(url);
        const type = String(file?.type || '').toLowerCase();
        const name = (file?.name || 'Media').replace(/</g, '');
        if (type.startsWith('audio/')) {
            return `<p><audio controls preload="metadata" src="${src}" style="width:100%;max-width:480px"></audio></p>`;
        }
        if (type.startsWith('video/') || /\.(mp4|webm|ogg|ogv|mov|m4v)(\?|$)/i.test(src)) {
            return `<p><video controls preload="metadata" src="${src}" style="max-width:100%;height:auto"></video></p>`;
        }
        return `<p><a class="ems-attachment-link" href="${src}" target="_blank" rel="noopener">${name.replace(/"/g, '&quot;')}</a></p>`;
    }

    /**
     * Open crop/rotate modal. Resolves { original, adjusted } or null if cancelled.
     * Prefers the shared GalleryImageEditor when available.
     */
    function openImageAdjuster(file, editor = null) {
        return new Promise(async (resolve) => {
            // Never leave TinyMCE dialogs (e.g. Insert/Edit Link) on top of the adjuster.
            closeTinyMceDialogs(editor);

            if (global.GalleryImageEditor?.open) {
                const objectUrl = URL.createObjectURL(file);
                try {
                    const edited = await global.GalleryImageEditor.open({
                        src: objectUrl,
                        name: file.name,
                        root: document,
                        originalFile: file,
                    });
                    if (!edited) {
                        resolve(null);
                        return;
                    }
                    if (edited.__keepOriginal) {
                        resolve({ original: file, adjusted: file });
                        return;
                    }
                    resolve({ original: file, adjusted: edited });
                    return;
                } catch (error) {
                    notify('warning', error.message || 'Falling back to basic image adjuster.');
                } finally {
                    URL.revokeObjectURL(objectUrl);
                }
            }

            const Cropper = await ensureCropper().catch(() => null);
            const objectUrl = URL.createObjectURL(file);

            const overlay = document.createElement('div');
            overlay.className = 'ems-image-adjust';
            overlay.innerHTML = `
                <div class="ems-image-adjust__dialog" role="dialog" aria-modal="true" aria-label="Adjust image">
                    <div class="ems-image-adjust__header">
                        <div>
                            <h3>Adjust image</h3>
                            <p>${(file.name || 'Image').replace(/</g, '')}</p>
                        </div>
                        <button type="button" class="ems-image-adjust__icon" data-adjust-cancel aria-label="Close">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>
                    <div class="ems-image-adjust__stage">
                        <img src="${objectUrl}" alt="Adjust preview" data-adjust-image>
                    </div>
                    <div class="ems-image-adjust__toolbar">
                        <button type="button" data-adjust-action="rotate-left" title="Rotate left">Rot L</button>
                        <button type="button" data-adjust-action="rotate-right" title="Rotate right">Rot R</button>
                        <button type="button" data-adjust-action="zoom-in" title="Zoom in">+</button>
                        <button type="button" data-adjust-action="zoom-out" title="Zoom out">-</button>
                        <button type="button" data-adjust-action="reset" title="Reset">Reset</button>
                        <span class="ems-image-adjust__sep"></span>
                        <button type="button" data-adjust-ratio="" class="is-active">Free</button>
                        <button type="button" data-adjust-ratio="1">1:1</button>
                        <button type="button" data-adjust-ratio="16/9">16:9</button>
                        <button type="button" data-adjust-ratio="4/3">4:3</button>
                    </div>
                    <div class="ems-image-adjust__footer">
                        <button type="button" class="ems-image-adjust__btn" data-adjust-cancel>Cancel</button>
                        <button type="button" class="ems-image-adjust__btn" data-adjust-original>Use original</button>
                        <button type="button" class="ems-image-adjust__btn ems-image-adjust__btn--primary" data-adjust-apply>Apply &amp; insert</button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
            document.body.classList.add('ems-image-adjust-open');

            const img = overlay.querySelector('[data-adjust-image]');
            let cropper = null;

            const cleanup = (result) => {
                try { cropper?.destroy(); } catch { /* ignore */ }
                URL.revokeObjectURL(objectUrl);
                overlay.remove();
                document.body.classList.remove('ems-image-adjust-open');
                resolve(result);
            };

            const finishWithFiles = async (adjustedBlob, usedOriginal) => {
                const baseName = (file.name || 'image').replace(/\.\w+$/, '');
                let adjustedFile = file;
                if (!usedOriginal && adjustedBlob) {
                    adjustedFile = new File([adjustedBlob], `${baseName}.jpg`, {
                        type: 'image/jpeg',
                        lastModified: Date.now(),
                    });
                    adjustedFile = await compressImageFile(adjustedFile, 1600, 0.82);
                } else {
                    adjustedFile = await compressImageFile(file, 1600, 0.82);
                }
                cleanup({ original: file, adjusted: adjustedFile });
            };

            img.addEventListener('load', () => {
                if (!Cropper) return;
                cropper = new Cropper(img, {
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    responsive: true,
                    movable: true,
                    zoomable: true,
                    rotatable: true,
                });
            }, { once: true });

            overlay.addEventListener('click', (event) => {
                if (event.target === overlay || event.target.closest('[data-adjust-cancel]')) {
                    cleanup(null);
                }
            });

            overlay.querySelector('[data-adjust-original]')?.addEventListener('click', () => {
                finishWithFiles(null, true);
            });

            overlay.querySelector('[data-adjust-apply]')?.addEventListener('click', () => {
                if (!cropper) {
                    finishWithFiles(null, true);
                    return;
                }
                const canvas = cropper.getCroppedCanvas({
                    maxWidth: 2000,
                    maxHeight: 2000,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });
                if (!canvas) {
                    finishWithFiles(null, true);
                    return;
                }
                canvas.toBlob((blob) => finishWithFiles(blob, false), 'image/jpeg', 0.9);
            });

            overlay.querySelectorAll('[data-adjust-action]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (!cropper) return;
                    const action = btn.getAttribute('data-adjust-action');
                    if (action === 'rotate-left') cropper.rotate(-90);
                    if (action === 'rotate-right') cropper.rotate(90);
                    if (action === 'zoom-in') cropper.zoom(0.1);
                    if (action === 'zoom-out') cropper.zoom(-0.1);
                    if (action === 'reset') cropper.reset();
                });
            });

            overlay.querySelectorAll('[data-adjust-ratio]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (!cropper) return;
                    overlay.querySelectorAll('[data-adjust-ratio]').forEach((b) => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    const raw = btn.getAttribute('data-adjust-ratio') || '';
                    if (!raw) {
                        cropper.setAspectRatio(NaN);
                        return;
                    }
                    if (raw.includes('/')) {
                        const [a, b] = raw.split('/').map(Number);
                        cropper.setAspectRatio(a / b);
                    } else {
                        cropper.setAspectRatio(Number(raw));
                    }
                });
            });
        });
    }

    async function uploadImagePair({ original, adjusted, uploadUrl, wrapper, onProgress, displayName }) {
        const maxKb = maxKbForKind(wrapper, 'image');
        let prepared = adjusted;
        if (prepared.size > maxKb * 1024) {
            prepared = await compressImageFile(prepared, 1280, 0.7);
        }
        if (prepared.size > maxKb * 1024) {
            prepared = await compressImageFile(prepared, 1024, 0.6);
        }
        if (prepared.size > maxKb * 1024) {
            throw new Error(`File is too large. Maximum allowed is ${formatMb(maxKb)} MB.`);
        }

        return uploadFile({
            file: prepared,
            original,
            kind: 'image',
            uploadUrl,
            wrapper,
            filename: prepared.name || original.name,
            displayName: displayName || original.name,
            onProgress,
        });
    }

    function buildImagesUploadHandler(uploadUrl, wrapper) {
        return (blobInfo, progress) => new Promise((resolve, reject) => {
            const blob = blobInfo.blob();
            const rawName = blobInfo.filename?.() || blob.name || `image-${Date.now()}.png`;
            const asFile = blob instanceof File
                ? blob
                : new File([blob], rawName, { type: blob.type || 'image/png', lastModified: Date.now() });

            (async () => {
                const adjusted = await compressImageFile(asFile, 1600, 0.82);
                const payload = await uploadImagePair({
                    original: asFile,
                    adjusted,
                    uploadUrl,
                    wrapper,
                    displayName: rawName,
                    onProgress: (pct) => {
                        if (typeof progress === 'function') progress(pct);
                    },
                });
                const location = resolveMediaUrl(payload.location);
                if (!location) throw new Error('Upload succeeded but no image URL was returned.');
                resolve(location);
            })().catch((error) => {
                const message = error.message || String(error);
                notify('error', message);
                reject(message);
            }).finally(() => {
                hideProgress(wrapper);
            });
        });
    }

    function buildFilePicker(uploadUrl, wrapper) {
        return (callback, value, meta) => {
            const input = document.createElement('input');
            input.type = 'file';
            const editor = editorFromWrapper(wrapper);

            if (meta.filetype === 'image') {
                input.accept = 'image/png,image/jpeg,image/jpg,image/gif,image/webp';
            } else if (meta.filetype === 'media') {
                input.accept = 'video/mp4,video/webm,video/ogg,audio/*';
            } else {
                input.accept = '*/*';
            }

            input.addEventListener('change', async () => {
                const file = input.files?.[0];
                if (!file) return;

                try {
                    // Image dialog browse only — adjust, upload, fill dialog fields.
                    if (meta.filetype === 'image') {
                        const adjustedPair = await openImageAdjuster(file, editor);
                        if (!adjustedPair) return;
                        const payload = await uploadImagePair({
                            original: adjustedPair.original,
                            adjusted: adjustedPair.adjusted,
                            uploadUrl,
                            wrapper,
                            displayName: file.name,
                        });
                        callback(resolveMediaUrl(payload.location), {
                            title: payload.name || file.name,
                            alt: payload.name || file.name,
                            width: String(payload.width || ''),
                            height: String(payload.height || ''),
                        });
                        notify('success', 'Image uploaded successfully.');
                        return;
                    }

                    // Media dialog browse — upload and insert directly so content always appears.
                    if (meta.filetype === 'media') {
                        const kind = file.type.startsWith('audio/') ? 'file' : 'video';
                        const prepared = await prepareUploadFile(file, kind === 'video' ? 'video' : 'file', wrapper);
                        const payload = await uploadFile({
                            file: prepared,
                            kind: kind === 'video' ? 'video' : 'file',
                            uploadUrl,
                            wrapper,
                            filename: prepared.name || file.name,
                        });
                        const url = resolveMediaUrl(payload.location);
                        closeTinyMceDialogs(editor);
                        if (editor) {
                            editor.insertContent(mediaHtml(url, file));
                            editor.focus();
                        } else {
                            callback(url, { title: payload.name || prepared.name || file.name });
                        }
                        notify('success', 'Media uploaded successfully.');
                        return;
                    }

                    // Link / generic file browse — upload URL only.
                    // Do NOT open the image adjuster here (it stacked under Insert/Edit Link).
                    const kind = file.type.startsWith('image/')
                        ? 'image'
                        : (file.type.startsWith('video/') ? 'video' : 'file');
                    const prepared = await prepareUploadFile(file, kind, wrapper);
                    const payload = await uploadFile({
                        file: prepared,
                        kind,
                        uploadUrl,
                        wrapper,
                        filename: prepared.name || file.name,
                    });
                    callback(resolveMediaUrl(payload.location), {
                        text: payload.name || prepared.name || file.name,
                        title: payload.name || prepared.name || file.name,
                    });
                } catch (error) {
                    notify('error', error.message || 'Upload failed.');
                } finally {
                    hideProgress(wrapper);
                }
            });

            input.click();
        };
    }

    /**
     * Shared "upload an image" pipeline used by the image toolbar button,
     * slash command, attachment picker, and drag/drop: opens the crop /
     * resize / adjust popup first, uploads the adjusted file, then inserts
     * it into the editor. Returns true on success.
     */
    async function insertUploadedImage(editor, wrapper, uploadUrl, file) {
        closeTinyMceDialogs(editor);
        const adjustedPair = await openImageAdjuster(file, editor);
        if (!adjustedPair) return false;
        const payload = await uploadImagePair({
            original: adjustedPair.original,
            adjusted: adjustedPair.adjusted,
            uploadUrl,
            wrapper,
            displayName: file.name,
        });
        closeTinyMceDialogs(editor);
        editor.insertContent(imageHtml(payload, file.name));
        notify('success', 'Image inserted successfully.');
        return true;
    }

    /**
     * Skips TinyMCE's native "Insert/Edit Image" dialog entirely: the OS
     * file picker opens immediately, and as soon as a file is chosen the
     * crop/resize/adjust popup appears before the image is uploaded and
     * inserted.
     */
    function insertImageFlow(editor) {
        closeTinyMceDialogs(editor);
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png,image/jpeg,image/jpg,image/gif,image/webp';
        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;
            const wrapper = editor.getElement()?.closest('[data-ems-rich-editor]');
            const uploadUrl = wrapper?.getAttribute('data-editor-upload-url');
            if (!uploadUrl) return;

            try {
                await insertUploadedImage(editor, wrapper, uploadUrl, file);
            } catch (error) {
                notify('error', error.message || 'Upload failed.');
            } finally {
                hideProgress(wrapper);
            }
        });
        input.click();
    }

    function insertMediaFlow(editor) {
        closeTinyMceDialogs(editor);
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'video/mp4,video/webm,video/ogg,audio/*,.mp4,.webm,.ogg,.mp3,.wav,.m4a';
        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;
            const wrapper = editor.getElement()?.closest('[data-ems-rich-editor]');
            const uploadUrl = wrapper?.getAttribute('data-editor-upload-url');
            if (!uploadUrl) return;

            try {
                const kind = file.type.startsWith('audio/') ? 'file' : 'video';
                const prepared = await prepareUploadFile(file, kind === 'video' ? 'video' : 'file', wrapper);
                const payload = await uploadFile({
                    file: prepared,
                    kind: kind === 'video' ? 'video' : 'file',
                    uploadUrl,
                    wrapper,
                    filename: prepared.name || file.name,
                });
                closeTinyMceDialogs(editor);
                editor.insertContent(mediaHtml(payload.location, file));
                editor.focus();
                notify('success', 'Media inserted successfully.');
            } catch (error) {
                notify('error', error.message || 'Upload failed.');
            } finally {
                hideProgress(wrapper);
            }
        });
        input.click();
    }

    function openAttachmentPicker(editor) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z,video/*';
        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;
            const wrapper = editor.getElement()?.closest('[data-ems-rich-editor]');
            const uploadUrl = wrapper?.getAttribute('data-editor-upload-url');
            if (!uploadUrl) return;

            try {
                if (file.type.startsWith('image/')) {
                    await insertUploadedImage(editor, wrapper, uploadUrl, file);
                    return;
                }

                const kind = file.type.startsWith('video/') ? 'video' : 'file';
                const prepared = await prepareUploadFile(file, kind, wrapper);
                const payload = await uploadFile({
                    file: prepared,
                    kind,
                    uploadUrl,
                    wrapper,
                    filename: prepared.name || file.name,
                });
                const name = payload.name || prepared.name || file.name;
                if (kind === 'video' || file.type.startsWith('audio/')) {
                    editor.insertContent(mediaHtml(payload.location, file));
                } else {
                    editor.insertContent(
                        `<p><a class="ems-attachment-link" href="${resolveMediaUrl(payload.location)}" target="_blank" rel="noopener">${editor.dom.encode(name)}</a></p>`
                    );
                }
                notify('success', 'Attachment uploaded successfully.');
            } catch (error) {
                notify('error', error.message || 'Upload failed.');
            } finally {
                hideProgress(wrapper);
            }
        });
        input.click();
    }

    function registerSlashCommands(editor) {
        const commands = [
            { text: 'Heading 1', value: 'h1', meta: 'Large section heading' },
            { text: 'Heading 2', value: 'h2', meta: 'Medium section heading' },
            { text: 'Heading 3', value: 'h3', meta: 'Small section heading' },
            { text: 'Bullet list', value: 'ul', meta: 'Unordered list' },
            { text: 'Numbered list', value: 'ol', meta: 'Ordered list' },
            { text: 'Checklist', value: 'checklist', meta: 'Task list' },
            { text: 'Quote', value: 'quote', meta: 'Block quote' },
            { text: 'Code block', value: 'code', meta: 'Monospace code' },
            { text: 'Divider', value: 'hr', meta: 'Horizontal rule' },
            { text: 'Table', value: 'table', meta: 'Insert table' },
            { text: 'Image', value: 'image', meta: 'Upload or browse image' },
            { text: 'Link', value: 'link', meta: 'Insert a link' },
            { text: 'Attachment', value: 'attachment', meta: 'Upload a file' },
        ];

        editor.ui.registry.addAutocompleter('ems-slash', {
            trigger: '/',
            minChars: 0,
            columns: 1,
            fetch: (pattern) => {
                const q = String(pattern || '').toLowerCase();
                const results = commands
                    .filter((item) => !q || item.text.toLowerCase().includes(q) || item.value.includes(q) || item.meta.toLowerCase().includes(q))
                    .map((item) => ({
                        type: 'autocompleteitem',
                        value: item.value,
                        text: item.text,
                        meta: item.meta,
                    }));
                return Promise.resolve(results);
            },
            onAction: (api, rng, value) => {
                editor.selection.setRng(rng);
                editor.insertContent('');
                api.hide();

                const map = {
                    h1: () => editor.execCommand('FormatBlock', false, 'h1'),
                    h2: () => editor.execCommand('FormatBlock', false, 'h2'),
                    h3: () => editor.execCommand('FormatBlock', false, 'h3'),
                    ul: () => editor.execCommand('InsertUnorderedList'),
                    ol: () => editor.execCommand('InsertOrderedList'),
                    checklist: () => editor.insertContent('<ul data-ems-checklist="true"><li>Checklist item</li></ul><p></p>'),
                    quote: () => editor.execCommand('mceBlockQuote'),
                    code: () => editor.execCommand('codesample'),
                    hr: () => editor.insertContent('<hr /><p></p>'),
                    table: () => editor.execCommand('mceInsertTable', false, { rows: 2, columns: 2 }),
                    image: () => insertImageFlow(editor),
                    link: () => editor.execCommand('mceLink'),
                    attachment: () => openAttachmentPicker(editor),
                };

                (map[value] || (() => {}))();
            },
        });
    }

    function registerCustomButtons(editor) {
        const wrapper = editor.getElement()?.closest('[data-ems-rich-editor]') || null;

        const getActiveShapeBox = () => editor.dom.getParent(editor.selection.getNode(), '.ems-shape-box');

        const handleShapeAlignCommand = (command) => {
            const box = getActiveShapeBox();
            if (!box) return false;
            const alignMatch = String(command || '').match(/^Justify(Left|Center|Right|Full)$/i)
                || String(command || '').match(/^align(left|center|right|justify)$/i);
            if (!alignMatch) return false;
            const raw = alignMatch[1].toLowerCase();
            applyShapeAlign(editor, box, raw === 'full' ? 'justify' : raw);
            return true;
        };

        const handleShapeColorCommand = (format, color) => {
            const box = getActiveShapeBox();
            if (!box || !color) return false;
            const name = String(format || '').toLowerCase();
            if (name.includes('hilite') || name.includes('back')) {
                applyShapeFillColor(editor, box, color);
            } else {
                applyShapeTextColor(editor, box, color);
            }
            return true;
        };

        editor.on('BeforeExecCommand', (event) => {
            const command = String(event.command || '');
            if (handleShapeAlignCommand(command)) {
                event.preventDefault();
                return;
            }

            if (command === 'mceApplyTextcolor' || command === 'ForeColor' || command === 'HiliteColor') {
                const format = event.ui || (command === 'HiliteColor' ? 'hilitecolor' : 'forecolor');
                if (handleShapeColorCommand(format, event.value)) {
                    event.preventDefault();
                }
                return;
            }

            if (command === 'LineHeight' || command === 'mceLineHeight') {
                const box = getActiveShapeBox();
                if (box) {
                    event.preventDefault();
                    applyShapeLineHeight(editor, box, event.value);
                }
                return;
            }

            if (command === 'FontName' || command === 'FontSize') {
                const box = getActiveShapeBox();
                if (!box || !event.value) return;
                event.preventDefault();
                if (command === 'FontName') {
                    applyShapeTextStyle(editor, box, { 'font-family': event.value });
                } else {
                    applyShapeTextStyle(editor, box, { 'font-size': event.value });
                }
            }
        });

        // Enter inside shape text inserts a line break (multiline), not a new paragraph.
        editor.on('keydown', (event) => {
            if (event.key !== 'Enter' && event.keyCode !== 13) return;
            const box = getActiveShapeBox();
            if (!box) return;
            if (event.shiftKey) return;
            event.preventDefault();
            event.stopPropagation();
            editor.execCommand('InsertLineBreak');
        });

        editor.on('init', () => {
            if (!editor.formatter?.apply) return;
            const originalApply = editor.formatter.apply.bind(editor.formatter);
            editor.formatter.apply = (name, vars, node) => {
                const formatName = String(name || '').toLowerCase();
                const box = getActiveShapeBox();

                if (box && ['alignleft', 'aligncenter', 'alignright', 'alignjustify'].includes(formatName)) {
                    if (handleShapeAlignCommand(formatName)) return;
                }
                if (box && (formatName === 'forecolor' || formatName === 'hilitecolor')) {
                    const color = vars?.value;
                    if (handleShapeColorCommand(formatName, color)) return;
                }
                if (box && (formatName === 'fontsize' || formatName === 'fontname' || formatName === 'lineheight')) {
                    const textEl = getShapeTextTarget(box);
                    if (textEl && vars?.value) {
                        if (formatName === 'fontsize') {
                            applyShapeTextStyle(editor, box, { 'font-size': vars.value });
                            return;
                        }
                        if (formatName === 'fontname') {
                            applyShapeTextStyle(editor, box, { 'font-family': vars.value });
                            return;
                        }
                        if (formatName === 'lineheight') {
                            applyShapeLineHeight(editor, box, vars.value);
                            return;
                        }
                    }
                }

                try {
                    return originalApply(name, vars, node);
                } catch {
                    if (!box) return;
                    // Fallback for formats that fail inside nested contenteditable.
                    if (['bold', 'italic', 'underline', 'strikethrough'].includes(formatName)) {
                        editor.execCommand(formatName === 'strikethrough' ? 'Strikethrough' : formatName.charAt(0).toUpperCase() + formatName.slice(1));
                    }
                }
            };

            enableShapeResizing(editor);
        });

        editor.ui.registry.addIcon(
            'ems-checklist',
            '<svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M9.5 16.2 5.8 12.5l1.4-1.4 2.3 2.3 6.3-6.3 1.4 1.4-7.7 7.7zM4 19h16v1.5H4V19zm0-4.5h2V16H4v-1.5zm0-4h2V12H4v-1.5zm0-4h2V8H4V6.5z"/></svg>'
        );
        editor.ui.registry.addIcon(
            'ems-attachment',
            '<svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M16.5 6.5v8.8a4.5 4.5 0 1 1-9 0V6.2a3.2 3.2 0 1 1 6.4 0v8.6a1.9 1.9 0 1 1-3.8 0V7.5h1.5v7.3a.4.4 0 0 0 .8 0V6.2a1.7 1.7 0 1 0-3.4 0v9.1a3 3 0 1 0 6 0V6.5h1.5z"/></svg>'
        );
        editor.ui.registry.addIcon(
            'ems-linespace',
            '<svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M4 5h16v1.6H4V5zm0 5.2h16v1.6H4v-1.6zm0 5.2h16V17H4v-1.6zM3 3.2l2.2 2.2L3 7.6V3.2zm0 13.2l2.2 2.2L3 20.8v-4.4z"/></svg>'
        );
        editor.ui.registry.addIcon(
            'ems-shape',
            '<svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h5A2.5 2.5 0 0 1 14 6.5v5a2.5 2.5 0 0 1-2.5 2.5h-5A2.5 2.5 0 0 1 4 11.5v-5zM16.5 10a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9z"/></svg>'
        );
        editor.ui.registry.addIcon(
            'ems-quote',
            '<svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M7.2 17.5 4 14.3V9.2C4 6.4 6.2 4.2 9 4.2h.8v2.2H9c-1.5 0-2.8 1.2-2.8 2.8v1.2h3.5v7.1H7.2zm9.8 0-3.2-3.2V9.2c0-2.8 2.2-5 5-5h.8v2.2h-.8c-1.5 0-2.8 1.2-2.8 2.8v1.2H20v7.1h-3z"/></svg>'
        );

        editor.ui.registry.addToggleButton('checklist', {
            icon: 'ems-checklist',
            tooltip: 'Insert checklist',
            onAction: () => {
                editor.insertContent('<ul data-ems-checklist="true"><li>Checklist item</li><li data-checked="true">Completed item</li></ul><p></p>');
            },
        });

        editor.ui.registry.addButton('attachment', {
            icon: 'ems-attachment',
            tooltip: 'Upload image or file',
            onAction: () => openAttachmentPicker(editor),
        });

        editor.ui.registry.addButton('emsimage', {
            icon: 'image',
            tooltip: 'Insert image',
            onAction: () => insertImageFlow(editor),
        });

        editor.ui.registry.addButton('emsmedia', {
            icon: 'embed',
            tooltip: 'Insert video or audio',
            onAction: () => insertMediaFlow(editor),
        });

        editor.ui.registry.addMenuButton('emslinespace', {
            icon: 'ems-linespace',
            tooltip: 'Line spacing',
            fetch: (callback) => {
                const items = LINE_SPACE_OPTIONS.map((opt) => ({
                    type: 'menuitem',
                    text: opt.text,
                    onAction: () => applyLineSpacing(editor, opt.value),
                }));
                items.push({ type: 'separator' });
                items.push({
                    type: 'menuitem',
                    text: 'Add space after paragraph',
                    onAction: () => applyParagraphGap(editor, '1.25em'),
                });
                items.push({
                    type: 'menuitem',
                    text: 'Remove space after paragraph',
                    onAction: () => applyParagraphGap(editor, '0'),
                });
                items.push({
                    type: 'menuitem',
                    text: 'Reset paragraph spacing',
                    onAction: () => applyParagraphGap(editor, null),
                });
                callback(items);
            },
        });

        editor.ui.registry.addMenuButton('emsquote', {
            icon: 'ems-quote',
            tooltip: 'Quote',
            fetch: (callback) => {
                callback([
                    {
                        type: 'menuitem',
                        text: 'Insert / toggle quote',
                        onAction: () => editor.execCommand('mceBlockQuote'),
                    },
                    {
                        type: 'menuitem',
                        text: 'Quote background color…',
                        onAction: () => openQuoteBgDialog(editor),
                    },
                ]);
            },
        });

        editor.ui.registry.addToggleButton('emscodeview', {
            icon: 'sourcecode',
            tooltip: 'Show HTML code',
            onAction: (api) => toggleCodeView(editor, wrapper, api),
            onSetup: (api) => {
                api.setActive(Boolean(wrapper?.classList.contains('is-codeview')));
                return () => {};
            },
        });

        editor.ui.registry.addMenuButton('emsshapes', {
            icon: 'ems-shape',
            tooltip: 'Insert shape',
            fetch: (callback) => {
                callback(SHAPE_OPTIONS.map((opt) => ({
                    type: 'menuitem',
                    text: opt.text,
                    onAction: () => openShapeDialog(editor, opt.value),
                })));
            },
        });

        editor.ui.registry.addButton('emsshapeedit', {
            text: 'Edit shape',
            tooltip: 'Edit shape colors, size and text',
            onAction: () => {
                const node = editor.selection.getNode();
                const box = editor.dom.getParent(node, '.ems-shape-box');
                if (!box) {
                    notify('warning', 'Select a shape first.');
                    return;
                }
                openShapeDialog(editor, box.getAttribute('data-ems-shape') || 'rectangle', box);
            },
        });

        editor.ui.registry.addButton('emsquotebg', {
            text: 'Quote color',
            tooltip: 'Change quote background color',
            onAction: () => openQuoteBgDialog(editor),
        });

        // Keep near the caret/selection so wide shapes don't pin the bar to the far right.
        editor.ui.registry.addContextToolbar('emsshapetoolbar', {
            predicate: (node) => {
                const box = editor.dom.getParent(node, '.ems-shape-box');
                return Boolean(box) && box.getAttribute('data-ems-shape') !== 'line';
            },
            items: 'emsshapeedit',
            position: 'selection',
            scope: 'node',
        });

        editor.ui.registry.addContextToolbar('emsquotetoolbar', {
            predicate: (node) => Boolean(editor.dom.getParent(node, 'blockquote')),
            items: 'emsquotebg',
            position: 'selection',
            scope: 'node',
        });

        editor.on('dblclick', (event) => {
            const box = editor.dom.getParent(event.target, '.ems-shape-box');
            if (!box) return;
            event.preventDefault();
            openShapeDialog(editor, box.getAttribute('data-ems-shape') || 'rectangle', box);
        });

        editor.ui.registry.addMenuButton('emstabledesign', {
            icon: 'table',
            tooltip: 'Table design',
            fetch: (callback) => {
                callback(TABLE_DESIGN_OPTIONS.map((opt) => ({
                    type: 'menuitem',
                    text: opt.text,
                    onAction: () => applyTableDesign(editor, opt.value),
                })));
            },
        });
    }

    function enableShapeResizing(editor) {
        const doc = editor.getDoc();
        const body = editor.getBody();
        if (!doc || !body) return;

        let drag = null;

        const clearHandles = () => {
            body.querySelectorAll('.ems-shape-resize-handle').forEach((handle) => handle.remove());
            body.querySelectorAll('.ems-shape-box.is-resize-active').forEach((box) => {
                box.classList.remove('is-resize-active');
            });
        };

        const showHandles = (box) => {
            clearHandles();
            if (!box || box.getAttribute('data-ems-shape') === 'line') return;
            box.classList.add('is-resize-active');
            ['e', 's', 'se'].forEach((dir) => {
                const handle = doc.createElement('span');
                handle.className = `mceNonEditable ems-shape-resize-handle ems-shape-resize-handle--${dir}`;
                handle.setAttribute('contenteditable', 'false');
                handle.setAttribute('data-resize-dir', dir);
                handle.setAttribute('title', 'Drag to resize');
                box.appendChild(handle);
            });
        };

        const onMouseMove = (event) => {
            if (!drag?.box) return;
            event.preventDefault();
            const dx = event.clientX - drag.startX;
            const dy = event.clientY - drag.startY;
            let nextW = drag.startW;
            let nextH = drag.startH;
            if (drag.dir.includes('e')) nextW = Math.max(80, Math.round(drag.startW + dx));
            if (drag.dir.includes('s')) nextH = Math.max(40, Math.round(drag.startH + dy));

            const kind = drag.box.getAttribute('data-ems-shape');
            if (kind === 'circle' || kind === 'star') {
                const edge = Math.max(nextW, nextH);
                nextW = edge;
                nextH = edge;
            }

            applyShapeBoxStyles(editor, drag.box, {
                width: `${nextW}px`,
                height: `${nextH}px`,
            });
        };

        const onMouseUp = () => {
            if (!drag) return;
            drag = null;
            doc.removeEventListener('mousemove', onMouseMove);
            doc.removeEventListener('mouseup', onMouseUp);
            editor.nodeChanged();
        };

        body.addEventListener('mousedown', (event) => {
            const handle = event.target?.closest?.('.ems-shape-resize-handle');
            if (!handle) return;
            const box = handle.closest('.ems-shape-box');
            if (!box) return;
            event.preventDefault();
            event.stopPropagation();
            const rect = box.getBoundingClientRect();
            drag = {
                box,
                dir: handle.getAttribute('data-resize-dir') || 'se',
                startX: event.clientX,
                startY: event.clientY,
                startW: rect.width,
                startH: rect.height,
            };
            doc.addEventListener('mousemove', onMouseMove);
            doc.addEventListener('mouseup', onMouseUp);
        });

        editor.on('NodeChange', () => {
            const box = editor.dom.getParent(editor.selection.getNode(), '.ems-shape-box');
            if (box) showHandles(box);
            else clearHandles();
        });

        const stripHandlesFromNode = (root) => {
            if (!root?.querySelectorAll) return;
            root.querySelectorAll('.ems-shape-resize-handle').forEach((handle) => handle.remove());
            root.querySelectorAll('.ems-shape-box.is-resize-active').forEach((box) => {
                box.classList.remove('is-resize-active');
            });
        };

        editor.on('GetContent', (event) => {
            if (!event.content || event.selection) return;
            // Defensive strip if handles leaked into serialized HTML.
            event.content = String(event.content)
                .replace(/<span[^>]*class="[^"]*ems-shape-resize-handle[^"]*"[^>]*>\s*<\/span>/gi, '')
                .replace(/\s*is-resize-active/g, '');
        });

        editor.on('PreProcess', (event) => {
            stripHandlesFromNode(event.node);
        });

        editor.on('remove', clearHandles);
    }

    async function handleEditorImageDrop(editor, wrapper, uploadUrl, file) {
        if (!file || !file.type.startsWith('image/')) return;
        try {
            await insertUploadedImage(editor, wrapper, uploadUrl, file);
        } catch (error) {
            notify('error', error.message || 'Upload failed.');
        } finally {
            hideProgress(wrapper);
        }
    }

    function setFullscreenPageState(active) {
        document.documentElement.classList.toggle('ems-editor-fullscreen', active);
        document.body.classList.toggle('ems-editor-fullscreen', active);
    }

    function getFullscreenElement() {
        return document.fullscreenElement
            || document.webkitFullscreenElement
            || null;
    }

    function requestElementFullscreen(el) {
        if (!el) return Promise.reject(new Error('No element'));
        if (typeof el.requestFullscreen === 'function') {
            return el.requestFullscreen({ navigationUI: 'hide' }).catch(() => el.requestFullscreen());
        }
        if (typeof el.webkitRequestFullscreen === 'function') {
            el.webkitRequestFullscreen();
            return Promise.resolve();
        }
        return Promise.reject(new Error('Fullscreen API unavailable'));
    }

    function exitElementFullscreen() {
        if (!getFullscreenElement()) return Promise.resolve();
        if (typeof document.exitFullscreen === 'function') {
            return document.exitFullscreen();
        }
        if (typeof document.webkitExitFullscreen === 'function') {
            document.webkitExitFullscreen();
            return Promise.resolve();
        }
        return Promise.resolve();
    }

    // Solid colors (matching .panel-input light/dark surfaces) — never
    // "transparent", otherwise the iframe falls back to browser-default
    // white regardless of the admin theme.
    function contentStyle(isDark) {
        const bg = isDark ? '#0f172a' : '#ffffff';
        const color = isDark ? '#e2e8f0' : '#0f172a';
        const muted = isDark ? '#94a3b8' : '#64748b';
        const link = isDark ? '#5eead4' : '#0f766e';
        const codeBg = isDark ? '#1e293b' : '#f1f5f9';
        const border = isDark ? '#334155' : '#e2e8f0';
        const quoteBg = isDark ? 'rgba(30, 41, 59, 0.55)' : '#f8fafc';
        return `
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Open+Sans:wght@400;600;700&family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap');
            html {
                height: 100%;
                background: ${bg} !important;
                scrollbar-color: ${border} ${bg};
            }
            html, body {
                background: ${bg} !important;
            }
            ::-webkit-scrollbar {
                width: 10px;
                height: 10px;
            }
            ::-webkit-scrollbar-track {
                background: ${bg};
            }
            ::-webkit-scrollbar-thumb {
                background: ${border};
                border-radius: 8px;
                border: 2px solid ${bg};
            }
            ::-webkit-scrollbar-thumb:hover {
                background: ${muted};
            }
            body {
                position: relative;
                box-sizing: border-box;
                font-family: Inter, "Segoe UI", system-ui, -apple-system, sans-serif;
                font-size: 15px;
                line-height: 1.65;
                color: ${color};
                margin: 0;
                padding: ${CONTENT_PAD_Y} ${CONTENT_PAD_X} 28px;
                min-height: 100%;
                text-align: left;
                direction: ltr;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            body.mce-content-body[data-mce-placeholder]:not(.mce-visualblocks)::before {
                position: absolute !important;
                left: ${CONTENT_PAD_X} !important;
                right: ${CONTENT_PAD_X} !important;
                top: ${CONTENT_PAD_Y} !important;
                margin: 0 !important;
                padding: 0 !important;
                width: auto !important;
                max-width: none !important;
                color: ${muted} !important;
                font-family: inherit !important;
                font-size: inherit !important;
                font-weight: 400 !important;
                line-height: inherit !important;
                letter-spacing: inherit !important;
                content: attr(data-mce-placeholder);
                pointer-events: none;
                white-space: pre-wrap;
            }
            /* Block alignment is controlled by toolbar (inline text-align styles). */
            p {
                margin: 0 0 0.85em;
            }
            p:last-child {
                margin-bottom: 0;
            }
            h1, h2, h3, h4, h5, h6 {
                font-weight: 650;
                line-height: 1.3;
                margin: 1.15em 0 0.45em;
                letter-spacing: -0.015em;
                color: ${color};
            }
            h1:first-child, h2:first-child, h3:first-child,
            h4:first-child, h5:first-child, h6:first-child,
            p:first-child {
                margin-top: 0;
            }
            h1 { font-size: 1.75em; }
            h2 { font-size: 1.4em; }
            h3 { font-size: 1.2em; }
            h4 { font-size: 1.05em; }
            h5, h6 { font-size: 0.95em; text-transform: uppercase; letter-spacing: 0.04em; color: ${muted}; }
            a { color: ${link}; text-decoration: underline; text-underline-offset: 2px; }
            img {
                max-width: 100%;
                height: auto;
                border-radius: 10px;
                box-shadow: 0 0 0 1px ${border};
            }
            img.ems-img-inline,
            p img,
            li img,
            td img,
            th img,
            span img {
                display: inline !important;
                vertical-align: middle !important;
                margin: 0 0.4em !important;
                max-width: min(100%, 280px);
            }
            video {
                max-width: 100%;
                height: auto;
                border-radius: 10px;
                margin: 0.5em 0;
                box-shadow: 0 0 0 1px ${border};
                display: block;
            }
            .ems-shape-box {
                position: relative;
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                vertical-align: middle;
                margin: 0.25em 0.4em;
                min-width: 88px;
                min-height: 56px;
                padding: 0.55em 0.85em;
                box-sizing: border-box;
                text-align: center;
                font-weight: 600;
                line-height: 1.35;
                cursor: text;
                user-select: text;
                box-shadow: 0 0 0 1px rgb(15 23 42 / 0.06);
                max-width: 100%;
            }
            .ems-shape-box.is-resize-active {
                outline: 2px solid ${link};
                outline-offset: 2px;
            }
            .ems-shape-box[data-ems-align="left"],
            .ems-shape-box[style*="text-align: left"],
            .ems-shape-box[style*="text-align:left"] {
                text-align: left !important;
                justify-content: flex-start !important;
            }
            .ems-shape-box[data-ems-align="center"],
            .ems-shape-box[style*="text-align: center"],
            .ems-shape-box[style*="text-align:center"] {
                text-align: center !important;
                justify-content: center !important;
            }
            .ems-shape-box[data-ems-align="right"],
            .ems-shape-box[style*="text-align: right"],
            .ems-shape-box[style*="text-align:right"] {
                text-align: right !important;
                justify-content: flex-end !important;
            }
            .ems-shape-box[data-ems-align="justify"],
            .ems-shape-box[style*="text-align: justify"],
            .ems-shape-box[style*="text-align:justify"] {
                text-align: justify !important;
                justify-content: stretch !important;
            }
            .ems-shape-box__text {
                display: block;
                width: 100%;
                outline: none;
                min-width: 1ch;
                word-break: break-word;
                white-space: pre-wrap;
                overflow-wrap: anywhere;
            }
            .ems-shape-resize-handle {
                position: absolute;
                z-index: 6;
                width: 10px;
                height: 10px;
                border: 2px solid #fff;
                border-radius: 2px;
                background: ${link};
                box-shadow: 0 0 0 1px rgb(15 23 42 / 0.25);
                pointer-events: auto;
            }
            .ems-shape-resize-handle--e {
                top: 50%;
                right: -6px;
                transform: translateY(-50%);
                cursor: ew-resize;
            }
            .ems-shape-resize-handle--s {
                left: 50%;
                bottom: -6px;
                transform: translateX(-50%);
                cursor: ns-resize;
            }
            .ems-shape-resize-handle--se {
                right: -6px;
                bottom: -6px;
                cursor: nwse-resize;
            }
            .ems-shape-box--rectangle { border-radius: 6px; min-width: 120px; }
            .ems-shape-box--rounded { border-radius: 18px; min-width: 120px; }
            .ems-shape-box--circle {
                border-radius: 50%;
                width: 96px;
                height: 96px;
                min-width: 96px;
                min-height: 96px;
                padding: 0.5em;
            }
            .ems-shape-box--ellipse {
                border-radius: 50%;
                min-width: 140px;
                min-height: 72px;
            }
            .ems-shape-box--triangle {
                clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
                border: 0 !important;
                min-width: 110px;
                min-height: 96px;
                padding: 1.6em 0.75em 0.55em;
            }
            .ems-shape-box--line {
                min-width: 140px;
                min-height: 12px;
                height: 12px;
                padding: 0;
                border-radius: 999px;
            }
            .ems-shape-box--line .ems-shape-box__text { display: none; }
            .ems-shape-box--arrow {
                clip-path: polygon(0 28%, 68% 28%, 68% 0, 100% 50%, 68% 100%, 68% 72%, 0 72%);
                border: 0 !important;
                min-width: 150px;
                min-height: 48px;
                padding: 0.55em 1.4em 0.55em 0.75em;
            }
            .ems-shape-box--star {
                clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
                border: 0 !important;
                width: 96px;
                height: 96px;
                min-width: 96px;
                min-height: 96px;
                padding: 1.1em 0.5em;
            }
            table { border-collapse: collapse; width: 100%; margin: 0.75em 0; }
            table td, table th {
                border: 1px solid ${border};
                padding: 8px 10px;
                vertical-align: top;
            }
            table th { background: ${codeBg}; font-weight: 600; }
            table.ems-table-bordered td,
            table.ems-table-bordered th {
                border: 2px solid ${border};
            }
            table.ems-table-striped tr:nth-child(even) td {
                background: ${codeBg};
            }
            table.ems-table-minimal td,
            table.ems-table-minimal th {
                border: 0;
                border-bottom: 1px solid ${border};
            }
            table.ems-table-modern {
                border: 0;
                overflow: hidden;
                border-radius: 12px;
                box-shadow: 0 0 0 1px ${border};
            }
            table.ems-table-modern td,
            table.ems-table-modern th {
                border: 0;
                border-bottom: 1px solid ${border};
            }
            table.ems-table-modern th {
                background: ${link};
                color: #fff;
            }
            table.ems-table-compact td,
            table.ems-table-compact th {
                padding: 4px 8px;
                font-size: 0.92em;
            }
            ul, ol { margin: 0.35em 0 0.85em; padding-left: 1.5em; }
            li { margin: 0.3em 0; }
            ul[data-ems-checklist] { list-style: none; padding-left: 0.15em; }
            ul[data-ems-checklist] li { position: relative; padding-left: 1.7em; margin: 0.4em 0; }
            ul[data-ems-checklist] li::before { content: '☐'; position: absolute; left: 0; color: ${muted}; }
            ul[data-ems-checklist] li[data-checked="true"]::before { content: '☑'; color: ${link}; }
            pre, pre.code, .mce-content-body pre {
                background: ${codeBg};
                padding: 12px 14px;
                border-radius: 10px;
                overflow: auto;
                border: 1px solid ${border};
                font-size: 0.92em;
                line-height: 1.55;
            }
            code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
            blockquote {
                border-left: 3px solid ${link};
                margin: 0.85em 0;
                padding: 0.55em 0.85em;
                background: ${quoteBg};
                border-radius: 0 8px 8px 0;
                color: ${muted};
            }
            blockquote[data-ems-quote-bg] {
                background: var(--ems-quote-bg, ${quoteBg});
            }
            hr {
                border: 0;
                border-top: 1px solid ${border};
                margin: 1.35em 0;
            }
            .ems-attachment-link {
                display: inline-flex;
                align-items: center;
                gap: 0.35em;
                padding: 0.35em 0.65em;
                border-radius: 8px;
                background: ${codeBg};
                border: 1px solid ${border};
                text-decoration: none !important;
                font-weight: 600;
            }
        `;
    }

    function isDarkMode() {
        return document.documentElement.classList.contains('dark')
            || document.body.classList.contains('dark');
    }

    function resolveUiMode(wrapper, options = {}) {
        const raw = options.mode
            || wrapper?.getAttribute('data-editor-mode')
            || wrapper?.getAttribute('data-editor-preset')
            || 'header';
        // One shared UI everywhere — legacy aliases all resolve to header.
        return MODE_ALIASES[raw] || 'header';
    }

    function resolveToolbar(wrapper, options = {}) {
        const custom = options.toolbar !== undefined
            ? options.toolbar
            : (wrapper?.getAttribute('data-editor-toolbar') || '').trim();
        if (custom && custom !== 'false') return custom;

        return SHARED_TOOLBAR;
    }

    async function mountTextarea(textarea, options = {}) {
        if (!textarea || !textarea.id) {
            return null;
        }

        if (registry.has(textarea.id)) {
            return registry.get(textarea.id);
        }

        ensureEditorCss();

        const wrapper = options.wrapper
            || textarea.closest('[data-ems-rich-editor]')
            || textarea.parentElement;

        const cdnBase = options.cdnBase
            || wrapper?.getAttribute('data-editor-cdn-base')
            || 'https://cdn.jsdelivr.net/npm/tinymce@7.6.1';

        const tinymce = await ensureTinyMce(cdnBase);
        if (!tinymce) {
            return createFallbackAdapter(textarea, wrapper);
        }

        const uploadUrl = options.uploadUrl
            || wrapper?.getAttribute('data-editor-upload-url')
            || '/admin/editor/media';

        const height = Number.parseInt(
            String(options.height || wrapper?.getAttribute('data-editor-height') || textarea.getAttribute('rows') * 24 || 280),
            10
        ) || 280;

        const readonly = Boolean(options.readonly || wrapper?.getAttribute('data-editor-readonly') === '1');
        const uiMode = resolveUiMode(wrapper, options);
        const toolbar = resolveToolbar(wrapper, options);
        const placeholder = options.placeholder
            || wrapper?.getAttribute('data-editor-placeholder')
            || textarea.getAttribute('placeholder')
            || 'Start writing…';

        const dark = isDarkMode();
        const skin = dark ? 'oxide-dark' : 'oxide';
        const contentCssName = dark ? 'dark' : 'default';
        const base = cdnBase.replace(/\/$/, '');
        const menubar = options.menubar !== undefined ? options.menubar : false;
        const configuredHeight = Math.max(height, 260);

        hideProgress(wrapper);
        wrapper?.classList.add('ems-rich-editor--header');
        wrapper?.classList.remove(
            'ems-rich-editor--compact',
            'ems-rich-editor--linear',
            'ems-rich-editor--classic',
            'ems-rich-editor--bubble'
        );
        wrapper?.setAttribute('data-editor-mode', 'header');
        wrapper?.setAttribute('data-editor-preset', options.preset || wrapper?.getAttribute('data-editor-preset') || 'full');

        return new Promise((resolve) => {
            tinymce.init({
                target: textarea,
                license_key: 'gpl',
                base_url: base,
                suffix: '.min',
                promotion: false,
                branding: false,
                menubar,
                statusbar: false,
                elementpath: false,
                plugins: options.plugins || DEFAULT_PLUGINS,
                toolbar,
                toolbar_mode: 'wrap',
                toolbar_sticky: false,
                fixed_toolbar_container: false,
                quickbars_selection_toolbar: false,
                quickbars_insert_toolbar: false,
                height: configuredHeight,
                min_height: Math.max(configuredHeight - 40, 220),
                placeholder,
                readonly,
                skin,
                skin_url: `${base}/skins/ui/${skin}`,
                content_css: `${base}/skins/content/${contentCssName}/content.min.css`,
                content_style: contentStyle(dark),
                font_family_formats: FONT_FAMILY_FORMATS,
                font_size_formats: FONT_SIZE_FORMATS,
                line_height_formats: LINE_HEIGHT_FORMATS,
                block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Preformatted=pre',
                color_map: [
                    '#0F172A', 'Slate 900', '#334155', 'Slate 700', '#64748B', 'Slate 500',
                    '#94A3B8', 'Slate 400', '#F8FAFC', 'White',
                    '#0D9488', 'Teal', '#0EA5E9', 'Sky', '#6366F1', 'Indigo', '#8B5CF6', 'Violet', '#EC4899', 'Pink',
                    '#EF4444', 'Red', '#F97316', 'Orange', '#F59E0B', 'Amber', '#EAB308', 'Yellow', '#22C55E', 'Green',
                ],
                color_cols: 5,
                custom_colors: true,
                color_default_foreground: '#0F172A',
                color_default_background: '#0D9488',
                force_hex_color: 'always',
                image_title: true,
                image_description: true,
                image_dimensions: true,
                automatic_uploads: true,
                images_upload_credentials: true,
                images_file_types: 'jpeg,jpg,png,gif,webp',
                images_upload_handler: buildImagesUploadHandler(uploadUrl, wrapper),
                file_picker_types: 'file image media',
                file_picker_callback: buildFilePicker(uploadUrl, wrapper),
                media_live_embeds: true,
                link_default_target: '_blank',
                link_context_toolbar: false,
                relative_urls: false,
                remove_script_host: false,
                convert_urls: true,
                paste_data_images: true,
                sandbox_iframes: false,
                extended_valid_elements: [
                    'img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|loading|style]',
                    'video[*]',
                    'audio[*]',
                    'source[*]',
                    'a[*]',
                    'br',
                    'span[class|style|contenteditable|title|data-resize-dir|data-ems-shape|data-ems-fill|data-ems-border|data-ems-border-width|data-ems-text-color|data-ems-align|data-ems-line-height|data-ems-width|data-ems-height]',
                    'blockquote[class|style|data-ems-quote-bg]',
                ].join(','),
                valid_styles: {
                    '*': 'text-align,color,background-color,background,font-size,font-family,font-weight,font-style,text-decoration,line-height,margin,margin-bottom,padding,border,border-radius,width,height,max-width,min-width,min-height,display,justify-content,align-items,vertical-align,float,box-shadow,clip-path,white-space,overflow-wrap,position,top,right,bottom,left,outline,outline-offset,cursor,transform',
                },
                noneditable_class: 'mceNonEditable',
                editable_class: 'mceEditable',
                table_class_list: [
                    { title: 'Default', value: '' },
                    { title: 'Bordered', value: 'ems-table-bordered' },
                    { title: 'Striped', value: 'ems-table-striped' },
                    { title: 'Minimal', value: 'ems-table-minimal' },
                    { title: 'Modern', value: 'ems-table-modern' },
                    { title: 'Compact', value: 'ems-table-compact' },
                ],
                table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol | tablecellprops',
                table_appearance_options: true,
                video_template_callback: (data) => (
                    `<video controls="controls" preload="metadata" width="${data.width || ''}" height="${data.height || ''}" style="max-width:100%;height:auto">`
                    + `<source src="${data.source}"${data.sourcemime ? ` type="${data.sourcemime}"` : ''} />`
                    + (data.altsource ? `<source src="${data.altsource}"${data.altsourcemime ? ` type="${data.altsourcemime}"` : ''} />` : '')
                    + '</video>'
                ),
                audio_template_callback: (data) => (
                    `<audio controls="controls" preload="metadata" style="width:100%;max-width:480px">`
                    + `<source src="${data.source}"${data.sourcemime ? ` type="${data.sourcemime}"` : ''} />`
                    + '</audio>'
                ),
                setup: (editor) => {
                    registerCustomButtons(editor);
                    registerSlashCommands(editor);

                    const surfaceEl = wrapper?.querySelector('.ems-rich-editor__surface') || null;
                    // configuredHeight already computed above for this instance
                    let fullscreenResizeHandler = null;
                    let fullscreenChangeHandler = null;
                    let surfaceResizeObserver = null;
                    let uiParkObserver = null;
                    let isEmsFullscreen = false;
                    let fullscreenButtonApi = null;
                    let savedLayout = null;
                    let normalSurfaceHeight = configuredHeight;
                    let lastSyncedSurfaceHeight = 0;
                    const parkedUiNodes = [];

                    const themeColors = () => {
                        const nowDark = isDarkMode();
                        return {
                            dark: nowDark,
                            bg: nowDark ? '#0f172a' : '#ffffff',
                            color: nowDark ? '#e2e8f0' : '#0f172a',
                        };
                    };

                    const isParkableUiNode = (node) => {
                        if (!node || node.nodeType !== 1) return false;
                        return node.classList?.contains('tox-tinymce-aux')
                            || node.classList?.contains('tox-silver-sink')
                            || node.classList?.contains('tox-dialog-wrap')
                            || node.matches?.('.tox-tinymce-aux, .tox-silver-sink, .tox-dialog-wrap');
                    };

                    const parkUiNode = (node) => {
                        if (!surfaceEl || !isParkableUiNode(node)) return;
                        if (node.parentElement === surfaceEl) return;
                        // Never reparent open menus mid-flight — only sinks/aux/dialogs.
                        if (node.classList?.contains('tox-menu') || node.classList?.contains('tox-collection')) {
                            return;
                        }
                        if (parkedUiNodes.some((entry) => entry.el === node)) {
                            surfaceEl.appendChild(node);
                            return;
                        }
                        parkedUiNodes.push({ el: node, parent: node.parentNode, next: node.nextSibling });
                        surfaceEl.appendChild(node);
                        if (node.classList?.contains('tox-tinymce-aux') || node.classList?.contains('tox-silver-sink')) {
                            node.classList.add('ems-rte-fs-sink');
                        }
                    };

                    const parkAuxInFullscreen = () => {
                        if (!surfaceEl) return;
                        document.querySelectorAll('.tox-tinymce-aux, .tox-silver-sink').forEach((node) => {
                            parkUiNode(node);
                        });
                        if (uiParkObserver) return;
                        uiParkObserver = new MutationObserver((mutations) => {
                            mutations.forEach((mutation) => {
                                mutation.addedNodes.forEach((node) => {
                                    if (!isParkableUiNode(node)) return;
                                    // Menus must stay inside the already-parked aux; only park new sinks.
                                    if (node.classList?.contains('tox-tinymce-aux')
                                        || node.classList?.contains('tox-silver-sink')
                                        || node.classList?.contains('tox-dialog-wrap')) {
                                        parkUiNode(node);
                                    }
                                });
                            });
                        });
                        uiParkObserver.observe(document.body, { childList: true });
                    };

                    const restoreAuxAfterFullscreen = () => {
                        if (uiParkObserver) {
                            uiParkObserver.disconnect();
                            uiParkObserver = null;
                        }
                        while (parkedUiNodes.length) {
                            const { el, parent, next } = parkedUiNodes.pop();
                            if (!el || !parent) continue;
                            el.classList?.remove('ems-rte-fs-sink');
                            try {
                                if (next && next.parentNode === parent) {
                                    parent.insertBefore(el, next);
                                } else {
                                    parent.appendChild(el);
                                }
                            } catch {
                                parent.appendChild(el);
                            }
                        }
                    };

                    const forceContentColors = () => {
                        const { bg, color } = themeColors();
                        const body = editor.getBody?.();
                        if (body) {
                            editor.dom.setStyles(body, {
                                'background-color': bg,
                                color,
                            });
                        }
                        const doc = editor.getDoc?.();
                        if (doc?.documentElement) {
                            doc.documentElement.style.backgroundColor = bg;
                        }
                        if (doc?.body) {
                            doc.body.style.backgroundColor = bg;
                            doc.body.style.color = color;
                        }
                        const iframe = editor.iframeElement
                            || editor.getContentAreaContainer?.()?.querySelector?.('iframe')
                            || editor.getContainer?.()?.querySelector?.('.tox-edit-area__iframe');
                        if (iframe) {
                            iframe.style.backgroundColor = bg;
                        }
                        const editArea = editor.getContentAreaContainer?.()
                            || editor.getContainer?.()?.querySelector?.('.tox-edit-area');
                        if (editArea) {
                            editArea.style.backgroundColor = bg;
                        }
                    };

                    const readStyle = (el, prop) => (el?.style ? el.style.getPropertyValue(prop) || el.style[prop] || '' : '');

                    const captureLayoutBeforeFullscreen = () => {
                        const container = editor.getContainer?.();
                        if (!container) {
                            savedLayout = null;
                            return;
                        }
                        const editArea = container.querySelector('.tox-edit-area');
                        const iframe = container.querySelector('.tox-edit-area__iframe, iframe');
                        const wrap = container.querySelector('.tox-sidebar-wrap');
                        const rectH = Math.round(container.getBoundingClientRect().height) || normalSurfaceHeight;
                        savedLayout = {
                            containerHeight: container.style.height || '',
                            containerWidth: container.style.width || '',
                            containerMaxHeight: container.style.maxHeight || '',
                            editAreaHeight: readStyle(editArea, 'height'),
                            editAreaFlex: readStyle(editArea, 'flex'),
                            editAreaBg: readStyle(editArea, 'backgroundColor') || editArea?.style?.backgroundColor || '',
                            iframeHeight: readStyle(iframe, 'height'),
                            iframeBg: iframe?.style?.backgroundColor || '',
                            wrapHeight: readStyle(wrap, 'height'),
                            wrapFlex: readStyle(wrap, 'flex'),
                            surfaceHeight: surfaceEl?.style.height || '',
                            surfaceWidth: surfaceEl?.style.width || '',
                            fallbackHeight: Math.max(rectH, configuredHeight),
                        };
                    };

                    const applyEditorPixelHeight = (totalHeight) => {
                        const container = editor.getContainer?.();
                        if (!container) return;
                        const target = Math.max(160, Math.round(totalHeight) || configuredHeight);
                        const header = container.querySelector('.tox-editor-header');
                        const headerH = header ? Math.ceil(header.getBoundingClientRect().height) : 48;
                        const editH = Math.max(100, target - headerH);
                        const { bg } = themeColors();

                        container.style.height = `${target}px`;
                        container.style.width = '100%';
                        container.style.maxHeight = '';

                        const wrap = container.querySelector('.tox-sidebar-wrap');
                        if (wrap) {
                            wrap.style.height = `${editH}px`;
                            wrap.style.flex = '';
                        }

                        const editArea = container.querySelector('.tox-edit-area');
                        if (editArea) {
                            editArea.style.height = `${editH}px`;
                            editArea.style.flex = '';
                            editArea.style.backgroundColor = bg;
                        }

                        const iframe = container.querySelector('.tox-edit-area__iframe, iframe');
                        if (iframe) {
                            iframe.style.height = `${editH}px`;
                            iframe.style.backgroundColor = bg;
                        }

                        try {
                            if (typeof editor.theme?.resizeTo === 'function') {
                                editor.theme.resizeTo(container.clientWidth || undefined, target);
                            } else {
                                editor.fire('ResizeEditor');
                            }
                        } catch {
                            try {
                                editor.fire('ResizeEditor');
                            } catch {
                                // ignore
                            }
                        }
                    };

                    const syncEditorToSurface = () => {
                        if (isEmsFullscreen || !surfaceEl) return;
                        const h = Math.round(surfaceEl.clientHeight || 0);
                        if (h < 140) return;
                        if (Math.abs(h - lastSyncedSurfaceHeight) < 2) return;
                        lastSyncedSurfaceHeight = h;
                        normalSurfaceHeight = h;
                        applyEditorPixelHeight(h);
                    };

                    const restoreLayoutAfterFullscreen = () => {
                        const restoreH = Math.max(
                            configuredHeight,
                            normalSurfaceHeight,
                            savedLayout?.fallbackHeight || 0
                        );

                        if (surfaceEl) {
                            surfaceEl.style.width = '';
                            surfaceEl.style.height = `${restoreH}px`;
                            surfaceEl.style.minHeight = `${configuredHeight}px`;
                        }

                        lastSyncedSurfaceHeight = 0;
                        applyEditorPixelHeight(restoreH);
                        lastSyncedSurfaceHeight = restoreH;
                        savedLayout = null;

                        window.requestAnimationFrame(() => {
                            syncEditorToSurface();
                            forceContentColors();
                        });
                    };

                    const applyFullscreenMetrics = () => {
                        if (!isEmsFullscreen || !surfaceEl) return;
                        const container = editor.getContainer?.();
                        if (!container) return;

                        const viewportH = window.innerHeight
                            || document.documentElement.clientHeight
                            || 600;
                        const header = container.querySelector('.tox-editor-header');
                        const headerH = header ? Math.ceil(header.getBoundingClientRect().height) : 48;
                        const editH = Math.max(160, viewportH - headerH);
                        const { bg } = themeColors();

                        surfaceEl.style.height = '100%';
                        surfaceEl.style.width = '100%';
                        container.style.height = '100%';
                        container.style.width = '100%';
                        container.style.maxHeight = '100%';

                        const wrap = container.querySelector('.tox-sidebar-wrap');
                        if (wrap) {
                            wrap.style.height = `${editH}px`;
                            wrap.style.flex = '1 1 auto';
                        }

                        const editArea = container.querySelector('.tox-edit-area');
                        if (editArea) {
                            editArea.style.height = `${editH}px`;
                            editArea.style.flex = '1 1 auto';
                            editArea.style.backgroundColor = bg;
                        }

                        const iframe = container.querySelector('.tox-edit-area__iframe, iframe');
                        if (iframe) {
                            iframe.style.height = `${editH}px`;
                            iframe.style.backgroundColor = bg;
                        }

                        forceContentColors();
                        parkAuxInFullscreen();

                        try {
                            editor.fire('ResizeEditor');
                        } catch {
                            // ignore
                        }
                    };

                    const syncThemeClassOnSurface = () => {
                        if (!surfaceEl) return;
                        surfaceEl.classList.toggle('ems-rich-editor--dark', isDarkMode());
                        surfaceEl.classList.toggle('ems-rich-editor--light', !isDarkMode());
                    };

                    const cleanupFullscreenUi = () => {
                        isEmsFullscreen = false;
                        if (fullscreenResizeHandler) {
                            window.removeEventListener('resize', fullscreenResizeHandler);
                            fullscreenResizeHandler = null;
                        }
                        restoreAuxAfterFullscreen();
                        surfaceEl?.classList.remove(
                            'ems-rich-editor__surface--fullscreen',
                            'ems-rich-editor--dark',
                            'ems-rich-editor--light'
                        );
                        setFullscreenPageState(false);
                        fullscreenButtonApi?.setActive(false);
                        restoreLayoutAfterFullscreen();
                    };

                    const onFullscreenChange = () => {
                        const active = getFullscreenElement() === surfaceEl;
                        if (active) {
                            if (!isEmsFullscreen) {
                                captureLayoutBeforeFullscreen();
                            }
                            isEmsFullscreen = true;
                            syncThemeClassOnSurface();
                            surfaceEl.classList.add('ems-rich-editor__surface--fullscreen');
                            parkAuxInFullscreen();
                            setFullscreenPageState(true);
                            fullscreenButtonApi?.setActive(true);
                            if (!fullscreenResizeHandler) {
                                fullscreenResizeHandler = () => applyFullscreenMetrics();
                                window.addEventListener('resize', fullscreenResizeHandler);
                            }
                            window.requestAnimationFrame(() => {
                                applyFullscreenMetrics();
                                editor.focus();
                            });
                            return;
                        }

                        if (isEmsFullscreen) {
                            cleanupFullscreenUi();
                        }
                    };

                    const exitEmsFullscreen = () => {
                        if (getFullscreenElement() === surfaceEl) {
                            exitElementFullscreen().catch(() => cleanupFullscreenUi());
                            return;
                        }
                        cleanupFullscreenUi();
                    };

                    const enterEmsFullscreen = () => {
                        if (!surfaceEl || isEmsFullscreen || getFullscreenElement() === surfaceEl) return;

                        captureLayoutBeforeFullscreen();
                        syncThemeClassOnSurface();
                        surfaceEl.classList.add('ems-rich-editor__surface--fullscreen');

                        requestElementFullscreen(surfaceEl)
                            .then(() => {
                                // fullscreenchange handler applies metrics
                            })
                            .catch(() => {
                                surfaceEl.classList.remove(
                                    'ems-rich-editor__surface--fullscreen',
                                    'ems-rich-editor--dark',
                                    'ems-rich-editor--light'
                                );
                                savedLayout = null;
                                notify('error', 'Fullscreen is not available in this browser.');
                            });
                    };

                    const toggleEmsFullscreen = () => {
                        if (isEmsFullscreen || getFullscreenElement() === surfaceEl) {
                            exitEmsFullscreen();
                            return;
                        }
                        enterEmsFullscreen();
                    };

                    editor.ui.registry.addToggleButton('emsfullscreen', {
                        icon: 'fullscreen',
                        tooltip: 'Fullscreen',
                        onAction: () => toggleEmsFullscreen(),
                        onSetup: (api) => {
                            fullscreenButtonApi = api;
                            api.setActive(isEmsFullscreen);
                            return () => {
                                if (fullscreenButtonApi === api) fullscreenButtonApi = null;
                            };
                        },
                    });

                    fullscreenChangeHandler = onFullscreenChange;
                    document.addEventListener('fullscreenchange', fullscreenChangeHandler);
                    document.addEventListener('webkitfullscreenchange', fullscreenChangeHandler);

                    editor.on('init', () => {
                        wrapper?.classList.add('is-ready');
                        hideProgress(wrapper);
                        textarea.removeAttribute('required');
                        forceContentColors();

                        if (surfaceEl) {
                            surfaceEl.style.minHeight = `${configuredHeight}px`;
                            surfaceEl.style.height = `${configuredHeight}px`;
                            normalSurfaceHeight = configuredHeight;
                            window.requestAnimationFrame(() => {
                                applyEditorPixelHeight(configuredHeight);
                                if (typeof ResizeObserver === 'function' && !surfaceResizeObserver) {
                                    surfaceResizeObserver = new ResizeObserver(() => {
                                        if (isEmsFullscreen) return;
                                        syncEditorToSurface();
                                    });
                                    surfaceResizeObserver.observe(surfaceEl);
                                }
                            });
                        }

                        if (editor.notificationManager?.open) {
                            const originalOpen = editor.notificationManager.open.bind(editor.notificationManager);
                            editor.notificationManager.open = (spec = {}) => {
                                const type = spec.type || 'info';
                                const text = String(spec.text || '').replace(/<[^>]*>/g, '').trim();
                                if (text && (type === 'error' || type === 'warning')) {
                                    notify(type === 'warning' ? 'warning' : 'error', text);
                                    return { close() {}, getEl() { return null; }, moveTo() {}, moveRel() {}, settings: spec };
                                }
                                return originalOpen(spec);
                            };
                        }

                        try {
                            editor.setProgressState(false);
                        } catch {
                            // ignore
                        }
                    });

                    editor.on('SetContent', forceContentColors);

                    editor.on('drop', (event) => {
                        const files = [...(event.dataTransfer?.files || [])];
                        const image = files.find((file) => file.type.startsWith('image/'));
                        if (!image) return;
                        event.preventDefault();
                        handleEditorImageDrop(editor, wrapper, uploadUrl, image);
                    });

                    editor.on('remove', () => {
                        if (fullscreenChangeHandler) {
                            document.removeEventListener('fullscreenchange', fullscreenChangeHandler);
                            document.removeEventListener('webkitfullscreenchange', fullscreenChangeHandler);
                            fullscreenChangeHandler = null;
                        }
                        if (surfaceResizeObserver) {
                            surfaceResizeObserver.disconnect();
                            surfaceResizeObserver = null;
                        }
                        const wasFullscreen = isEmsFullscreen || getFullscreenElement() === surfaceEl;
                        if (wasFullscreen) {
                            if (getFullscreenElement() === surfaceEl) {
                                exitElementFullscreen().catch(() => {});
                            }
                            cleanupFullscreenUi();
                        } else if (uiParkObserver) {
                            uiParkObserver.disconnect();
                            uiParkObserver = null;
                        }
                    });

                    const syncTextareaFromEditor = () => {
                        if (wrapper?.classList.contains('is-codeview')) {
                            const panel = surfaceEl?.querySelector('.ems-rich-editor__codeview');
                            if (panel) {
                                textarea.value = panel.value;
                                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                                return;
                            }
                        }
                        textarea.value = editor.getContent();
                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    };

                    editor.on('change input undo redo SetContent', syncTextareaFromEditor);

                    editor.on('focus', () => {
                        wrapper?.classList.add('is-focused');
                        surfaceEl?.classList.add('is-focused');
                    });
                    editor.on('blur', () => {
                        wrapper?.classList.remove('is-focused');
                        surfaceEl?.classList.remove('is-focused');
                        syncTextareaFromEditor();
                    });

                    // Keep form value in sync while editing HTML source.
                    surfaceEl?.addEventListener('input', (event) => {
                        if (event.target?.classList?.contains('ems-rich-editor__codeview')) {
                            textarea.value = event.target.value;
                            textarea.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                },
                init_instance_callback: (editor) => {
                    const readEditorHtml = () => {
                        if (wrapper?.classList.contains('is-codeview')) {
                            const panel = wrapper.querySelector('.ems-rich-editor__codeview');
                            if (panel) return panel.value;
                        }
                        return editor.getContent();
                    };
                    const adapter = {
                        id: textarea.id,
                        input: textarea,
                        host: wrapper,
                        editor,
                        isFallback: false,
                        mode: uiMode,
                        getData: () => readEditorHtml(),
                        setData: (value) => {
                            editor.setContent(String(value ?? ''));
                            textarea.value = editor.getContent();
                            const panel = wrapper?.querySelector('.ems-rich-editor__codeview');
                            if (panel && wrapper.classList.contains('is-codeview')) {
                                panel.value = textarea.value;
                            }
                        },
                        sync: () => {
                            textarea.value = readEditorHtml();
                            return textarea.value;
                        },
                        onChange: (callback) => {
                            if (typeof callback !== 'function') return;
                            editor.on('change input undo redo SetContent', () => callback(readEditorHtml()));
                        },
                        focus: () => editor.focus(),
                        destroy: async () => {
                            if (wrapper?.classList.contains('is-codeview')) {
                                exitCodeView(editor, wrapper, null);
                            }
                            if (getFullscreenElement() === wrapper?.querySelector?.('.ems-rich-editor__surface')) {
                                setFullscreenPageState(false);
                            }
                            hideProgress(wrapper);
                            editor.destroy();
                            registry.delete(textarea.id);
                            wrapper?.classList.remove('is-ready', 'is-focused', 'is-codeview');
                        },
                    };

                    registry.set(textarea.id, adapter);
                    resolve(adapter);
                },
            });
        });
    }

    function createFallbackAdapter(textarea, wrapper) {
        wrapper?.classList.remove('is-ready');
        const adapter = {
            id: textarea.id,
            input: textarea,
            host: wrapper,
            isFallback: true,
            getData: () => textarea.value,
            setData: (value) => {
                textarea.value = String(value ?? '');
            },
            sync: () => textarea.value,
            onChange: (callback) => {
                if (typeof callback !== 'function') return;
                textarea.addEventListener('input', () => callback(textarea.value));
            },
            focus: () => textarea.focus(),
            destroy: () => registry.delete(textarea.id),
        };
        registry.set(textarea.id, adapter);
        return adapter;
    }

    async function mountFromWrapper(wrapper) {
        const inputId = wrapper.getAttribute('data-editor-input');
        const textarea = inputId
            ? document.getElementById(inputId)
            : wrapper.querySelector('[data-ems-rich-textarea], textarea');

        if (!textarea) return null;
        return mountTextarea(textarea, { wrapper });
    }

    async function initAll(root = document) {
        ensureEditorCss();
        const wrappers = [...root.querySelectorAll('[data-ems-rich-editor]')];
        const adapters = [];

        for (const wrapper of wrappers) {
            const adapter = await mountFromWrapper(wrapper);
            if (adapter) adapters.push(adapter);
        }

        const legacy = [...root.querySelectorAll('textarea[data-rich-text]')];
        for (const textarea of legacy) {
            if (registry.has(textarea.id)) continue;
            const adapter = await mountTextarea(textarea, {
                height: Number.parseInt(textarea.dataset.editorHeight || '220', 10),
                mode: textarea.dataset.editorMode || 'header',
                toolbar: textarea.dataset.editorToolbar || undefined,
            });
            if (adapter) adapters.push(adapter);
        }

        return registry;
    }

    function get(id) {
        return registry.get(id) || null;
    }

    function syncAll() {
        registry.forEach((adapter) => adapter.sync());
    }

    async function destroy(id) {
        const adapter = registry.get(id);
        if (adapter?.destroy) {
            await adapter.destroy();
        }
    }

    async function remountAllForTheme() {
        const snapshots = [];
        registry.forEach((adapter) => {
            if (!adapter?.input?.id) return;
            snapshots.push({
                id: adapter.input.id,
                content: adapter.getData?.() ?? adapter.input.value ?? '',
                wrapper: adapter.host || adapter.input.closest('[data-ems-rich-editor]'),
            });
        });

        for (const snap of snapshots) {
            await destroy(snap.id);
            const textarea = document.getElementById(snap.id);
            if (!textarea) continue;
            if (snap.content != null) textarea.value = snap.content;
            await mountTextarea(textarea, { wrapper: snap.wrapper });
        }
    }

    let themeRemountTimer = null;
    let lastDark = isDarkMode();
    function scheduleThemeRemount(force = false) {
        const nextDark = isDarkMode();
        if (!force && nextDark === lastDark) return;
        lastDark = nextDark;
        if (themeRemountTimer) window.clearTimeout(themeRemountTimer);
        themeRemountTimer = window.setTimeout(() => {
            remountAllForTheme().catch(() => {
                // ignore remount errors
            });
        }, 80);
    }

    if (!global.__emsRichEditorThemeBound) {
        global.__emsRichEditorThemeBound = true;
        window.addEventListener('ems:themechange', () => scheduleThemeRemount(true));
        const root = document.documentElement;
        const observer = new MutationObserver(() => scheduleThemeRemount(false));
        observer.observe(root, { attributes: true, attributeFilter: ['class'] });
    }

    global.EmsRichTextEditor = {
        initAll,
        mount: mountTextarea,
        mountWrapper: mountFromWrapper,
        get,
        syncAll,
        destroy,
        remountAllForTheme,
        uploadFile,
        presets: PRESET_TOOLBARS,
    };
}(window));
