/**
 * Gallery image editor — crop, brightness, contrast, resize, rotate, flip, shapes, compress.
 * Lazy-loads Cropper.js when needed. Modal markup: #gallery-image-editor (shared partial).
 */
(function (global) {
    'use strict';

    const DEFAULTS = {
        quality: 0.85,
        brightness: 0,
        contrast: 0,
        maxEdge: 2400,
    };

    let cropperLoading = null;

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const existing = document.querySelector(`script[src="${src}"]`);
            if (existing) {
                if (global.Cropper) {
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
            script.addEventListener('load', () => resolve(), { once: true });
            script.addEventListener('error', reject, { once: true });
            document.head.appendChild(script);
        });
    }

    async function ensureCropper() {
        if (typeof global.Cropper === 'function') {
            return global.Cropper;
        }
        if (!cropperLoading) {
            cropperLoading = loadScript('https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js')
                .then(() => global.Cropper);
        }
        return cropperLoading;
    }

    function loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error('Failed to load image for editing.'));
            img.src = src;
        });
    }

    function canvasToBlob(canvas, type, quality) {
        return new Promise((resolve) => {
            canvas.toBlob((blob) => resolve(blob), type || 'image/jpeg', quality);
        });
    }

    function filterCss(brightness, contrast) {
        return `brightness(${1 + brightness / 100}) contrast(${1 + contrast / 100})`;
    }

    /**
     * Open the gallery image editor modal.
     * @returns {Promise<File|null>} edited file, or null if skipped/cancelled
     */
    async function openGalleryImageEditor(options) {
        const root = options.root || document;
        const modal = (root.querySelector ? root : document).querySelector('#gallery-image-editor');
        if (!modal) {
            throw new Error('Image editor markup is missing.');
        }

        await ensureCropper();
        if (typeof global.Cropper !== 'function') {
            throw new Error('Cropper.js is required for the image editor.');
        }

        const els = {
            stage: modal.querySelector('[data-gie-stage]'),
            img: modal.querySelector('[data-gie-image]'),
            title: modal.querySelector('[data-gie-title]'),
            meta: modal.querySelector('[data-gie-meta]'),
            brightness: modal.querySelector('[data-gie-brightness]'),
            brightnessVal: modal.querySelector('[data-gie-brightness-val]'),
            contrast: modal.querySelector('[data-gie-contrast]'),
            contrastVal: modal.querySelector('[data-gie-contrast-val]'),
            quality: modal.querySelector('[data-gie-quality]'),
            qualityVal: modal.querySelector('[data-gie-quality-val]'),
            width: modal.querySelector('[data-gie-width]'),
            height: modal.querySelector('[data-gie-height]'),
            lockRatio: modal.querySelector('[data-gie-lock-ratio]'),
            shapeGroup: modal.querySelector('[data-gie-shapes]'),
            preview: modal.querySelector('[data-gie-preview]'),
            previewWrap: modal.querySelector('[data-gie-preview-wrap]'),
        };

        const state = {
            cropper: null,
            naturalWidth: 0,
            naturalHeight: 0,
            aspect: NaN,
            shape: 'rectangle',
            flipX: 1,
            flipY: 1,
            brightness: DEFAULTS.brightness,
            contrast: DEFAULTS.contrast,
            quality: DEFAULTS.quality,
        };

        els.title.textContent = options.name || 'Edit image';
        els.img.removeAttribute('src');
        els.previewWrap.hidden = true;
        els.preview.removeAttribute('src');

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        const sourceImg = await loadImage(options.src);
        state.naturalWidth = sourceImg.naturalWidth;
        state.naturalHeight = sourceImg.naturalHeight;
        state.aspect = state.naturalWidth / Math.max(1, state.naturalHeight);

        els.img.src = options.src;
        els.width.value = String(state.naturalWidth);
        els.height.value = String(state.naturalHeight);
        els.brightness.value = String(DEFAULTS.brightness);
        els.quality.value = String(Math.round(DEFAULTS.quality * 100));
        els.brightnessVal.textContent = '0';
        els.qualityVal.textContent = `${Math.round(DEFAULTS.quality * 100)}%`;
        if (els.contrast) {
            els.contrast.value = String(DEFAULTS.contrast);
            els.contrastVal.textContent = '0';
        }
        els.meta.textContent = `${state.naturalWidth} × ${state.naturalHeight}`;
        els.lockRatio.checked = true;

        await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));

        state.cropper = new global.Cropper(els.img, {
            viewMode: 1,
            autoCropArea: 1,
            background: false,
            responsive: true,
            movable: true,
            zoomable: true,
            rotatable: true,
            scalable: true,
            aspectRatio: NaN,
            ready() {
                applyFilters();
            },
        });

        function applyFilters() {
            const cropImg = els.stage.querySelector('.cropper-view-box img, .cropper-canvas img');
            const targets = [els.img];
            if (cropImg) targets.push(cropImg);
            const css = filterCss(state.brightness, state.contrast);
            targets.forEach((node) => {
                node.style.filter = css;
            });
        }

        function setShape(shape) {
            state.shape = shape;
            els.shapeGroup.querySelectorAll('[data-shape]').forEach((btn) => {
                btn.classList.toggle('is-active', btn.getAttribute('data-shape') === shape);
            });
            if (!state.cropper) return;
            if (shape === 'square' || shape === 'circle') {
                state.cropper.setAspectRatio(1);
            } else {
                state.cropper.setAspectRatio(NaN);
            }
            els.stage.classList.toggle('is-circle-mask', shape === 'circle');
        }

        function syncSizeFromCrop() {
            if (!state.cropper) return;
            const data = state.cropper.getData(true);
            els.width.value = String(Math.max(1, Math.round(data.width)));
            els.height.value = String(Math.max(1, Math.round(data.height)));
        }

        function onWidthInput() {
            const w = Math.max(1, parseInt(els.width.value, 10) || 1);
            if (els.lockRatio.checked && state.aspect) {
                els.height.value = String(Math.max(1, Math.round(w / state.aspect)));
            }
        }

        function onHeightInput() {
            const h = Math.max(1, parseInt(els.height.value, 10) || 1);
            if (els.lockRatio.checked && state.aspect) {
                els.width.value = String(Math.max(1, Math.round(h * state.aspect)));
            }
        }

        async function buildExportCanvas() {
            if (!state.cropper) throw new Error('Editor is not ready.');

            const source = state.cropper.getCroppedCanvas({
                maxWidth: DEFAULTS.maxEdge,
                maxHeight: DEFAULTS.maxEdge,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            if (!source) throw new Error('Unable to crop image.');

            const targetW = Math.max(1, parseInt(els.width.value, 10) || source.width);
            const targetH = Math.max(1, parseInt(els.height.value, 10) || source.height);

            const canvas = document.createElement('canvas');
            canvas.width = targetW;
            canvas.height = targetH;
            const ctx = canvas.getContext('2d');

            ctx.filter = filterCss(state.brightness, state.contrast);

            if (state.shape === 'circle') {
                ctx.beginPath();
                ctx.arc(targetW / 2, targetH / 2, Math.min(targetW, targetH) / 2, 0, Math.PI * 2);
                ctx.closePath();
                ctx.clip();
            }

            ctx.drawImage(source, 0, 0, targetW, targetH);
            ctx.filter = 'none';

            return canvas;
        }

        async function refreshPreview() {
            const canvas = await buildExportCanvas();
            const blob = await canvasToBlob(
                canvas,
                state.shape === 'circle' ? 'image/png' : 'image/jpeg',
                state.quality
            );
            if (els.preview.src) URL.revokeObjectURL(els.preview.src);
            els.preview.src = URL.createObjectURL(blob);
            els.previewWrap.hidden = false;
            els.meta.textContent = `${canvas.width} × ${canvas.height} · ${Math.round(blob.size / 1024)} KB`;
            return { canvas, blob };
        }

        return new Promise((resolve) => {
            const cleanup = (result) => {
                modal.removeEventListener('click', onClick);
                els.brightness.removeEventListener('input', onBrightness);
                els.quality.removeEventListener('input', onQuality);
                if (els.contrast) els.contrast.removeEventListener('input', onContrast);
                els.width.removeEventListener('input', onWidthInput);
                els.height.removeEventListener('input', onHeightInput);
                if (state.cropper) {
                    state.cropper.destroy();
                    state.cropper = null;
                }
                if (els.preview.src) URL.revokeObjectURL(els.preview.src);
                els.img.removeAttribute('src');
                els.stage.classList.remove('is-circle-mask');
                modal.hidden = true;
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                resolve(result);
            };

            const onBrightness = () => {
                state.brightness = parseInt(els.brightness.value, 10) || 0;
                els.brightnessVal.textContent = String(state.brightness);
                applyFilters();
            };

            const onContrast = () => {
                if (!els.contrast) return;
                state.contrast = parseInt(els.contrast.value, 10) || 0;
                els.contrastVal.textContent = String(state.contrast);
                applyFilters();
            };

            const onQuality = () => {
                state.quality = Math.min(1, Math.max(0.4, (parseInt(els.quality.value, 10) || 85) / 100));
                els.qualityVal.textContent = `${Math.round(state.quality * 100)}%`;
            };

            const onClick = async (event) => {
                const actionBtn = event.target.closest('[data-gie-action]');
                const shapeBtn = event.target.closest('[data-shape]');

                if (shapeBtn) {
                    setShape(shapeBtn.getAttribute('data-shape') || 'rectangle');
                    return;
                }
                if (!actionBtn) {
                    if (event.target.closest('[data-gie-close]')) cleanup(null);
                    return;
                }

                const action = actionBtn.getAttribute('data-gie-action');
                try {
                    if (action === 'rotate-left') {
                        state.cropper.rotate(-90);
                        return;
                    }
                    if (action === 'rotate-right') {
                        state.cropper.rotate(90);
                        return;
                    }
                    if (action === 'flip-h') {
                        state.flipX *= -1;
                        state.cropper.scaleX(state.flipX);
                        return;
                    }
                    if (action === 'flip-v') {
                        state.flipY *= -1;
                        state.cropper.scaleY(state.flipY);
                        return;
                    }
                    if (action === 'reset') {
                        state.cropper.reset();
                        state.flipX = 1;
                        state.flipY = 1;
                        state.brightness = 0;
                        state.contrast = 0;
                        els.brightness.value = '0';
                        els.brightnessVal.textContent = '0';
                        if (els.contrast) {
                            els.contrast.value = '0';
                            els.contrastVal.textContent = '0';
                        }
                        els.width.value = String(state.naturalWidth);
                        els.height.value = String(state.naturalHeight);
                        setShape('rectangle');
                        applyFilters();
                        return;
                    }
                    if (action === 'preview') {
                        actionBtn.disabled = true;
                        await refreshPreview();
                        actionBtn.disabled = false;
                        return;
                    }
                    if (action === 'skip') {
                        const keep = options.originalFile || null;
                        if (keep) keep.__keepOriginal = true;
                        cleanup(keep);
                        return;
                    }
                    if (action === 'save') {
                        actionBtn.disabled = true;
                        actionBtn.textContent = 'Saving…';
                        syncSizeFromCrop();
                        const { blob, canvas } = await refreshPreview();
                        const ext = state.shape === 'circle' ? 'png' : 'jpg';
                        const base = (options.name || 'image').replace(/\.[^.]+$/, '');
                        const file = new File([blob], `${base}-edited.${ext}`, {
                            type: blob.type,
                            lastModified: Date.now(),
                        });
                        file.__editorMeta = { width: canvas.width, height: canvas.height };
                        cleanup(file);
                        return;
                    }
                } catch (error) {
                    actionBtn.disabled = false;
                    if (action === 'save') actionBtn.textContent = 'Save edited';
                    global.EmsToast?.error?.(error.message || 'Editor action failed.');
                }
            };

            modal.addEventListener('click', onClick);
            els.brightness.addEventListener('input', onBrightness);
            if (els.contrast) els.contrast.addEventListener('input', onContrast);
            els.quality.addEventListener('input', onQuality);
            els.width.addEventListener('input', onWidthInput);
            els.height.addEventListener('input', onHeightInput);
            setShape('rectangle');
            els.img.addEventListener('crop', syncSizeFromCrop);
        });
    }

    global.GalleryImageEditor = { open: openGalleryImageEditor };
})(typeof window !== 'undefined' ? window : globalThis);
