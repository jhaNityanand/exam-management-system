(function registerEmsRichTextEditor(global) {
    // Linear / Jira style editor: one simple header row with every action,
    // clean writing area below. No floating/overlapping quickbars.
    const DEFAULT_PLUGINS = [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount',
        'codesample', 'nonbreaking', 'directionality',
    ];

    // Single-row header — TinyMCE collapses overflow behind a "…" button
    // (toolbar_mode: 'floating'), so every action stays reachable without
    // wrapping into extra rows.
    // "fullscreen" is placed first: toolbar_mode "floating" fills the row
    // left-to-right and pushes overflow into the "…" drawer starting from
    // the right, so a trailing fullscreen button could get buried and
    // become unclickable on narrower editors. Keeping it first guarantees
    // it's always visible and reachable.
    const HEADER_TOOLBAR = [
        'fullscreen', '|',
        'fontfamily', 'fontsize', '|',
        'blocks', '|',
        'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', '|',
        'forecolor', 'backcolor', '|',
        'align', '|',
        'bullist', 'numlist', 'checklist', 'outdent', 'indent', '|',
        'blockquote', 'codesample', '|',
        'link', 'emsimage', 'table', 'media', 'attachment', '|',
        'removeformat',
    ].join(' ');

    const COMPACT_TOOLBAR = [
        'fullscreen', '|',
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'forecolor', '|',
        'bullist', 'numlist', '|',
        'link', 'emsimage', '|',
        'removeformat',
    ].join(' ');

    const PRESET_TOOLBARS = {
        header: HEADER_TOOLBAR,
        compact: COMPACT_TOOLBAR,
        full: HEADER_TOOLBAR,
        standard: 'blocks | bold italic underline | bullist numlist | link emsimage table | removeformat | fullscreen',
    };

    // Legacy mode names are mapped onto the current header/compact UI.
    const MODE_ALIASES = {
        linear: 'header',
        bubble: 'header',
        classic: 'header',
        full: 'header',
        standard: 'header',
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
        link.href = href + '?v=7';
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
     * Prefers the shared GalleryImageEditor when available.
     */
    function openImageAdjuster(file) {
        return new Promise(async (resolve) => {
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
        const adjustedPair = await openImageAdjuster(file);
        if (!adjustedPair) return false;
        const payload = await uploadImagePair({
            original: adjustedPair.original,
            adjusted: adjustedPair.adjusted,
            uploadUrl,
            wrapper,
            displayName: file.name,
        });
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
                if (kind === 'video') {
                    editor.insertContent(
                        `<video controls src="${resolveMediaUrl(payload.location)}" style="max-width:100%"></video>`
                    );
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
            { text: 'Mention', value: 'mention', meta: 'Mention someone with @' },
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
                    mention: () => editor.insertContent('@'),
                };

                (map[value] || (() => {}))();
            },
        });
    }

    function registerMentions(editor, wrapper) {
        const resolveCandidates = () => {
            if (Array.isArray(global.emsEditorMentions)) return global.emsEditorMentions;
            const raw = wrapper?.getAttribute('data-editor-mentions') || '';
            if (!raw) return [];
            try {
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [];
            } catch {
                return [];
            }
        };

        editor.ui.registry.addAutocompleter('ems-mentions', {
            trigger: '@',
            minChars: 0,
            columns: 1,
            fetch: (pattern) => {
                const q = String(pattern || '').toLowerCase();
                const people = resolveCandidates()
                    .filter((person) => {
                        const name = String(person.name || person.label || '').toLowerCase();
                        const email = String(person.email || '').toLowerCase();
                        return !q || name.includes(q) || email.includes(q);
                    })
                    .slice(0, 8)
                    .map((person) => ({
                        type: 'autocompleteitem',
                        value: String(person.id || person.name || person.label || ''),
                        text: person.name || person.label || person.email || 'User',
                        meta: person.email || 'Mention',
                    }));

                if (!people.length && !q) {
                    return Promise.resolve([{
                        type: 'autocompleteitem',
                        value: '',
                        text: 'No people available',
                        meta: 'Mentions list is empty',
                    }]);
                }

                return Promise.resolve(people);
            },
            onAction: (api, rng, value) => {
                editor.selection.setRng(rng);
                api.hide();
                if (!value) {
                    editor.insertContent('');
                    return;
                }
                const people = resolveCandidates();
                const person = people.find((p) => String(p.id || p.name || p.label) === String(value));
                const label = person?.name || person?.label || value;
                const safeLabel = editor.dom.encode(String(label));
                const safeId = editor.dom.encode(String(value));
                editor.insertContent(
                    `<span class="ems-mention" data-mention-id="${safeId}" contenteditable="false">@${safeLabel}</span>&nbsp;`
                );
            },
        });
    }

    function registerCustomButtons(editor) {
        editor.ui.registry.addIcon(
            'ems-checklist',
            '<svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M9.5 16.2 5.8 12.5l1.4-1.4 2.3 2.3 6.3-6.3 1.4 1.4-7.7 7.7zM4 19h16v1.5H4V19zm0-4.5h2V16H4v-1.5zm0-4h2V12H4v-1.5zm0-4h2V8H4V6.5z"/></svg>'
        );
        editor.ui.registry.addIcon(
            'ems-attachment',
            '<svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M16.5 6.5v8.8a4.5 4.5 0 1 1-9 0V6.2a3.2 3.2 0 1 1 6.4 0v8.6a1.9 1.9 0 1 1-3.8 0V7.5h1.5v7.3a.4.4 0 0 0 .8 0V6.2a1.7 1.7 0 1 0-3.4 0v9.1a3 3 0 1 0 6 0V6.5h1.5z"/></svg>'
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
        const mentionBg = isDark ? 'rgba(45, 212, 191, 0.16)' : 'rgba(13, 148, 136, 0.12)';
        return `
            html, body {
                background: ${bg} !important;
                scrollbar-color: ${border} ${bg};
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
            }
            ::-webkit-scrollbar-thumb:hover {
                background: ${muted};
            }
            body {
                font-family: "Segoe UI", Inter, system-ui, -apple-system, sans-serif;
                font-size: 15px;
                line-height: 1.65;
                color: ${color};
                margin: 0;
                padding: 14px 16px 20px;
                min-height: 140px;
                text-align: left;
                direction: ltr;
            }
            p, h1, h2, h3, h4, li, blockquote {
                text-align: left;
            }
            p { margin: 0 0 0.75em; }
            h1, h2, h3, h4 {
                font-weight: 650;
                line-height: 1.3;
                margin: 1.1em 0 0.45em;
                letter-spacing: -0.01em;
            }
            h1 { font-size: 1.65em; }
            h2 { font-size: 1.35em; }
            h3 { font-size: 1.15em; }
            a { color: ${link}; text-decoration: underline; text-underline-offset: 2px; }
            img, video {
                max-width: 100%;
                height: auto;
                border-radius: 10px;
                margin: 0.5em 0;
                box-shadow: 0 0 0 1px ${border};
            }
            table { border-collapse: collapse; width: 100%; margin: 0.75em 0; }
            table td, table th {
                border: 1px solid ${border};
                padding: 8px 10px;
                vertical-align: top;
            }
            table th { background: ${codeBg}; font-weight: 600; }
            ul, ol { margin: 0.4em 0 0.8em; padding-left: 1.4em; }
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
            }
            code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
            blockquote {
                border-left: 3px solid ${link};
                margin: 0.75em 0;
                padding: 0.15em 0 0.15em 14px;
                color: ${muted};
            }
            hr {
                border: 0;
                border-top: 1px solid ${border};
                margin: 1.25em 0;
            }
            .ems-mention {
                display: inline-flex;
                align-items: center;
                padding: 0 0.4em;
                border-radius: 999px;
                background: ${mentionBg};
                color: ${link};
                font-weight: 600;
                white-space: nowrap;
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
        return MODE_ALIASES[raw] || (raw === 'compact' ? 'compact' : 'header');
    }

    function resolveToolbar(wrapper, options = {}) {
        const custom = options.toolbar !== undefined
            ? options.toolbar
            : (wrapper?.getAttribute('data-editor-toolbar') || '').trim();
        if (custom && custom !== 'false') return custom;

        const mode = resolveUiMode(wrapper, options);
        return PRESET_TOOLBARS[mode] || HEADER_TOOLBAR;
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
        const isCompact = uiMode === 'compact';
        const toolbar = resolveToolbar(wrapper, options);
        const placeholder = options.placeholder
            || wrapper?.getAttribute('data-editor-placeholder')
            || textarea.getAttribute('placeholder')
            || 'Write a description, or type / for commands…';

        const dark = isDarkMode();
        const skin = dark ? 'oxide-dark' : 'oxide';
        const contentCssName = dark ? 'dark' : 'default';
        const base = cdnBase.replace(/\/$/, '');
        const menubar = options.menubar !== undefined ? options.menubar : false;

        hideProgress(wrapper);
        wrapper?.classList.toggle('ems-rich-editor--header', !isCompact);
        wrapper?.classList.toggle('ems-rich-editor--compact', isCompact);
        wrapper?.classList.remove('ems-rich-editor--linear', 'ems-rich-editor--classic', 'ems-rich-editor--bubble');

        return new Promise((resolve) => {
            tinymce.init({
                target: textarea,
                license_key: 'gpl',
                base_url: base,
                suffix: '.min',
                promotion: false,
                branding: false,
                menubar,
                statusbar: !isCompact,
                elementpath: false,
                plugins: options.plugins || DEFAULT_PLUGINS,
                toolbar,
                toolbar_mode: 'floating',
                toolbar_sticky: false,
                fixed_toolbar_container: false,
                quickbars_selection_toolbar: false,
                quickbars_insert_toolbar: false,
                height: Math.max(height, isCompact ? 150 : 220),
                min_height: Math.max(Math.min(height, 180), isCompact ? 130 : 160),
                placeholder,
                readonly,
                skin,
                skin_url: `${base}/skins/ui/${skin}`,
                content_css: `${base}/skins/content/${contentCssName}/content.min.css`,
                content_style: contentStyle(dark),
                font_family_formats:
                    'Inter=Inter,sans-serif; Arial=arial,helvetica,sans-serif; Georgia=georgia,serif; Courier New=courier new,courier,monospace; Times New Roman=times new roman,times,serif; Verdana=verdana,geneva,sans-serif',
                font_size_formats: '10px 12px 14px 15px 16px 18px 20px 24px 28px 32px 36px',
                block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Preformatted=pre',
                color_map: [
                    '#0F172A', 'Slate 900', '#334155', 'Slate 700', '#64748B', 'Slate 500',
                    '#94A3B8', 'Slate 400', '#F8FAFC', 'White',
                    '#0D9488', 'Teal', '#0EA5E9', 'Sky', '#6366F1', 'Indigo', '#8B5CF6', 'Violet', '#EC4899', 'Pink',
                    '#EF4444', 'Red', '#F97316', 'Orange', '#F59E0B', 'Amber', '#EAB308', 'Yellow', '#22C55E', 'Green',
                ],
                color_cols: 5,
                custom_colors: true,
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
                fullscreen_native: false,
                setup: (editor) => {
                    registerCustomButtons(editor);
                    registerSlashCommands(editor);
                    registerMentions(editor, wrapper);

                    const forceContentColors = () => {
                        const body = editor.getBody?.();
                        if (!body) return;
                        const nowDark = isDarkMode();
                        editor.dom.setStyles(body, {
                            'background-color': nowDark ? '#0f172a' : '#ffffff',
                            color: nowDark ? '#e2e8f0' : '#0f172a',
                        });
                        const doc = editor.getDoc?.();
                        if (doc?.documentElement) {
                            doc.documentElement.style.backgroundColor = nowDark ? '#0f172a' : '#ffffff';
                        }
                    };

                    editor.on('init', () => {
                        wrapper?.classList.add('is-ready');
                        hideProgress(wrapper);
                        textarea.removeAttribute('required');
                        forceContentColors();

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

                    // Fullscreen reliably escapes any ancestor with overflow/scroll
                    // clipping (the admin panel's scroll containers) by moving the
                    // editor surface to <body> for the duration, then restoring it.
                    // The reference is captured once up front — once the surface is
                    // reparented to <body> it's no longer inside `wrapper`, so we
                    // can't re-query it via wrapper.querySelector on the way out.
                    const surfaceEl = wrapper?.querySelector('.ems-rich-editor__surface') || null;
                    let fullscreenAnchor = null;
                    const exitFullscreenReattach = () => {
                        if (surfaceEl && fullscreenAnchor?.parentNode) {
                            fullscreenAnchor.parentNode.insertBefore(surfaceEl, fullscreenAnchor);
                        }
                        fullscreenAnchor?.remove();
                        fullscreenAnchor = null;
                        surfaceEl?.classList.remove('ems-rich-editor__surface--fullscreen');
                    };

                    editor.on('FullscreenStateChanged', (event) => {
                        const active = Boolean(event.state);
                        setFullscreenPageState(active);
                        if (!surfaceEl) return;

                        if (active) {
                            fullscreenAnchor = document.createComment('ems-editor-fullscreen-anchor');
                            surfaceEl.parentNode.insertBefore(fullscreenAnchor, surfaceEl);
                            document.body.appendChild(surfaceEl);
                            surfaceEl.classList.add('ems-rich-editor__surface--fullscreen');
                        } else {
                            exitFullscreenReattach();
                        }
                    });

                    editor.on('remove', exitFullscreenReattach);

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
                        mode: uiMode,
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
                            hideProgress(wrapper);
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
