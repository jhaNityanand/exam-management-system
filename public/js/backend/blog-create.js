document.addEventListener('DOMContentLoaded', () => {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    const seoSlugPreview = document.getElementById('blog-seo-slug');
    const authorSelect = document.getElementById('author_id');
    const authorNameInput = document.getElementById('author_name');
    const metaTitle = document.getElementById('meta-title');
    const metaDesc = document.getElementById('meta-desc');
    const excerptInput = document.getElementById('excerpt');
    const previewTitle = document.getElementById('seo-preview-title');
    const previewUrl = document.getElementById('seo-preview-url');
    const previewDesc = document.getElementById('seo-preview-desc');
    const baseUrl = window.blogBaseUrl || `${window.location.origin}/blog`;

    let slugManual = Boolean(slugInput?.value?.trim());

    const slugify = (text) => String(text || '')
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');

    if (slugInput) {
        slugInput.addEventListener('input', () => {
            slugManual = slugInput.value.trim() !== '';
            syncSeoSlugPreview();
            updateSeoPreview();
        });
    }

    if (titleInput) {
        titleInput.addEventListener('input', () => {
            if (!slugManual && slugInput) {
                slugInput.value = slugify(titleInput.value);
            }
            syncSeoSlugPreview();
            updateSeoPreview();
        });
    }

    const syncSeoSlugPreview = () => {
        if (seoSlugPreview && slugInput) {
            seoSlugPreview.value = slugInput.value;
        }
    };

    const updateSeoPreview = () => {
        const title = metaTitle?.value?.trim() || titleInput?.value?.trim() || 'Page title preview';
        const desc = metaDesc?.value?.trim() || excerptInput?.value?.trim() || 'Meta description preview will appear here.';
        const slug = slugInput?.value?.trim() || 'example-slug';
        if (previewTitle) previewTitle.textContent = title;
        if (previewDesc) previewDesc.textContent = desc;
        if (previewUrl) previewUrl.textContent = `${baseUrl}/${slug}`;
    };

    [metaTitle, metaDesc, excerptInput].forEach((el) => {
        el?.addEventListener('input', updateSeoPreview);
    });

    if (authorSelect && authorNameInput) {
        authorSelect.addEventListener('change', () => {
            const option = authorSelect.selectedOptions[0];
            if (option?.dataset?.name) {
                authorNameInput.value = option.dataset.name;
            }
        });
    }

    syncSeoSlugPreview();
    updateSeoPreview();

    // Tags TomSelect with create
    const tagsEl = document.getElementById('tags');
    if (tagsEl && window.TomSelect) {
        const tagsSelect = new TomSelect(tagsEl, {
            plugins: ['remove_button'],
            create: true,
            persist: false,
            maxItems: null,
            placeholder: 'Add tags…',
        });
        window.EmsTomSelectBlur?.attach(tagsSelect);
    }

    // Gallery picker instances
    const fetchGallery = async (kind = 'image', search = '') => {
        const url = new URL(window.galleryDataUrl, window.location.origin);
        url.searchParams.set('kind', kind);
        url.searchParams.set('per_page', '24');
        if (search) url.searchParams.set('search', search);
        const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('Failed to load gallery');
        return res.json();
    };

    const uploadToGallery = async (file) => {
        const formData = new FormData();
        formData.append('files[]', file);
        formData.append('_token', window.galleryCsrf);
        const res = await fetch(window.galleryStoreUrl, {
            method: 'POST',
            body: formData,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error('Upload failed');
        const json = await res.json();
        return json.data?.[0] || json.data || json;
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
                input.name = fieldRoot.dataset.name + '[]';
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
            thumb.innerHTML = `<span class="gallery-picker-thumb__placeholder">${item.original_name || '#' + id}</span>`;
        }

        if (multiple) {
            container.appendChild(thumb);
        } else {
            container.appendChild(thumb);
        }
    };

    const hydrateExistingMedia = (fieldRoot) => {
        const existing = window.blogExistingMedia || {};
        const name = fieldRoot.dataset.name;
        const preview = fieldRoot.querySelector('.gallery-picker-preview');
        if (!preview) return;

        if (name === 'banner_image_id' && existing.banner_image_id) {
            renderThumb(preview, { id: fieldRoot.querySelector('input[type="hidden"]')?.value, file_url: existing.banner_image_id, kind: 'image' }, false, fieldRoot);
        }
        if (name === 'og_image_id' && existing.og_image_id) {
            renderThumb(preview, { id: fieldRoot.querySelector('input[type="hidden"]')?.value, file_url: existing.og_image_id, kind: 'image' }, false, fieldRoot);
        }
        if (name === 'attachment_ids' && existing.attachment_ids) {
            Object.entries(existing.attachment_ids).forEach(([id, url]) => {
                renderThumb(preview, { id, file_url: url, kind: 'image' }, true, fieldRoot);
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
        let picked = new Set();

        const loadGrid = async () => {
            if (!grid) return;
            grid.innerHTML = '<p class="col-span-full text-sm text-slate-500">Loading…</p>';
            try {
                const json = await fetchGallery(kind, searchInput?.value?.trim() || '');
                const items = json.data || [];
                if (!items.length) {
                    grid.innerHTML = '<p class="col-span-full text-sm text-slate-500">No media found.</p>';
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
                grid.innerHTML = '<p class="col-span-full text-sm text-red-500">Failed to load gallery.</p>';
            }
        };

        fieldRoot.querySelectorAll('.gallery-picker-open').forEach((btn) => {
            btn.addEventListener('click', () => {
                picked = new Set();
                if (modal) {
                    modal.classList.remove('hidden');
                    loadGrid();
                }
            });
        });

        modal?.querySelectorAll('[data-close-modal]').forEach((el) => {
            el.addEventListener('click', () => modal.classList.add('hidden'));
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
                modal.classList.add('hidden');
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
            modal.classList.add('hidden');
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
                const item = await uploadToGallery(file);
                renderThumb(preview, item, multiple, fieldRoot);
            } catch {
                window.Swal?.fire?.({ icon: 'error', title: 'Upload failed', text: 'Could not upload file to gallery.' });
            }
            e.target.value = '';
        });

        hydrateExistingMedia(fieldRoot);
    });

    // Rich text editor init
    if (window.EmsRichTextEditor?.initAll) {
        window.EmsRichTextEditor.initAll(document);
    }
});
