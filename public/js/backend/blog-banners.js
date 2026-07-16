/**
 * Multi-banner uploader for Blog create/edit.
 * Images only · DnD reorder · edit via GalleryImageEditor · gallery commit API.
 */
(function () {
    'use strict';

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || window.galleryCsrf
            || '';
    }

    function toast(type, message) {
        if (window.EmsToast?.[type]) window.EmsToast[type](message);
    }

    function initUploader(root) {
        if (!root || root.dataset.ready === '1') return;
        root.dataset.ready = '1';

        const name = root.dataset.name || 'banner_ids';
        const commitUrl = root.dataset.commitUrl || window.galleryCommitUrl;
        const dataUrl = root.dataset.galleryDataUrl || window.galleryDataUrl;
        const grid = root.querySelector('[data-banner-grid]');
        const empty = root.querySelector('[data-banner-empty]');
        const dropzone = root.querySelector('[data-banner-dropzone]');
        const fileInput = root.querySelector('[data-banner-file]');
        const progress = root.querySelector('[data-banner-progress]');
        const progressBar = root.querySelector('[data-banner-progress-bar]');
        const progressLabel = root.querySelector('[data-banner-progress-label]');
        const chooseBtn = root.querySelector('[data-banner-choose]');

        let dragCard = null;

        function selectedIds() {
            return [...grid.querySelectorAll('input[name="' + name + '[]"]')].map((el) => Number(el.value));
        }

        function syncEmpty() {
            const has = selectedIds().length > 0;
            if (empty) empty.hidden = has;
            grid.querySelectorAll('.blog-banner-card').forEach((card, index) => {
                const badge = card.querySelector('.blog-banner-card__badge');
                if (index === 0) {
                    if (!badge) {
                        const el = document.createElement('span');
                        el.className = 'blog-banner-card__badge';
                        el.textContent = 'Featured';
                        card.querySelector('.blog-banner-card__media')?.appendChild(el);
                    }
                } else if (badge) {
                    badge.remove();
                }
            });
        }

        function showProgress(pct, label) {
            if (!progress || !progressBar) return;
            progress.hidden = false;
            progressBar.style.transform = `scaleX(${Math.max(0.05, Math.min(1, pct / 100))})`;
            if (progressLabel && label) progressLabel.textContent = label;
        }

        function hideProgress() {
            if (progress) progress.hidden = true;
        }

        function createCard(item) {
            const card = document.createElement('article');
            card.className = 'blog-banner-card';
            card.dataset.bannerId = String(item.id);
            card.draggable = true;
            card.innerHTML = `
                <div class="blog-banner-card__media">
                    <img src="${item.file_url || item.url || ''}" alt="${(item.original_name || 'Banner').replace(/"/g, '&quot;')}">
                </div>
                <div class="blog-banner-card__actions">
                    <button type="button" class="blog-banner-icon-btn" data-banner-edit title="Edit" aria-label="Edit">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 00.707-.293l9.414-9.414a2 2 0 000-2.828l-2.172-2.172a2 2 0 00-2.828 0L4.293 14.707A1 1 0 004 15.414V20z"/></svg>
                    </button>
                    <button type="button" class="blog-banner-icon-btn blog-banner-icon-btn--danger" data-banner-remove title="Remove" aria-label="Remove">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/></svg>
                    </button>
                </div>
                <input type="hidden" name="${name}[]" value="${item.id}">
            `;
            return card;
        }

        function addItem(item) {
            if (!item?.id) return;
            if (selectedIds().includes(Number(item.id))) {
                toast('info', 'That banner is already selected.');
                return;
            }
            grid.appendChild(createCard(item));
            syncEmpty();
        }

        function commitFile(displayFile, originalFile) {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('file', displayFile);
                formData.append('module', 'blog');
                formData.append('source', 'picker');
                if (originalFile && originalFile !== displayFile) {
                    formData.append('original', originalFile);
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', commitUrl, true);
                xhr.setRequestHeader('X-CSRF-TOKEN', csrf());
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.onprogress = (event) => {
                    if (!event.lengthComputable) return;
                    const pct = Math.round((event.loaded / event.total) * 100);
                    showProgress(pct, `Uploading ${pct}%`);
                };
                xhr.onload = () => {
                    hideProgress();
                    let payload = null;
                    try { payload = JSON.parse(xhr.responseText || '{}'); } catch { /* ignore */ }
                    if (xhr.status >= 200 && xhr.status < 300 && payload?.data) {
                        resolve(payload.data);
                        return;
                    }
                    reject(new Error(payload?.message || `Upload failed (${xhr.status})`));
                };
                xhr.onerror = () => {
                    hideProgress();
                    reject(new Error('Network error while uploading.'));
                };
                showProgress(5, 'Uploading…');
                xhr.send(formData);
            });
        }

        async function processFiles(fileList) {
            const files = [...(fileList || [])].filter((f) => f && f.type.startsWith('image/'));
            if (!files.length) {
                toast('error', 'Only image files are allowed for banners.');
                return;
            }

            for (const file of files) {
                try {
                    let display = file;
                    let original = null;
                    if (window.GalleryImageEditor?.open) {
                        const objectUrl = URL.createObjectURL(file);
                        try {
                            const edited = await window.GalleryImageEditor.open({
                                src: objectUrl,
                                name: file.name,
                                root: document,
                                originalFile: file,
                            });
                            if (edited && !edited.__keepOriginal) {
                                display = edited;
                                original = file;
                            }
                        } finally {
                            URL.revokeObjectURL(objectUrl);
                        }
                    }
                    const item = await commitFile(display, original);
                    addItem(item);
                } catch (error) {
                    toast('error', error.message || 'Banner upload failed.');
                }
            }
        }

        async function openGalleryPicker() {
            const url = new URL(dataUrl, window.location.origin);
            url.searchParams.set('kind', 'image');
            url.searchParams.set('per_page', '48');
            try {
                const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('Failed to load gallery');
                const json = await res.json();
                const items = json.data || [];
                if (!items.length) {
                    toast('info', 'No images in gallery yet. Upload one first.');
                    return;
                }

                const overlay = document.createElement('div');
                overlay.className = 'blog-banner-picker-modal';
                overlay.innerHTML = `
                    <div class="blog-banner-picker-modal__backdrop" data-close></div>
                    <div class="blog-banner-picker-modal__panel" role="dialog" aria-modal="true">
                        <header class="blog-banner-picker-modal__header">
                            <h3>Select banner images</h3>
                            <button type="button" class="blog-banner-picker-modal__close" data-close aria-label="Close">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                            </button>
                        </header>
                        <div class="blog-banner-picker-modal__grid">
                            ${items.map((item) => `
                                <button type="button" class="blog-banner-picker-item" data-id="${item.id}" data-url="${item.file_url}" data-name="${(item.original_name || '').replace(/"/g, '&quot;')}">
                                    <img src="${item.file_url}" alt="">
                                </button>
                            `).join('')}
                        </div>
                        <footer class="blog-banner-picker-modal__footer">
                            <button type="button" class="panel-button-secondary" data-close>Done</button>
                        </footer>
                    </div>
                `;
                document.body.appendChild(overlay);
                document.body.classList.add('ems-modal-open');

                const close = () => {
                    overlay.remove();
                    document.body.classList.remove('ems-modal-open');
                };
                overlay.addEventListener('click', (event) => {
                    if (event.target.closest('[data-close]')) {
                        close();
                        return;
                    }
                    const itemBtn = event.target.closest('.blog-banner-picker-item');
                    if (!itemBtn) return;
                    addItem({
                        id: Number(itemBtn.dataset.id),
                        file_url: itemBtn.dataset.url,
                        original_name: itemBtn.dataset.name,
                    });
                    itemBtn.classList.add('is-selected');
                });
            } catch (error) {
                toast('error', error.message || 'Unable to open gallery.');
            }
        }

        fileInput?.addEventListener('change', () => {
            processFiles(fileInput.files);
            fileInput.value = '';
        });

        chooseBtn?.addEventListener('click', openGalleryPicker);

        ['dragenter', 'dragover'].forEach((type) => {
            dropzone?.addEventListener(type, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach((type) => {
            dropzone?.addEventListener(type, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });
        dropzone?.addEventListener('drop', (event) => processFiles(event.dataTransfer?.files));
        dropzone?.addEventListener('click', () => fileInput?.click());

        grid?.addEventListener('click', async (event) => {
            const card = event.target.closest('.blog-banner-card');
            if (!card) return;

            if (event.target.closest('[data-banner-remove]')) {
                card.remove();
                syncEmpty();
                return;
            }

            if (event.target.closest('[data-banner-edit]')) {
                const img = card.querySelector('img');
                if (!img?.src || !window.GalleryImageEditor?.open) return;
                try {
                    const edited = await window.GalleryImageEditor.open({
                        src: img.src,
                        name: 'banner.jpg',
                        root: document,
                    });
                    if (!edited) return;
                    const item = await commitFile(edited, null);
                    const oldId = card.dataset.bannerId;
                    card.dataset.bannerId = String(item.id);
                    card.querySelector('input')?.setAttribute('value', String(item.id));
                    if (img) img.src = item.file_url;
                    // Replace previous id in order if duplicates somehow appear
                    if (oldId && Number(oldId) !== Number(item.id)) {
                        // keep card as-is
                    }
                    toast('success', 'Banner updated.');
                } catch (error) {
                    toast('error', error.message || 'Unable to edit banner.');
                }
            }
        });

        grid?.addEventListener('dragstart', (event) => {
            dragCard = event.target.closest('.blog-banner-card');
            if (dragCard) dragCard.classList.add('is-dragging');
        });
        grid?.addEventListener('dragend', () => {
            dragCard?.classList.remove('is-dragging');
            dragCard = null;
            syncEmpty();
        });
        grid?.addEventListener('dragover', (event) => {
            event.preventDefault();
            const over = event.target.closest('.blog-banner-card');
            if (!dragCard || !over || over === dragCard) return;
            const cards = [...grid.querySelectorAll('.blog-banner-card')];
            const from = cards.indexOf(dragCard);
            const to = cards.indexOf(over);
            if (from < to) over.after(dragCard);
            else over.before(dragCard);
        });

        syncEmpty();
    }

    function boot() {
        document.querySelectorAll('[data-blog-banners]').forEach(initUploader);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
