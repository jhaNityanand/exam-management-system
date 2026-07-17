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
        if (!(el instanceof HTMLSelectElement) || el.tomselect) return el?.tomselect || null;

        const instance = new global.TomSelect(el, {
            create: false,
            closeAfterSelect: true,
            allowEmptyOption: true,
            maxOptions: 250,
            placeholder: options.placeholder || 'Search or select…',
            ...options,
        });
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
            { selector: '#status', placeholder: 'Select status…', allowEmptyOption: false },
            { selector: '#author_id', placeholder: 'Search or select author…' },
            { selector: '#visibility', placeholder: 'Select visibility…', allowEmptyOption: false },
            { selector: '#meta-robots', placeholder: 'Select robots directive…', allowEmptyOption: false },
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
            placeholder: 'Type a tag and press Enter…',
            render: {
                item: (data, escape) => `<div class="item ${itemClass}">${escape(data.text)}</div>`,
                option: (data, escape) => `<div class="option">${escape(data.text)}</div>`,
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
            onInitialize() {
                this.items.forEach((value) => seen.add(String(value).trim().toLowerCase()));
            },
            onItemRemove(value) {
                seen.delete(String(value || '').trim().toLowerCase());
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

    const initGalleryPickers = (existingMedia = {}) => {
        const fetchGallery = async (kind = 'image', search = '') => {
            const url = new URL(global.galleryDataUrl, global.location.origin);
            url.searchParams.set('kind', kind);
            url.searchParams.set('per_page', '24');
            if (search) url.searchParams.set('search', search);
            const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('Failed to load gallery');
            return res.json();
        };

        const uploadToGallery = async (file, onProgress) => {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('files[]', file);
                formData.append('_token', global.galleryCsrf);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', global.galleryStoreUrl, true);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.upload.onprogress = (event) => {
                    if (!event.lengthComputable || typeof onProgress !== 'function') return;
                    onProgress(Math.round((event.loaded / event.total) * 100));
                };
                xhr.onload = () => {
                    let payload = null;
                    try { payload = JSON.parse(xhr.responseText || '{}'); } catch { /* ignore */ }
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve(payload?.data?.[0] || payload?.data || payload);
                        return;
                    }
                    reject(new Error(payload?.message || 'Upload failed'));
                };
                xhr.onerror = () => reject(new Error('Upload failed'));
                xhr.send(formData);
            });
        };

        const renderThumb = (container, item, multiple, fieldRoot) => {
            const id = item.id;
            const url = item.file_url;
            const isImage = item.is_image !== false && (item.kind === 'image' || /\.(jpe?g|png|gif|webp|svg)$/i.test(url || ''));

            if (!multiple) {
                container.innerHTML = '';
                const hidden = fieldRoot.querySelector('input[type="hidden"]:not([name$="[]"])');
                if (hidden) hidden.value = id;
                const clearBtn = fieldRoot.querySelector('.gallery-picker-clear');
                if (clearBtn) clearBtn.hidden = false;
            } else {
                const inputsHost = fieldRoot.querySelector('.gallery-picker-inputs');
                if (inputsHost && !inputsHost.querySelector(`input[value="${id}"]`)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${fieldRoot.dataset.name}[]`;
                    input.value = id;
                    inputsHost.appendChild(input);
                }
            }

            const thumb = document.createElement('div');
            thumb.className = 'gallery-picker-thumb is-selected';
            thumb.dataset.id = id;
            if (isImage && url) {
                thumb.innerHTML = `<img src="${url}" alt="" class="gallery-picker-thumb__img">`;
            } else {
                thumb.innerHTML = `<span class="gallery-picker-thumb__placeholder">${item.original_name || `#${id}`}</span>`;
            }
            container.appendChild(thumb);
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
                }, false, fieldRoot);
            }
            if (name === 'featured_image_id' && existingMedia.featured_image_id) {
                renderThumb(preview, {
                    id: fieldRoot.querySelector('input[type="hidden"]')?.value,
                    file_url: existingMedia.featured_image_id,
                    kind: 'image',
                }, false, fieldRoot);
            }
            if (name === 'attachment_ids' && existingMedia.attachment_ids) {
                Object.entries(existingMedia.attachment_ids).forEach(([id, fileUrl]) => {
                    renderThumb(preview, { id, file_url: fileUrl, kind: 'image' }, true, fieldRoot);
                });
            }
        };

        document.querySelectorAll('[data-gallery-picker]').forEach((fieldRoot) => {
            const multiple = fieldRoot.dataset.multiple === '1';
            const kind = fieldRoot.dataset.kind || 'image';
            const modalId = fieldRoot.dataset.modalId;
            const modal = document.getElementById(modalId);
            const grid = modal?.querySelector('[data-grid]');
            const searchInput = modal?.querySelector('.gallery-picker-search');
            const preview = fieldRoot.querySelector('.gallery-picker-preview');
            const uploadProgress = fieldRoot.querySelector('[data-gallery-upload-progress]');
            const uploadProgressBar = fieldRoot.querySelector('[data-gallery-upload-progress-bar]');
            let picked = new Set();

            const showUploadProgress = (pct) => {
                if (!uploadProgress || !uploadProgressBar) return;
                uploadProgress.hidden = false;
                uploadProgressBar.style.transform = `scaleX(${Math.max(0.05, Math.min(1, pct / 100))})`;
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
                    const item = { id: ids[0], file_url: itemCell?.querySelector('img')?.src, kind };
                    renderThumb(preview, item, false, fieldRoot);
                } else {
                    ids.forEach((id) => {
                        const itemCell = grid?.querySelector(`[data-id="${id}"]`);
                        const item = { id, file_url: itemCell?.querySelector('img')?.src, kind };
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
                const clearBtn = fieldRoot.querySelector('.gallery-picker-clear');
                if (clearBtn) clearBtn.hidden = true;
            });

            fieldRoot.querySelector('.gallery-picker-upload-input')?.addEventListener('change', async (e) => {
                const file = e.target.files?.[0];
                if (!file || !preview) return;
                try {
                    showUploadProgress(5);
                    const item = await uploadToGallery(file, showUploadProgress);
                    hideUploadProgress();
                    renderThumb(preview, item, multiple, fieldRoot);
                } catch {
                    hideUploadProgress();
                    global.Swal?.fire?.({ icon: 'error', title: 'Upload failed', text: 'Could not upload file to gallery.' });
                }
                e.target.value = '';
            });

            hydrateExistingMedia(fieldRoot);
        });
    };

    const bindSeoPreview = (config) => {
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        const seoSlugPreview = document.getElementById(config.seoSlugId);
        const authorSelect = document.getElementById('author_id');
        const authorNameInput = document.getElementById('author_name');
        const metaTitle = document.getElementById('meta-title');
        const metaDesc = document.getElementById('meta-desc');
        const previewTitle = document.getElementById('seo-preview-title');
        const previewUrl = document.getElementById('seo-preview-url');
        const previewDesc = document.getElementById('seo-preview-desc');
        const baseUrl = config.baseUrl || global.location.origin;

        let slugManual = Boolean(slugInput?.value?.trim());

        const slugify = (text) => String(text || '')
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');

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

        slugInput?.addEventListener('input', () => {
            slugManual = slugInput.value.trim() !== '';
            syncSeoSlugPreview();
            updateSeoPreview();
        });

        titleInput?.addEventListener('input', () => {
            if (!slugManual && slugInput) slugInput.value = slugify(titleInput.value);
            syncSeoSlugPreview();
            updateSeoPreview();
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

            const slugInput = document.getElementById('slug');
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
