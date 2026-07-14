(function registerEmsRichTextEditor(global) {
    const DEFAULT_PLUGINS = [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons',
        'codesample', 'pagebreak', 'nonbreaking', 'directionality',
    ];

    // Dense single-surface toolbar (no empty menubar gap).
    const DEFAULT_TOOLBAR = [
        'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough forecolor backcolor',
        '| alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent',
        '| blockquote codesample hr | link image media attachment table | emoticons charmap removeformat',
        '| searchreplace code preview fullscreen',
    ].join(' ');

    const PRESET_TOOLBARS = {
        full: DEFAULT_TOOLBAR,
        standard: 'undo redo | blocks fontsize | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist outdent indent | link image table | searchreplace code preview fullscreen',
        compact: 'undo redo | bold italic underline | bullist numlist | link image | removeformat',
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
        link.href = href + '?v=3';
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
        bar.style.transform = `scaleX(${Math.max(0.05, Math.min(1, percent / 100))})`;
        if (text && label) text.textContent = label;
    }

    function hideProgress(wrapper) {
        const progress = wrapper?.querySelector('[data-editor-progress]');
        if (progress) progress.hidden = true;
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
                hideProgress(wrapper);
                let payload = null;
                try {
                    payload = JSON.parse(xhr.responseText || '{}');
                } catch {
                    payload = null;
                }

                if (xhr.status >= 200 && xhr.status < 300 && payload?.location) {
                    resolve(payload);
                    return;
                }

                reject(new Error(parseUploadError(xhr, payload)));
            };

            xhr.onerror = () => {
                hideProgress(wrapper);
                reject(new Error('Network error while uploading.'));
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
        const dims = (width && height) ? ` width="${width}" height="${height}"` : '';
        return `<img src="${src}" alt="${alt}"${dims} />`;
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

    /**
     * Open crop/rotate modal. Resolves { original, adjusted } or null if cancelled.
     */
    function openImageAdjuster(file) {
        return new Promise(async (resolve) => {
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
                        <button type="button" class="ems-image-adjust__icon" data-adjust-cancel aria-label="Close">&times;</button>
                    </div>
                    <div class="ems-image-adjust__stage">
                        <img src="${objectUrl}" alt="Adjust preview" data-adjust-image>
                    </div>
                    <div class="ems-image-adjust__toolbar">
                        <button type="button" data-adjust-action="rotate-left" title="Rotate left">↺</button>
                        <button type="button" data-adjust-action="rotate-right" title="Rotate right">↻</button>
                        <button type="button" data-adjust-action="zoom-in" title="Zoom in">+</button>
                        <button type="button" data-adjust-action="zoom-out" title="Zoom out">−</button>
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
            });
        });
    }

    function buildFilePicker(uploadUrl, wrapper) {
        return (callback, value, meta) => {
            const input = document.createElement('input');
            input.type = 'file';

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
                    if (meta.filetype === 'image' || file.type.startsWith('image/')) {
                        const adjustedPair = await openImageAdjuster(file);
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

                    const kind = meta.filetype === 'media' ? 'video' : 'file';
                    const prepared = await prepareUploadFile(file, kind, wrapper);
                    const payload = await uploadFile({
                        file: prepared,
                        kind,
                        uploadUrl,
                        wrapper,
                        filename: prepared.name || file.name,
                    });

                    if (meta.filetype === 'file') {
                        callback(resolveMediaUrl(payload.location), {
                            text: payload.name || prepared.name || file.name,
                            title: payload.name || prepared.name || file.name,
                        });
                    } else {
                        callback(resolveMediaUrl(payload.location), { title: payload.name || prepared.name || file.name });
                        notify('success', 'Media uploaded successfully.');
                    }
                } catch (error) {
                    notify('error', error.message || 'Upload failed.');
                }
            });

            input.click();
        };
    }

    function registerChecklistButton(editor) {
        editor.ui.registry.addToggleButton('checklist', {
            text: 'Checklist',
            tooltip: 'Insert checklist',
            onAction: () => {
                editor.insertContent('<ul data-ems-checklist="true"><li>Checklist item</li><li data-checked="true">Completed item</li></ul><p></p>');
            },
        });

        editor.ui.registry.addButton('attachment', {
            text: 'Attach',
            tooltip: 'Upload image or file',
            onAction: () => {
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
                            const adjustedPair = await openImageAdjuster(file);
                            if (!adjustedPair) return;
                            const payload = await uploadImagePair({
                                original: adjustedPair.original,
                                adjusted: adjustedPair.adjusted,
                                uploadUrl,
                                wrapper,
                                displayName: file.name,
                            });
                            editor.insertContent(imageHtml(payload, file.name));
                            notify('success', 'Image inserted successfully.');
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
                        if (kind === 'video') {
                            editor.insertContent(
                                `<video controls src="${resolveMediaUrl(payload.location)}" style="max-width:100%"></video>`
                            );
                        } else {
                            editor.insertContent(
                                `<p><a href="${resolveMediaUrl(payload.location)}" target="_blank" rel="noopener">${editor.dom.encode(name)}</a></p>`
                            );
                        }
                        notify('success', 'Attachment uploaded successfully.');
                    } catch (error) {
                        notify('error', error.message || 'Upload failed.');
                    }
                });
                input.click();
            },
        });
    }

    function setFullscreenPageState(active) {
        document.documentElement.classList.toggle('ems-editor-fullscreen', active);
        document.body.classList.toggle('ems-editor-fullscreen', active);
    }

    function contentStyle(isDark) {
        const bg = isDark ? '#0f172a' : '#ffffff';
        const color = isDark ? '#e2e8f0' : '#0f172a';
        const link = isDark ? '#93c5fd' : '#2563eb';
        return `
            body {
                font-family: Inter, Segoe UI, Helvetica, Arial, sans-serif;
                font-size: 15px;
                line-height: 1.6;
                color: ${color};
                background: ${bg};
                margin: 12px 14px;
            }
            a { color: ${link}; }
            img, video { max-width: 100%; height: auto; border-radius: 6px; }
            table { border-collapse: collapse; width: 100%; }
            table td, table th { border: 1px solid #94a3b8; padding: 6px 8px; }
            ul[data-ems-checklist] { list-style: none; padding-left: 0; }
            ul[data-ems-checklist] li { position: relative; padding-left: 1.6em; margin: 0.35em 0; }
            ul[data-ems-checklist] li::before { content: '☐'; position: absolute; left: 0; }
            ul[data-ems-checklist] li[data-checked="true"]::before { content: '☑'; }
            pre { background: ${isDark ? '#1e293b' : '#f1f5f9'}; padding: 10px; border-radius: 8px; overflow: auto; }
            blockquote { border-left: 4px solid #94a3b8; margin-left: 0; padding-left: 12px; color: #64748b; }
        `;
    }

    function isDarkMode() {
        return document.documentElement.classList.contains('dark')
            || document.body.classList.contains('dark');
    }

    function resolveToolbar(wrapper) {
        const custom = (wrapper.getAttribute('data-editor-toolbar') || '').trim();
        if (custom) return custom;
        const preset = wrapper.getAttribute('data-editor-preset') || 'full';
        return PRESET_TOOLBARS[preset] || DEFAULT_TOOLBAR;
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
        const toolbar = options.toolbar || (wrapper ? resolveToolbar(wrapper) : DEFAULT_TOOLBAR);
        const preset = options.preset || wrapper?.getAttribute('data-editor-preset') || 'full';
        const placeholder = options.placeholder
            || wrapper?.getAttribute('data-editor-placeholder')
            || textarea.getAttribute('placeholder')
            || '';

        const skin = isDarkMode() ? 'oxide-dark' : 'oxide';
        const contentCss = isDarkMode() ? 'dark' : 'default';
        // Compact keeps no menu; full/standard also skip menubar so toolbar fills width.
        const menubar = options.menubar !== undefined
            ? options.menubar
            : false;

        return new Promise((resolve) => {
            tinymce.init({
                target: textarea,
                license_key: 'gpl',
                base_url: cdnBase.replace(/\/$/, ''),
                suffix: '.min',
                promotion: false,
                branding: false,
                menubar,
                plugins: options.plugins || DEFAULT_PLUGINS,
                toolbar,
                toolbar_mode: preset === 'compact' ? 'scrolling' : 'wrap',
                toolbar_sticky: false,
                height: Math.max(height, 220),
                min_height: Math.max(Math.min(height, 180), 160),
                placeholder,
                readonly,
                skin,
                content_css: contentCss,
                content_style: contentStyle(isDarkMode()),
                font_family_formats:
                    'Inter=Inter,sans-serif; Arial=arial,helvetica,sans-serif; Georgia=georgia,serif; Courier New=courier new,courier,monospace; Times New Roman=times new roman,times,serif; Verdana=verdana,geneva,sans-serif',
                font_size_formats: '10px 12px 14px 15px 16px 18px 20px 24px 28px 32px 36px',
                block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Preformatted=pre',
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
                relative_urls: false,
                remove_script_host: false,
                convert_urls: true,
                paste_data_images: true,
                sandbox_iframes: true,
                // Work inside admin layout overflow containers.
                fullscreen_native: false,
                setup: (editor) => {
                    registerChecklistButton(editor);

                    editor.on('init', () => {
                        wrapper?.classList.add('is-ready');
                        textarea.removeAttribute('required');

                        // Route TinyMCE error/warning notifications into EmsToast.
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
                    });

                    editor.on('FullscreenStateChanged', (event) => {
                        setFullscreenPageState(Boolean(event.state));
                    });

                    editor.on('change input undo redo SetContent', () => {
                        textarea.value = editor.getContent();
                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    });

                    editor.on('focus', () => wrapper?.classList.add('is-focused'));
                    editor.on('blur', () => {
                        wrapper?.classList.remove('is-focused');
                        textarea.value = editor.getContent();
                    });
                },
                init_instance_callback: (editor) => {
                    const adapter = {
                        id: textarea.id,
                        input: textarea,
                        host: wrapper,
                        editor,
                        isFallback: false,
                        getData: () => editor.getContent(),
                        setData: (value) => {
                            editor.setContent(String(value ?? ''));
                            textarea.value = editor.getContent();
                        },
                        sync: () => {
                            textarea.value = editor.getContent();
                            return textarea.value;
                        },
                        onChange: (callback) => {
                            if (typeof callback !== 'function') return;
                            editor.on('change input undo redo SetContent', () => callback(editor.getContent()));
                        },
                        focus: () => editor.focus(),
                        destroy: async () => {
                            setFullscreenPageState(false);
                            editor.destroy();
                            registry.delete(textarea.id);
                            wrapper?.classList.remove('is-ready', 'is-focused');
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
                toolbar: textarea.dataset.editorToolbar || PRESET_TOOLBARS.compact,
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

    global.EmsRichTextEditor = {
        initAll,
        mount: mountTextarea,
        mountWrapper: mountFromWrapper,
        get,
        syncAll,
        destroy,
        uploadFile,
        presets: PRESET_TOOLBARS,
    };
}(window));
