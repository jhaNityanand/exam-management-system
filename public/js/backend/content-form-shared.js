/**
 * Shared helpers for Blog / News create & edit forms.
 */
(function (global) {
    'use strict';

    const stripHtml = (html) => {
        const tmp = document.createElement('div');
        tmp.innerHTML = html || '';
        return tmp.textContent || tmp.innerText || '';
    };

    const showError = (field, message) => {
        if (!field) return;
        field.classList.add('is-invalid');
        if (field.tomselect?.wrapper) {
            field.tomselect.wrapper.querySelector('.ts-control')?.classList.add('is-invalid');
        }
        const host = field.closest('.ts-wrapper')
            || field.closest('.ems-dtp')
            || field.closest('.ems-rich-editor')
            || field.parentElement;
        let errorEl = host?.querySelector('.qcat-field-error:not([id^="err-"])')
            || host?.querySelector('.qcat-field-error')
            || field.parentElement?.querySelector('.qcat-field-error');
        if (!errorEl && host) {
            errorEl = document.createElement('p');
            errorEl.className = 'qcat-field-error';
            host.appendChild(errorEl);
        }
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.add('is-visible');
        }
    };

    const clearError = (field) => {
        if (!field) return;
        field.classList.remove('is-invalid');
        if (field.tomselect?.wrapper) {
            field.tomselect.wrapper.querySelector('.ts-control')?.classList.remove('is-invalid');
        }
        const host = field.closest('.ts-wrapper')
            || field.closest('.ems-dtp')
            || field.closest('.ems-rich-editor')
            || field.parentElement;
        const errorEl = host?.querySelector('.qcat-field-error');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.remove('is-visible');
        }
    };

    const createSearchableSelect = (selector, options = {}, root = document) => {
        if (typeof global.TomSelect === 'undefined') return null;
        const el = typeof selector === 'string' ? root.querySelector(selector) : selector;
        if (!(el instanceof HTMLSelectElement)) return null;

        // Prefer a fresh instance so theme wrappers / dropdownParent always apply.
        if (el.tomselect) {
            try {
                el.tomselect.destroy();
            } catch (_) {
                /* ignore */
            }
        }

        const disableSearch = el.dataset.noSearch != null
            || el.hasAttribute('data-no-search')
            || el.dataset.disableSearch != null
            || el.hasAttribute('data-disable-search')
            || options.disableSearch === true;

        const userOnInitialize = options.onInitialize;
        const userOnDropdownOpen = options.onDropdownOpen;
        const userOnDropdownClose = options.onDropdownClose;

        const config = {
            create: false,
            closeAfterSelect: true,
            allowEmptyOption: true,
            maxOptions: 250,
            placeholder: options.placeholder || el.dataset.placeholder || 'Search or select…',
            dropdownParent: 'body',
            plugins: disableSearch ? [] : ['dropdown_input'],
            ...options,
            onInitialize() {
                this.wrapper.classList.add('ems-select-wrapper');
                this.wrapper.classList.remove('panel-input', 'qcat-meta-input');
                this.dropdown.classList.add('ems-select-dropdown');
                if (disableSearch) {
                    this.wrapper.classList.add('ems-select-wrapper--no-search');
                }
                if (typeof userOnInitialize === 'function') {
                    userOnInitialize.call(this);
                }
            },
            onDropdownOpen() {
                if (typeof global.EmsFilterDrawer?.positionTomSelectDropdown === 'function') {
                    global.EmsFilterDrawer.positionTomSelectDropdown(this);
                } else if (typeof global.EmsSearchableSelect?.positionDropdown === 'function') {
                    global.EmsSearchableSelect.positionDropdown(this);
                }
                if (typeof userOnDropdownOpen === 'function') {
                    userOnDropdownOpen.call(this);
                }
            },
            onDropdownClose() {
                this.dropdown?.classList.remove('ts-dropdown--up');
                if (this.dropdown) {
                    this.dropdown.style.top = '';
                    this.dropdown.style.bottom = '';
                    this.dropdown.style.maxHeight = '';
                }
                const content = this.dropdown_content
                    || this.dropdown?.querySelector?.('.ts-dropdown-content');
                if (content) {
                    content.style.maxHeight = '';
                }
                if (typeof userOnDropdownClose === 'function') {
                    userOnDropdownClose.call(this);
                }
            },
        };

        if (disableSearch) {
            config.plugins = (config.plugins || []).filter((plugin) => plugin !== 'dropdown_input');
            config.searchField = ['text'];
            config.score = () => () => 1;
        }

        // Strip helper flags that are not Tom Select options.
        delete config.disableSearch;

        const instance = new global.TomSelect(el, config);
        global.EmsTomSelectBlur?.attach(instance);
        return instance;
    };

    const initFormSelects = (form, extra = {}) => {
        const root = form || document;

        if (extra.categorySelector) {
            global.EmsTomSelectHierarchy?.create(extra.categorySelector, {
                placeholder: 'Search or select category…',
            });
        }

        const selects = [
            { selector: '#status', placeholder: 'Select status…', allowEmptyOption: false, disableSearch: true },
            { selector: '#author_id', placeholder: 'Search or select author…' },
            { selector: '#visibility', placeholder: 'Select visibility…', allowEmptyOption: false, disableSearch: true },
            { selector: '#meta-robots', placeholder: 'Select robots directive…', allowEmptyOption: false, disableSearch: true },
        ];

        selects.forEach(({ selector, ...opts }) => {
            const el = root.querySelector(selector);
            if (el) createSearchableSelect(el, opts, root);
        });

        global.EmsTomSelectBlur?.blurNativeSelects(root);
    };

    const initTagsSelect = (config = {}) => {
        const tagsEl = document.getElementById('tags');
        if (!tagsEl || !global.TomSelect) return null;

        if (tagsEl.tomselect) {
            try {
                tagsEl.tomselect.destroy();
            } catch (_) {
                /* ignore */
            }
        }

        const itemClass = config.tagItemClass || 'content-tag-item';
        const seen = new Set();

        const tagsSelect = new global.TomSelect(tagsEl, {
            plugins: ['remove_button'],
            create: true,
            persist: false,
            maxItems: null,
            duplicates: false,
            delimiter: '',
            separator: '',
            dropdownParent: 'body',
            placeholder: 'Type a tag and press Enter…',
            render: {
                item: (data, escape) => `<div class="item ${itemClass}">${escape(data.text)}</div>`,
                option: (data, escape) => `<div class="option">${escape(data.text)}</div>`,
            },
            onInitialize() {
                this.wrapper.classList.add('ems-select-wrapper', 'is-multiple');
                this.dropdown.classList.add('ems-select-dropdown');
                this.items.forEach((value) => seen.add(String(value).trim().toLowerCase()));
            },
            onItemAdd(value) {
                const normalized = String(value || '').trim().toLowerCase();
                if (!normalized) {
                    this.removeItem(value, true);
                    return;
                }
                if (seen.has(normalized)) {
                    this.removeItem(value, true);
                    return;
                }
                seen.add(normalized);
            },
            onItemRemove(value) {
                seen.delete(String(value || '').trim().toLowerCase());
            },
            onDropdownOpen() {
                if (typeof global.EmsFilterDrawer?.positionTomSelectDropdown === 'function') {
                    global.EmsFilterDrawer.positionTomSelectDropdown(this);
                } else if (typeof global.EmsSearchableSelect?.positionDropdown === 'function') {
                    global.EmsSearchableSelect.positionDropdown(this);
                }
            },
            onDropdownClose() {
                this.dropdown?.classList.remove('ts-dropdown--up');
                if (this.dropdown) {
                    this.dropdown.style.top = '';
                    this.dropdown.style.bottom = '';
                    this.dropdown.style.maxHeight = '';
                }
                const content = this.dropdown_content
                    || this.dropdown?.querySelector?.('.ts-dropdown-content');
                if (content) {
                    content.style.maxHeight = '';
                }
            },
        });
        global.EmsTomSelectBlur?.attach(tagsSelect);
        return tagsSelect;
    };

    const lockBodyScroll = (locked) => {
        document.body.classList.toggle('ems-modal-open', locked);
    };

    const portalModal = (modal) => {
        if (!modal || modal.parentElement === document.body) return;
        document.body.appendChild(modal);
    };

    const galleryEditUrlFor = (id) => {
        if (global.galleryEditUrlTemplate) {
            return String(global.galleryEditUrlTemplate).replace('__ID__', String(id));
        }
        const commit = global.galleryCommitUrl || '';
        if (commit.includes('/commit')) {
            return commit.replace(/\/commit\/?$/, `/${id}/edit`);
        }
        const store = String(global.galleryStoreUrl || '').replace(/\/$/, '');
        return `${store}/${id}/edit`;
    };

    const initGalleryPickers = (existingMedia = {}) => {
        const csrf = () => global.galleryCsrf
            || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || '';

        const fetchGallery = async (kind = 'image', search = '') => {
            const url = new URL(global.galleryDataUrl, global.location.origin);
            url.searchParams.set('kind', kind);
            url.searchParams.set('per_page', '24');
            if (search) url.searchParams.set('search', search);
            const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('Failed to load gallery');
            return res.json();
        };

        const uploadFilesToGallery = (files, onProgress) => new Promise((resolve, reject) => {
            const formData = new FormData();
            files.forEach((file) => formData.append('files[]', file));
            formData.append('_token', csrf());
            formData.append('source', 'picker');
            const xhr = new XMLHttpRequest();
            xhr.open('POST', global.galleryStoreUrl, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf());
            xhr.upload.onprogress = (event) => {
                if (!event.lengthComputable || typeof onProgress !== 'function') return;
                onProgress(Math.round((event.loaded / event.total) * 100));
            };
            xhr.onload = () => {
                let payload = null;
                try { payload = JSON.parse(xhr.responseText || '{}'); } catch { /* ignore */ }
                if (xhr.status >= 200 && xhr.status < 300) {
                    const rows = Array.isArray(payload?.data) ? payload.data : [payload?.data || payload].filter(Boolean);
                    resolve(rows);
                    return;
                }
                reject(new Error(payload?.message || 'Upload failed'));
            };
            xhr.onerror = () => reject(new Error('Upload failed'));
            xhr.send(formData);
        });

        const saveEditedToGallery = (id, file) => new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', galleryEditUrlFor(id), true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf());
            xhr.onload = () => {
                let payload = null;
                try { payload = JSON.parse(xhr.responseText || '{}'); } catch { /* ignore */ }
                if (xhr.status >= 200 && xhr.status < 300 && payload?.data) {
                    resolve(payload.data);
                    return;
                }
                reject(new Error(payload?.message || 'Save edit failed'));
            };
            xhr.onerror = () => reject(new Error('Save edit failed'));
            xhr.send(formData);
        });

        const isImageItem = (item) => {
            if (item?.is_image === true || item?.kind === 'image') return true;
            return /\.(jpe?g|png|gif|webp|svg)(\?|$)/i.test(item?.file_url || '');
        };

        const syncEmptyState = (fieldRoot) => {
            const preview = fieldRoot.querySelector('[data-gallery-preview]');
            const empty = fieldRoot.querySelector('[data-gallery-empty]');
            const clearBtn = fieldRoot.querySelector('.gallery-picker-clear');
            const has = Boolean(preview?.querySelector('.gallery-picker-thumb'));
            if (empty) empty.hidden = has;
            if (clearBtn) clearBtn.hidden = !has;
        };

        const bindThumbActions = (thumb, fieldRoot, multiple) => {
            if (thumb.dataset.actionsBound === '1') return;
            thumb.dataset.actionsBound = '1';

            thumb.addEventListener('click', async (event) => {
                const removeBtn = event.target.closest('[data-picker-remove]');
                const editBtn = event.target.closest('[data-picker-edit]');
                const id = thumb.dataset.id;

                if (removeBtn) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (multiple) {
                        fieldRoot.querySelector(`.gallery-picker-inputs input[value="${id}"]`)?.remove();
                        thumb.remove();
                    } else {
                        fieldRoot.querySelector('[data-gallery-preview]').innerHTML = '';
                        const hidden = fieldRoot.querySelector('input[type="hidden"]:not([name$="[]"])');
                        if (hidden) hidden.value = '';
                    }
                    syncEmptyState(fieldRoot);
                    return;
                }

                if (!editBtn) return;
                event.preventDefault();
                event.stopPropagation();
                const img = thumb.querySelector('img');
                if (!img?.src || !global.GalleryImageEditor?.open) return;

                try {
                    const edited = await global.GalleryImageEditor.open({
                        src: img.src,
                        name: thumb.dataset.name || 'image.jpg',
                        root: document,
                    });
                    if (!edited || edited.__keepOriginal) return;
                    const item = await saveEditedToGallery(id, edited);
                    const url = item.file_url || item.url;
                    if (url) {
                        img.src = `${url}${url.includes('?') ? '&' : '?'}t=${Date.now()}`;
                        img.classList.remove('hidden');
                        thumb.querySelector('.gallery-picker-thumb__placeholder')?.remove();
                    }
                    global.EmsToast?.success?.('Image updated.');
                } catch (error) {
                    global.EmsToast?.error?.(error.message || 'Unable to edit image.');
                }
            });
        };

        const renderThumb = (container, item, multiple, fieldRoot) => {
            const id = item.id;
            const url = item.file_url;
            const image = isImageItem(item);
            const existing = container.querySelector(`.gallery-picker-thumb[data-id="${id}"]`);

            if (!multiple) {
                container.innerHTML = '';
                const hidden = fieldRoot.querySelector('input[type="hidden"]:not([name$="[]"])');
                if (hidden) hidden.value = id;
            } else {
                const inputsHost = fieldRoot.querySelector('.gallery-picker-inputs');
                if (inputsHost && !inputsHost.querySelector(`input[value="${id}"]`)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${fieldRoot.dataset.name}[]`;
                    input.value = id;
                    inputsHost.appendChild(input);
                }
                if (existing) {
                    const img = existing.querySelector('img');
                    if (image && url && img) {
                        img.src = url;
                        img.classList.remove('hidden');
                    }
                    syncEmptyState(fieldRoot);
                    return;
                }
            }

            const thumb = document.createElement('div');
            thumb.className = 'gallery-picker-thumb is-selected';
            thumb.dataset.id = id;
            thumb.dataset.name = item.original_name || '';
            const media = image && url
                ? `<img src="${url}" alt="" class="gallery-picker-thumb__img">`
                : `<span class="gallery-picker-thumb__placeholder">${item.original_name || `#${id}`}</span>`;
            const editBtn = image
                ? `<button type="button" class="gallery-picker-thumb__btn" data-picker-edit title="Edit" aria-label="Edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 00.707-.293l9.414-9.414a2 2 0 000-2.828l-2.172-2.172a2 2 0 00-2.828 0L4.293 14.707A1 1 0 004 15.414V20z"/></svg></button>`
                : '';
            thumb.innerHTML = `
                ${media}
                <div class="gallery-picker-thumb__actions">
                    ${editBtn}
                    <button type="button" class="gallery-picker-thumb__btn gallery-picker-thumb__btn--danger" data-picker-remove title="Remove" aria-label="Remove">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            `;
            container.appendChild(thumb);
            bindThumbActions(thumb, fieldRoot, multiple);
            syncEmptyState(fieldRoot);
        };

        const openEditorForItem = async (item) => {
            if (!isImageItem(item) || !global.GalleryImageEditor?.open) return item;
            const src = item.file_url || item.url;
            if (!src) return item;
            try {
                const edited = await global.GalleryImageEditor.open({
                    src,
                    name: item.original_name || 'image.jpg',
                    root: document,
                });
                if (!edited || edited.__keepOriginal) return item;
                return await saveEditedToGallery(item.id, edited);
            } catch (error) {
                global.EmsToast?.error?.(error.message || 'Unable to open image editor.');
                return item;
            }
        };

        const hydrateExistingMedia = (fieldRoot) => {
            const name = fieldRoot.dataset.name;
            const preview = fieldRoot.querySelector('.gallery-picker-preview');
            if (!preview || !existingMedia) return;

            if (name === 'og_image_id' && existingMedia.og_image_id) {
                renderThumb(preview, {
                    id: fieldRoot.querySelector('input[type="hidden"]')?.value,
                    file_url: existingMedia.og_image_id,
                    kind: 'image',
                    is_image: true,
                }, false, fieldRoot);
            }
            if (name === 'featured_image_id' && existingMedia.featured_image_id) {
                renderThumb(preview, {
                    id: fieldRoot.querySelector('input[type="hidden"]')?.value,
                    file_url: existingMedia.featured_image_id,
                    kind: 'image',
                    is_image: true,
                }, false, fieldRoot);
            }
            if (name === 'attachment_ids' && existingMedia.attachment_ids) {
                Object.entries(existingMedia.attachment_ids).forEach(([id, fileUrl]) => {
                    renderThumb(preview, { id, file_url: fileUrl, kind: 'image', is_image: true }, true, fieldRoot);
                });
            }
        };

        document.querySelectorAll('[data-gallery-picker]').forEach((fieldRoot) => {
            if (fieldRoot.dataset.emsGalleryBound === '1') {
                hydrateExistingMedia(fieldRoot);
                return;
            }
            fieldRoot.dataset.emsGalleryBound = '1';

            const multiple = fieldRoot.dataset.multiple === '1';
            const kind = fieldRoot.dataset.kind || 'image';
            const modalId = fieldRoot.dataset.modalId;
            const modal = document.getElementById(modalId);
            const grid = modal?.querySelector('[data-grid]');
            const searchInput = modal?.querySelector('.gallery-picker-search');
            const preview = fieldRoot.querySelector('.gallery-picker-preview');
            const dropzone = fieldRoot.querySelector('[data-gallery-dropzone]');
            const fileInput = fieldRoot.querySelector('.gallery-picker-upload-input');
            const uploadProgress = fieldRoot.querySelector('[data-gallery-upload-progress]');
            const uploadProgressBar = fieldRoot.querySelector('[data-gallery-upload-progress-bar]');
            const uploadProgressLabel = fieldRoot.querySelector('[data-gallery-upload-progress-label]');
            let picked = new Set();
            let uploading = false;

            const showUploadProgress = (pct, label) => {
                if (!uploadProgress || !uploadProgressBar) return;
                uploadProgress.hidden = false;
                uploadProgressBar.style.transform = `scaleX(${Math.max(0.05, Math.min(1, pct / 100))})`;
                if (uploadProgressLabel && label) uploadProgressLabel.textContent = label;
            };

            const hideUploadProgress = () => {
                if (uploadProgress) uploadProgress.hidden = true;
            };

            const closeModal = () => {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                lockBodyScroll(false);
            };

            const openModal = () => {
                if (!modal) return;
                portalModal(modal);
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                lockBodyScroll(true);
                loadGrid();
                searchInput?.focus();
            };

            const loadGrid = async () => {
                if (!grid) return;
                grid.innerHTML = Array.from({ length: 12 }).map((_, i) => `
                    <div class="gallery-picker-skeleton" aria-hidden="true">
                        <div class="gallery-picker-skeleton__thumb"></div>
                        <div class="gallery-picker-skeleton__line" style="width:${50 + (i % 4) * 10}%"></div>
                    </div>
                `).join('');
                try {
                    const json = await fetchGallery(kind, searchInput?.value?.trim() || '');
                    const items = json.data || [];
                    if (!items.length) {
                        grid.innerHTML = '<p class="gallery-picker-modal__status">No media found.</p>';
                        return;
                    }
                    grid.innerHTML = '';
                    items.forEach((item) => {
                        const cell = document.createElement('button');
                        cell.type = 'button';
                        cell.className = 'gallery-picker-grid-item';
                        cell.dataset.id = item.id;
                        if (item.is_image && item.file_url) {
                            cell.innerHTML = `<img src="${item.file_url}" alt="${item.original_name || ''}">`;
                        } else {
                            cell.innerHTML = `<div class="gallery-picker-grid-item__file">${item.original_name || 'File'}</div>`;
                        }
                        cell.addEventListener('click', () => {
                            if (multiple) {
                                cell.classList.toggle('is-picked');
                                if (cell.classList.contains('is-picked')) picked.add(String(item.id));
                                else picked.delete(String(item.id));
                            } else {
                                grid.querySelectorAll('.is-picked').forEach((el) => el.classList.remove('is-picked'));
                                cell.classList.add('is-picked');
                                picked = new Set([String(item.id)]);
                            }
                        });
                        grid.appendChild(cell);
                    });
                } catch {
                    grid.innerHTML = '<p class="gallery-picker-modal__status gallery-picker-modal__status--error">Failed to load gallery.</p>';
                }
            };

            const processFiles = async (fileList) => {
                if (!preview || uploading) return;
                const files = [...(fileList || [])].filter(Boolean);
                if (!files.length) return;

                if (!multiple && files.length > 1) {
                    global.EmsToast?.info?.('Only one file can be selected for this field.');
                }

                const batch = multiple ? files : files.slice(0, 1);
                uploading = true;
                dropzone?.classList.add('is-uploading');

                try {
                    showUploadProgress(5, batch.length > 1 ? `Uploading ${batch.length} files…` : 'Uploading…');
                    let items = await uploadFilesToGallery(batch, (pct) => {
                        showUploadProgress(pct, batch.length > 1 ? `Uploading ${pct}%` : `Uploading ${pct}%`);
                    });
                    hideUploadProgress();

                    // Single image upload → auto-open editor after Gallery store.
                    if (batch.length === 1 && isImageItem(items[0])) {
                        items = [await openEditorForItem(items[0])];
                    }

                    items.forEach((item) => renderThumb(preview, item, multiple, fieldRoot));
                    global.EmsToast?.success?.(
                        items.length === 1 ? 'Uploaded to Gallery.' : `${items.length} files uploaded to Gallery.`
                    );
                } catch (error) {
                    hideUploadProgress();
                    global.EmsToast?.error?.(error.message || 'Could not upload file to gallery.');
                    global.Swal?.fire?.({ icon: 'error', title: 'Upload failed', text: error.message || 'Could not upload file to gallery.' });
                } finally {
                    uploading = false;
                    dropzone?.classList.remove('is-uploading');
                }
            };

            fieldRoot.querySelectorAll('.gallery-picker-open').forEach((btn) => {
                btn.addEventListener('click', () => {
                    picked = new Set();
                    openModal();
                });
            });

            modal?.querySelectorAll('[data-close-modal]').forEach((el) => {
                el.addEventListener('click', closeModal);
            });

            modal?.querySelector('.gallery-picker-refresh')?.addEventListener('click', loadGrid);
            searchInput?.addEventListener('input', () => {
                clearTimeout(searchInput._debounce);
                searchInput._debounce = setTimeout(loadGrid, 350);
            });

            modal?.querySelector('.gallery-picker-confirm')?.addEventListener('click', () => {
                if (!preview) return;
                const ids = Array.from(picked);
                if (!ids.length) {
                    closeModal();
                    return;
                }
                if (!multiple) {
                    const itemCell = grid?.querySelector(`[data-id="${ids[0]}"]`);
                    const item = {
                        id: ids[0],
                        file_url: itemCell?.querySelector('img')?.src,
                        kind,
                        is_image: Boolean(itemCell?.querySelector('img')),
                    };
                    renderThumb(preview, item, false, fieldRoot);
                } else {
                    ids.forEach((id) => {
                        const itemCell = grid?.querySelector(`[data-id="${id}"]`);
                        const item = {
                            id,
                            file_url: itemCell?.querySelector('img')?.src,
                            kind,
                            is_image: Boolean(itemCell?.querySelector('img')),
                        };
                        renderThumb(preview, item, true, fieldRoot);
                    });
                }
                closeModal();
            });

            fieldRoot.querySelector('.gallery-picker-clear')?.addEventListener('click', () => {
                if (preview) preview.innerHTML = '';
                const hidden = fieldRoot.querySelector('input[type="hidden"]:not([name$="[]"])');
                if (hidden) hidden.value = '';
                const inputsHost = fieldRoot.querySelector('.gallery-picker-inputs');
                if (inputsHost) inputsHost.innerHTML = '';
                syncEmptyState(fieldRoot);
            });

            fileInput?.addEventListener('change', async (e) => {
                await processFiles(e.target.files);
                e.target.value = '';
            });

            if (dropzone) {
                ['dragenter', 'dragover'].forEach((type) => {
                    dropzone.addEventListener(type, (event) => {
                        event.preventDefault();
                        dropzone.classList.add('is-dragover');
                    });
                });
                ['dragleave', 'drop'].forEach((type) => {
                    dropzone.addEventListener(type, (event) => {
                        event.preventDefault();
                        dropzone.classList.remove('is-dragover');
                    });
                });
                dropzone.addEventListener('drop', (event) => processFiles(event.dataTransfer?.files));
                dropzone.addEventListener('click', () => fileInput?.click());
                dropzone.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        fileInput?.click();
                    }
                });
            }

            // Bind edit/remove on server-rendered thumbs.
            preview?.querySelectorAll('.gallery-picker-thumb').forEach((thumb) => {
                if (!thumb.querySelector('.gallery-picker-thumb__actions')) {
                    const image = Boolean(thumb.querySelector('img:not(.hidden)'));
                    const editBtn = image
                        ? `<button type="button" class="gallery-picker-thumb__btn" data-picker-edit title="Edit" aria-label="Edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 00.707-.293l9.414-9.414a2 2 0 000-2.828l-2.172-2.172a2 2 0 00-2.828 0L4.293 14.707A1 1 0 004 15.414V20z"/></svg></button>`
                        : '';
                    const actions = document.createElement('div');
                    actions.className = 'gallery-picker-thumb__actions';
                    actions.innerHTML = `
                        ${editBtn}
                        <button type="button" class="gallery-picker-thumb__btn gallery-picker-thumb__btn--danger" data-picker-remove title="Remove" aria-label="Remove">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    `;
                    thumb.appendChild(actions);
                }
                bindThumbActions(thumb, fieldRoot, multiple);
            });

            hydrateExistingMedia(fieldRoot);
            syncEmptyState(fieldRoot);
        });
    };

    const bindSeoPreview = (config) => {
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug') || document.getElementById('meta-slug');
        const seoSlugPreview = config.seoSlugId ? document.getElementById(config.seoSlugId) : null;
        const authorSelect = document.getElementById('author_id');
        const authorNameInput = document.getElementById('author_name');
        const metaTitle = document.getElementById('meta-title');
        const metaDesc = document.getElementById('meta-desc');
        const previewTitle = document.getElementById('seo-preview-title');
        const previewUrl = document.getElementById('seo-preview-url');
        const previewDesc = document.getElementById('seo-preview-desc');
        const baseUrl = config.baseUrl || global.location.origin;
        const resolveUrl = config.resolveUrl || global.slugResolveUrl;
        const moduleName = config.module || null;
        const ignoreId = config.ignoreId || null;

        let slugManual = Boolean(slugInput?.value?.trim());
        let debounceTimer = null;
        let requestId = 0;

        const slugify = (text) => String(text || '')
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 80)
            .replace(/-+$/g, '');

        const syncSeoSlugPreview = () => {
            if (seoSlugPreview && slugInput) seoSlugPreview.value = slugInput.value;
        };

        const updateSeoPreview = () => {
            const title = metaTitle?.value?.trim() || titleInput?.value?.trim() || 'Page title preview';
            const desc = metaDesc?.value?.trim() || 'Meta description preview will appear here.';
            const slug = slugInput?.value?.trim() || 'example-slug';
            if (previewTitle) previewTitle.textContent = title;
            if (previewDesc) previewDesc.textContent = desc;
            if (previewUrl) previewUrl.textContent = `${baseUrl}/${slug}`;
        };

        const resolveUniqueSlug = async (preferred) => {
            if (!slugInput) return;
            if (!resolveUrl || !moduleName) {
                slugInput.value = slugify(preferred);
                syncSeoSlugPreview();
                updateSeoPreview();
                return;
            }

            const currentRequest = ++requestId;
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
                if (currentRequest !== requestId) return;
                slugInput.value = payload.slug || slugify(preferred);
            } catch {
                if (currentRequest !== requestId) return;
                slugInput.value = slugify(preferred);
            }

            syncSeoSlugPreview();
            updateSeoPreview();
        };

        const scheduleResolve = (preferred) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => resolveUniqueSlug(preferred), 350);
        };

        slugInput?.addEventListener('input', () => {
            slugManual = slugInput.value.trim() !== '';
            if (!slugManual) {
                scheduleResolve(titleInput?.value || '');
                return;
            }
            scheduleResolve(slugInput.value);
        });

        titleInput?.addEventListener('input', () => {
            if (!slugManual) scheduleResolve(titleInput.value);
            else {
                syncSeoSlugPreview();
                updateSeoPreview();
            }
        });

        [metaTitle, metaDesc].forEach((el) => el?.addEventListener('input', updateSeoPreview));

        authorSelect?.addEventListener('change', () => {
            const option = authorSelect.selectedOptions[0];
            if (option?.dataset?.name && authorNameInput) authorNameInput.value = option.dataset.name;
        });

        syncSeoSlugPreview();
        updateSeoPreview();
    };

    const bindFormValidation = (config) => {
        const form = document.getElementById(config.formId);
        if (!form) return;

        form.querySelectorAll('.panel-input, select, textarea').forEach((field) => {
            field.addEventListener('input', () => clearError(field));
            field.addEventListener('change', () => clearError(field));
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            let isValid = true;

            form.querySelectorAll('.qcat-field-error').forEach((el) => {
                el.textContent = '';
                el.classList.remove('is-visible');
            });
            form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));

            const titleInput = document.getElementById('title');
            if (!titleInput?.value?.trim()) {
                showError(titleInput, 'Please enter a title.');
                isValid = false;
            }

            const slugInput = document.getElementById('slug') || document.getElementById('meta-slug');
            if (slugInput?.value?.trim() && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/i.test(slugInput.value.trim())) {
                showError(slugInput, 'Slug may only contain letters, numbers, and hyphens.');
                isValid = false;
            }

            global.EmsRichTextEditor?.syncAll?.();
            const contentEditor = global.EmsRichTextEditor?.get('content');
            const contentHtml = contentEditor?.getData?.() || document.getElementById('content')?.value || '';
            const contentText = stripHtml(contentHtml).trim();
            const contentField = document.getElementById('content')?.closest('.ems-rich-editor') || document.getElementById('content');
            const statusVal = document.getElementById('status')?.value || '';
            const requiresContent = ['published', 'pending_review'].includes(statusVal);
            if (requiresContent && !contentText) {
                showError(contentField, 'Please enter content before publishing.');
                isValid = false;
            }

            const publishedInput = document.getElementById('published_at');
            const publishedValue = publishedInput?.value?.trim();
            const initialPublished = publishedInput?.dataset.initialValue?.trim() || '';
            if (publishedValue && publishedValue !== initialPublished) {
                const picked = new Date(publishedValue.replace(' ', 'T'));
                if (!Number.isNaN(picked.getTime()) && picked <= new Date()) {
                    showError(publishedInput.closest('.ems-dtp') || publishedInput, 'Published date must be in the future.');
                    isValid = false;
                }
            } else if (publishedValue && config.isCreate) {
                const picked = new Date(publishedValue.replace(' ', 'T'));
                if (!Number.isNaN(picked.getTime()) && picked <= new Date()) {
                    showError(publishedInput.closest('.ems-dtp') || publishedInput, 'Published date must be in the future.');
                    isValid = false;
                }
            }

            if (config.module === 'news' && publishedValue) {
                const publishedDate = new Date(publishedValue.replace(' ', 'T'));
                if (!Number.isNaN(publishedDate.getTime())) {
                    const expiresInput = document.getElementById('expires_at');
                    const expiresValue = expiresInput?.value?.trim();
                    if (expiresValue) {
                        const expiresDate = new Date(expiresValue.replace(' ', 'T'));
                        if (!Number.isNaN(expiresDate.getTime()) && expiresDate <= publishedDate) {
                            showError(expiresInput.closest('.ems-dtp') || expiresInput, 'Expiry date must be greater than the publish date.');
                            isValid = false;
                        }
                    }

                    const breakingUntilInput = document.getElementById('breaking_until');
                    const breakingUntilValue = breakingUntilInput?.value?.trim();
                    if (breakingUntilValue) {
                        const breakingUntilDate = new Date(breakingUntilValue.replace(' ', 'T'));
                        if (!Number.isNaN(breakingUntilDate.getTime()) && breakingUntilDate <= publishedDate) {
                            showError(breakingUntilInput.closest('.ems-dtp') || breakingUntilInput, 'Breaking News Until must be greater than the publish date.');
                            isValid = false;
                        }
                    }
                }
            }

            const canonicalFld = document.getElementById('meta-canonical');
            if (canonicalFld?.value?.trim()) {
                try {
                    new URL(canonicalFld.value.trim());
                } catch {
                    showError(canonicalFld, 'Please enter a valid URL (e.g. https://example.com).');
                    isValid = false;
                }
            }

            if (!isValid) {
                const firstInvalid = form.querySelector('.is-invalid, .ems-rich-editor.is-invalid, .ts-control.is-invalid');
                firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const submitBtn = document.getElementById('btn-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                `;
            }

            form.submit();
        });
    };

    const parsePickerDate = (value) => {
        if (!value?.trim()) return null;
        const parsed = new Date(value.trim().replace(' ', 'T'));
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    };

    const syncNewsDateMins = () => {
        const publishedInput = document.getElementById('published_at');
        const publishedDate = parsePickerDate(publishedInput?.value);
        ['expires_at', 'breaking_until'].forEach((id) => {
            const input = document.getElementById(id);
            if (!input?._flatpickr) return;
            input._flatpickr.set('minDate', publishedDate || null);
            const current = parsePickerDate(input.value);
            if (publishedDate && current && current <= publishedDate) {
                input._flatpickr.clear();
            }
        });
    };

    const bindNewsDateConstraints = (config) => {
        if (config.module !== 'news') return;
        const publishedInput = document.getElementById('published_at');
        if (!publishedInput) return;

        const onPublishedChange = () => syncNewsDateMins();
        publishedInput.addEventListener('change', onPublishedChange);
        publishedInput.addEventListener('input', onPublishedChange);
        syncNewsDateMins();
    };

    const initContentForm = (config) => {
        initFormSelects(document.getElementById(config.formId), {
            categorySelector: config.categorySelector,
        });
        initTagsSelect({ tagItemClass: config.tagItemClass });
        bindSeoPreview(config);
        initGalleryPickers(config.existingMedia || {});
        bindNewsDateConstraints(config);
        bindFormValidation(config);

        if (global.EmsRichTextEditor?.initAll) {
            global.EmsRichTextEditor.initAll(document);
        }
    };

    global.EmsContentForm = {
        initContentForm,
        initFormSelects,
        initTagsSelect,
        initGalleryPickers,
        bindSeoPreview,
        bindFormValidation,
        createSearchableSelect,
        showError,
        clearError,
    };
}(window));
