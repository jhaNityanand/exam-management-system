(function initGalleryModule() {
    const root = document.getElementById('gallery-app');
    if (!root) return;

    const endpoints = JSON.parse(root.dataset.endpoints || '{}');
    const csrf = root.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';

    const state = {
        items: [],
        page: 1,
        lastPage: 1,
        total: 0,
        view: localStorage.getItem('gallery.view') || 'grid',
        trash: 'active',
        selected: new Set(),
        loading: false,
        previewIndex: -1,
        openMenuId: null,
    };

    const els = {
        grid: document.getElementById('gallery-grid'),
        skeleton: document.getElementById('gallery-skeleton'),
        empty: document.getElementById('gallery-empty'),
        pagination: document.getElementById('gallery-pagination'),
        search: document.getElementById('gallery-search'),
        kind: document.getElementById('gallery-kind'),
        sort: document.getElementById('gallery-sort'),
        perPage: document.getElementById('gallery-per-page'),
        bulkBar: document.getElementById('gallery-bulk-bar'),
        selectAll: document.getElementById('gallery-select-all'),
        selectedCount: document.getElementById('gallery-selected-count'),
        bulkActive: document.getElementById('gallery-bulk-actions-active'),
        bulkBin: document.getElementById('gallery-bulk-actions-bin'),
        dropzone: document.getElementById('gallery-dropzone'),
        fileInput: document.getElementById('gallery-file-input'),
        uploadProgress: document.getElementById('gallery-upload-progress'),
        uploadBar: document.getElementById('gallery-upload-progress-bar'),
        uploadLabel: document.getElementById('gallery-upload-progress-label'),
        modal: document.getElementById('gallery-preview-modal'),
        previewImage: document.getElementById('gallery-preview-image'),
        previewBroken: document.getElementById('gallery-preview-broken'),
        previewFile: document.getElementById('gallery-preview-file'),
        previewTitle: document.getElementById('gallery-preview-title'),
        previewSub: document.getElementById('gallery-preview-sub'),
        previewActions: document.getElementById('gallery-preview-actions'),
        previewPrev: document.getElementById('gallery-preview-prev'),
        previewNext: document.getElementById('gallery-preview-next'),
    };

    function toast(type, message) {
        if (window.EmsToast?.[type]) {
            window.EmsToast[type](message);
            return;
        }
        window.EmsToast?.show?.({ type, message });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * Rewrite absolute storage URLs onto the current origin so images work when
     * APP_URL is http://localhost but the app runs on 127.0.0.1:8000 (or vice versa).
     */
    function resolveMediaUrl(urlOrPath) {
        if (!urlOrPath) return '';
        const raw = String(urlOrPath).trim();
        try {
            if (/^https?:\/\//i.test(raw)) {
                const parsed = new URL(raw);
                if (parsed.pathname.includes('/storage/')) {
                    return window.location.origin + parsed.pathname + parsed.search;
                }
                return raw;
            }
            if (raw.startsWith('/')) {
                return window.location.origin + raw;
            }
            return window.location.origin + '/storage/' + raw.replace(/^storage\//, '');
        } catch {
            return raw;
        }
    }

    function normalizeItems(items) {
        return (items || []).map((item) => {
            const fileUrl = resolveMediaUrl(item.file_url || item.file_path);
            return { ...item, file_url: fileUrl };
        });
    }

    async function request(url, options = {}) {
        const headers = Object.assign({
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        }, options.headers || {});

        if (options.json) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.json);
            delete options.json;
        }

        const response = await fetch(url, { ...options, headers, credentials: 'same-origin' });
        let payload = null;
        try {
            payload = await response.json();
        } catch {
            payload = null;
        }

        if (!response.ok) {
            const message = payload?.message
                || payload?.errors?.files?.[0]
                || payload?.errors?.file?.[0]
                || Object.values(payload?.errors || {}).flat()[0]
                || `Request failed (${response.status})`;
            throw new Error(message);
        }

        return payload;
    }

    function updateStats(stats = {}) {
        root.querySelectorAll('[data-stat]').forEach((node) => {
            const key = node.getAttribute('data-stat');
            if (key && stats[key] !== undefined) {
                node.textContent = String(stats[key]);
            }
        });
    }

    function showSkeleton(count = 10) {
        els.skeleton.hidden = false;
        els.grid.hidden = true;
        els.empty.hidden = true;
        els.skeleton.innerHTML = Array.from({ length: count }).map(() => `
            <div class="gallery-skeleton-card">
                <div class="gallery-skeleton-card__thumb"></div>
                <div class="gallery-skeleton-card__line"></div>
                <div class="gallery-skeleton-card__line" style="width:55%"></div>
            </div>
        `).join('');
    }

    function hideSkeleton() {
        els.skeleton.hidden = true;
        els.skeleton.innerHTML = '';
        els.grid.hidden = false;
    }

    function syncBulkBar() {
        const count = state.selected.size;
        els.selectedCount.textContent = String(count);
        els.bulkBar.hidden = count === 0;
        els.bulkActive.hidden = state.trash === 'bin';
        els.bulkBin.hidden = state.trash !== 'bin';
        els.selectAll.checked = count > 0 && count === state.items.length;
    }

    function cardMarkup(item) {
        const selected = state.selected.has(item.id) ? 'is-selected' : '';
        const checked = state.selected.has(item.id) ? 'checked' : '';
        const thumb = item.is_image
            ? `<img src="${escapeHtml(resolveMediaUrl(item.file_url))}" alt="${escapeHtml(item.original_name)}" loading="lazy" data-thumb-fallback>`
            : `<div class="text-xs font-semibold uppercase tracking-wide text-slate-500">${escapeHtml((item.file_extension || item.kind || 'file').toUpperCase())}</div>`;

        const menuItems = state.trash === 'bin'
            ? `
                <button type="button" data-action="restore" data-id="${item.id}">Restore</button>
                <button type="button" data-action="force-delete" data-id="${item.id}">Delete forever</button>
              `
            : `
                <button type="button" data-action="view" data-id="${item.id}">View</button>
                <button type="button" data-action="rename" data-id="${item.id}">Rename</button>
                <button type="button" data-action="copy-url" data-id="${item.id}">Copy URL</button>
                <button type="button" data-action="copy-path" data-id="${item.id}">Copy path</button>
                <button type="button" data-action="download" data-id="${item.id}">Download</button>
                <button type="button" data-action="delete" data-id="${item.id}">Move to bin</button>
              `;

        return `
            <article class="gallery-card ${selected}" data-id="${item.id}">
                <label class="gallery-card__check">
                    <input type="checkbox" data-select="${item.id}" ${checked}>
                </label>
                <div class="gallery-card__menu">
                    <button type="button" class="gallery-card__menu-btn" data-menu-toggle="${item.id}" aria-label="Actions">⋯</button>
                    <div class="gallery-card__dropdown" data-menu="${item.id}">${menuItems}</div>
                </div>
                <div class="gallery-card__thumb" data-action="view" data-id="${item.id}">${thumb}</div>
                <div class="gallery-card__body">
                    <div class="gallery-card__name" title="${escapeHtml(item.original_name)}">${escapeHtml(item.original_name)}</div>
                    <div class="gallery-card__meta">${item.variant === 'adjusted' ? 'Adjusted · ' : (item.variant === 'original' && item.parent_id ? 'Original · ' : '')}${escapeHtml(item.human_size)}${item.dimensions ? ' · ' + escapeHtml(item.dimensions) : ''} · ${escapeHtml(item.created_at_human || '')}</div>
                </div>
            </article>
        `;
    }

    function renderItems() {
        els.grid.setAttribute('data-view', state.view);
        document.querySelectorAll('[data-view]').forEach((btn) => {
            btn.classList.toggle('is-active', btn.getAttribute('data-view') === state.view);
        });

        if (!state.items.length) {
            els.grid.innerHTML = '';
            els.empty.hidden = false;
            els.pagination.innerHTML = '';
            syncBulkBar();
            return;
        }

        els.empty.hidden = true;
        els.grid.innerHTML = state.items.map(cardMarkup).join('');
        els.grid.querySelectorAll('img[data-thumb-fallback]').forEach((img) => {
            img.addEventListener('error', () => {
                const fallback = document.createElement('div');
                fallback.className = 'gallery-card__missing';
                fallback.textContent = 'Unavailable';
                img.replaceWith(fallback);
            }, { once: true });
        });
        renderPagination();
        syncBulkBar();
    }

    function renderPagination() {
        if (state.lastPage <= 1) {
            els.pagination.innerHTML = `<p class="text-sm text-slate-500">${state.total} file(s)</p>`;
            return;
        }

        const pages = [];
        for (let i = 1; i <= state.lastPage; i += 1) {
            if (i === 1 || i === state.lastPage || Math.abs(i - state.page) <= 2) {
                pages.push(i);
            } else if (pages[pages.length - 1] !== '…') {
                pages.push('…');
            }
        }

        els.pagination.innerHTML = `
            <p class="text-sm text-slate-500">Showing page ${state.page} of ${state.lastPage} · ${state.total} file(s)</p>
            <div class="gallery-pagination__pages">
                <button type="button" data-page="${Math.max(1, state.page - 1)}" ${state.page <= 1 ? 'disabled' : ''}>Prev</button>
                ${pages.map((p) => (p === '…'
                    ? '<span class="px-2 text-slate-400">…</span>'
                    : `<button type="button" data-page="${p}" class="${p === state.page ? 'is-active' : ''}">${p}</button>`)).join('')}
                <button type="button" data-page="${Math.min(state.lastPage, state.page + 1)}" ${state.page >= state.lastPage ? 'disabled' : ''}>Next</button>
            </div>
        `;
    }

    async function loadItems(page = state.page) {
        state.loading = true;
        state.page = page;
        showSkeleton();

        const params = new URLSearchParams({
            page: String(state.page),
            per_page: els.perPage.value || '24',
            search: els.search.value.trim(),
            kind: els.kind.value,
            sort: els.sort.value,
            trash: state.trash,
        });

        try {
            const payload = await request(`${endpoints.list}?${params.toString()}`);
            state.items = normalizeItems(payload.data || []);
            state.page = payload.meta?.current_page || 1;
            state.lastPage = payload.meta?.last_page || 1;
            state.total = payload.meta?.total || 0;
            state.selected.clear();
            if (payload.stats) updateStats(payload.stats);
            hideSkeleton();
            renderItems();
        } catch (error) {
            hideSkeleton();
            toast('error', error.message || 'Failed to load gallery.');
        } finally {
            state.loading = false;
        }
    }

    function findItem(id) {
        return state.items.find((item) => Number(item.id) === Number(id)) || null;
    }

    async function copyText(text, successMessage) {
        try {
            await navigator.clipboard.writeText(text);
            toast('success', successMessage);
        } catch {
            toast('error', 'Unable to copy to clipboard.');
        }
    }

    async function confirmAction({ title, text, confirmButtonText = 'Confirm', icon = 'warning' }) {
        if (!window.Swal) {
            return window.confirm(text || title);
        }
        const result = await window.Swal.fire({
            title,
            text,
            icon,
            showCancelButton: true,
            confirmButtonText,
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#64748b',
        });
        return result.isConfirmed;
    }

    async function renameItem(item) {
        let name = item.original_name;
        if (window.Swal) {
            const result = await window.Swal.fire({
                title: 'Rename file',
                input: 'text',
                inputValue: item.original_name,
                showCancelButton: true,
                confirmButtonText: 'Save',
                inputValidator: (value) => (!value || !value.trim() ? 'Name is required' : null),
            });
            if (!result.isConfirmed) return;
            name = result.value;
        } else {
            name = window.prompt('Rename file', item.original_name);
            if (!name) return;
        }

        try {
            const payload = await request(`/admin/gallery/${item.id}`, {
                method: 'PUT',
                json: { original_name: name.trim() },
            });
            toast('success', payload.message || 'Renamed.');
            await loadItems(state.page);
            if (state.previewIndex >= 0 && payload.data) {
                openPreview(payload.data);
            }
        } catch (error) {
            toast('error', error.message || 'Rename failed.');
        }
    }

    function fillPreview(item) {
        const fileUrl = resolveMediaUrl(item.file_url || item.file_path);

        els.previewTitle.textContent = item.original_name || 'Untitled';
        els.previewSub.textContent = `${item.kind || 'file'} · ${item.source || 'gallery'}${item.uploader_name ? ' · ' + item.uploader_name : ''}`;
        document.getElementById('meta-dimensions').textContent = item.dimensions || '—';
        document.getElementById('meta-size').textContent = item.human_size || '—';
        document.getElementById('meta-mime').textContent = item.mime_type || '—';
        document.getElementById('meta-date').textContent = item.created_at_formatted || '—';
        document.getElementById('meta-path').textContent = item.file_path || '—';
        document.getElementById('meta-url').textContent = fileUrl || '—';

        els.previewBroken.hidden = true;
        els.previewBroken.classList.remove('is-visible');
        els.previewImage.classList.remove('is-broken');

        if (item.is_image) {
            els.previewImage.hidden = false;
            els.previewFile.hidden = true;
            els.previewImage.onload = () => {
                els.previewBroken.hidden = true;
                els.previewBroken.classList.remove('is-visible');
                els.previewImage.classList.remove('is-broken');
            };
            els.previewImage.onerror = () => {
                els.previewImage.classList.add('is-broken');
                els.previewBroken.hidden = false;
                els.previewBroken.classList.add('is-visible');
            };
            els.previewImage.src = fileUrl;
            els.previewImage.alt = item.original_name || '';
        } else {
            els.previewImage.hidden = true;
            els.previewImage.removeAttribute('src');
            els.previewFile.hidden = false;
            els.previewFile.innerHTML = `<p class="text-lg font-semibold">${escapeHtml((item.file_extension || item.kind || 'file').toUpperCase())}</p><p class="mt-2 text-sm text-slate-300">${escapeHtml(item.original_name || '')}</p>`;
        }

        const isBin = state.trash === 'bin' || item.is_trashed;
        els.previewActions.innerHTML = isBin
            ? `
                <button type="button" class="gallery-action-btn" data-preview-action="restore">Restore</button>
                <button type="button" class="gallery-action-btn gallery-action-btn--danger" data-preview-action="force-delete">Delete forever</button>
              `
            : `
                <button type="button" class="gallery-action-btn" data-preview-action="download">Download</button>
                <button type="button" class="gallery-action-btn" data-preview-action="rename">Rename</button>
                <button type="button" class="gallery-action-btn" data-preview-action="copy-url">Copy URL</button>
                <button type="button" class="gallery-action-btn" data-preview-action="copy-path">Copy path</button>
                <button type="button" class="gallery-action-btn gallery-action-btn--danger" data-preview-action="delete">Move to bin</button>
              `;

        if (els.previewPrev) els.previewPrev.hidden = state.previewIndex <= 0;
        if (els.previewNext) els.previewNext.hidden = state.previewIndex < 0 || state.previewIndex >= state.items.length - 1;
    }

    async function openPreview(item) {
        if (!item) return;
        state.previewIndex = state.items.findIndex((row) => Number(row.id) === Number(item.id));
        els.modal.hidden = false;
        els.modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        fillPreview({ ...item, file_url: resolveMediaUrl(item.file_url) });

        try {
            const payload = await request(`/admin/gallery/${item.id}`);
            if (payload?.data) {
                const fresh = normalizeItems([payload.data])[0];
                const idx = state.items.findIndex((row) => Number(row.id) === Number(fresh.id));
                if (idx >= 0) state.items[idx] = fresh;
                if (state.previewIndex >= 0 && Number(state.items[state.previewIndex]?.id) === Number(fresh.id)) {
                    fillPreview(fresh);
                }
            }
        } catch {
            // Keep the list payload already rendered in the modal.
        }
    }

    function closePreview() {
        els.modal.hidden = true;
        els.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        state.previewIndex = -1;
        els.previewImage.onload = null;
        els.previewImage.onerror = null;
        els.previewImage.removeAttribute('src');
        els.previewImage.classList.remove('is-broken');
        if (els.previewBroken) {
            els.previewBroken.hidden = true;
            els.previewBroken.classList.remove('is-visible');
        }
    }

    async function runItemAction(action, item) {
        if (!item) return;

        if (action === 'view') {
            openPreview(item);
            return;
        }
        if (action === 'copy-url') {
            await copyText(resolveMediaUrl(item.file_url), 'Image URL copied.');
            return;
        }
        if (action === 'copy-path') {
            await copyText(item.file_path, 'File path copied.');
            return;
        }
        if (action === 'download') {
            window.location.href = `/admin/gallery/${item.id}/download`;
            return;
        }
        if (action === 'rename') {
            await renameItem(item);
            return;
        }
        if (action === 'delete') {
            const ok = await confirmAction({
                title: 'Move to bin?',
                text: 'The file will be moved to the bin and can be restored later.',
                confirmButtonText: 'Move to bin',
            });
            if (!ok) return;
            try {
                const payload = await request(`/admin/gallery/${item.id}`, { method: 'DELETE' });
                toast('success', payload.message || 'Moved to bin.');
                closePreview();
                await loadItems(state.page);
            } catch (error) {
                toast('error', error.message);
            }
            return;
        }
        if (action === 'restore') {
            try {
                const payload = await request(`/admin/gallery/${item.id}/restore`, { method: 'PATCH' });
                toast('success', payload.message || 'Restored.');
                closePreview();
                await loadItems(state.page);
            } catch (error) {
                toast('error', error.message);
            }
            return;
        }
        if (action === 'force-delete') {
            const ok = await confirmAction({
                title: 'Delete permanently?',
                text: 'This cannot be undone. The file will be removed from disk.',
                confirmButtonText: 'Delete forever',
                icon: 'error',
            });
            if (!ok) return;
            try {
                const payload = await request(`/admin/gallery/${item.id}/force`, { method: 'DELETE' });
                toast('success', payload.message || 'Deleted forever.');
                closePreview();
                await loadItems(state.page);
            } catch (error) {
                toast('error', error.message);
            }
        }
    }

    async function uploadFiles(fileList) {
        const files = [...fileList];
        if (!files.length) return;

        const formData = new FormData();
        files.forEach((file) => formData.append('files[]', file));
        formData.append('source', 'gallery');

        els.dropzone.hidden = false;
        els.uploadProgress.hidden = false;
        els.uploadBar.style.transform = 'scaleX(0.08)';
        els.uploadLabel.textContent = `Uploading 0/${files.length}…`;

        try {
            await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', endpoints.store);
                xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.onprogress = (event) => {
                    if (!event.lengthComputable) return;
                    const pct = Math.max(0.08, event.loaded / event.total);
                    els.uploadBar.style.transform = `scaleX(${pct})`;
                    els.uploadLabel.textContent = `Uploading ${Math.round(pct * 100)}%`;
                };
                xhr.onload = () => {
                    let payload = null;
                    try { payload = JSON.parse(xhr.responseText || '{}'); } catch { payload = null; }
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve(payload);
                        return;
                    }
                    reject(new Error(payload?.message || payload?.errors?.files?.[0] || `Upload failed (${xhr.status})`));
                };
                xhr.onerror = () => reject(new Error('Network error while uploading.'));
                xhr.send(formData);
            }).then(async (payload) => {
                toast('success', payload.message || 'Upload complete.');
                if (payload.stats) updateStats(payload.stats);
                state.trash = 'active';
                document.querySelectorAll('[data-trash]').forEach((btn) => {
                    btn.classList.toggle('is-active', btn.getAttribute('data-trash') === 'active');
                });
                await loadItems(1);
            });
        } catch (error) {
            toast('error', error.message || 'Upload failed.');
        } finally {
            els.uploadProgress.hidden = true;
            els.fileInput.value = '';
        }
    }

    // Events
    let searchTimer = null;
    els.search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadItems(1), 280);
    });
    els.kind.addEventListener('change', () => loadItems(1));
    els.sort.addEventListener('change', () => loadItems(1));
    els.perPage.addEventListener('change', () => loadItems(1));

    document.getElementById('gallery-refresh')?.addEventListener('click', () => loadItems(state.page));

    document.querySelectorAll('[data-view]').forEach((btn) => {
        btn.addEventListener('click', () => {
            state.view = btn.getAttribute('data-view') || 'grid';
            localStorage.setItem('gallery.view', state.view);
            renderItems();
        });
    });

    document.querySelectorAll('[data-trash]').forEach((btn) => {
        btn.addEventListener('click', () => {
            state.trash = btn.getAttribute('data-trash') || 'active';
            document.querySelectorAll('[data-trash]').forEach((node) => {
                node.classList.toggle('is-active', node === btn);
            });
            state.selected.clear();
            loadItems(1);
        });
    });

    els.pagination.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-page]');
        if (!btn || btn.disabled) return;
        loadItems(Number(btn.getAttribute('data-page')));
    });

    els.grid.addEventListener('click', async (event) => {
        const menuToggle = event.target.closest('[data-menu-toggle]');
        if (menuToggle) {
            event.stopPropagation();
            const id = menuToggle.getAttribute('data-menu-toggle');
            document.querySelectorAll('[data-menu]').forEach((menu) => {
                menu.classList.toggle('is-open', menu.getAttribute('data-menu') === id && !menu.classList.contains('is-open'));
            });
            return;
        }

        const actionBtn = event.target.closest('[data-action]');
        if (actionBtn) {
            const action = actionBtn.getAttribute('data-action');
            const item = findItem(actionBtn.getAttribute('data-id'));
            document.querySelectorAll('[data-menu]').forEach((menu) => menu.classList.remove('is-open'));
            await runItemAction(action, item);
        }
    });

    els.grid.addEventListener('change', (event) => {
        const checkbox = event.target.closest('[data-select]');
        if (!checkbox) return;
        const id = Number(checkbox.getAttribute('data-select'));
        if (checkbox.checked) state.selected.add(id);
        else state.selected.delete(id);
        renderItems();
    });

    els.selectAll.addEventListener('change', () => {
        if (els.selectAll.checked) {
            state.items.forEach((item) => state.selected.add(item.id));
        } else {
            state.selected.clear();
        }
        renderItems();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.gallery-card__menu')) {
            document.querySelectorAll('[data-menu]').forEach((menu) => menu.classList.remove('is-open'));
        }
    });

    function toggleDropzone(force) {
        const show = typeof force === 'boolean' ? force : els.dropzone.hidden;
        els.dropzone.hidden = !show;
    }

    document.getElementById('gallery-open-upload')?.addEventListener('click', () => toggleDropzone(true));
    document.getElementById('gallery-empty-upload')?.addEventListener('click', () => toggleDropzone(true));
    document.getElementById('gallery-browse-btn')?.addEventListener('click', () => els.fileInput.click());
    els.fileInput.addEventListener('change', () => uploadFiles(els.fileInput.files));

    ['dragenter', 'dragover'].forEach((type) => {
        els.dropzone.addEventListener(type, (event) => {
            event.preventDefault();
            els.dropzone.classList.add('is-dragover');
        });
    });
    ['dragleave', 'drop'].forEach((type) => {
        els.dropzone.addEventListener(type, (event) => {
            event.preventDefault();
            els.dropzone.classList.remove('is-dragover');
        });
    });
    els.dropzone.addEventListener('drop', (event) => {
        uploadFiles(event.dataTransfer?.files || []);
    });

    // Whole-page drag highlight when dropzone open or always allow drop on page body? keep on dropzone only.

    root.querySelector('#gallery-bulk-bar')?.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-bulk]');
        if (!btn) return;
        const ids = [...state.selected];
        if (!ids.length) return;
        const action = btn.getAttribute('data-bulk');

        if (action === 'delete') {
            const ok = await confirmAction({
                title: `Move ${ids.length} file(s) to bin?`,
                text: 'You can restore them later from the Bin tab.',
                confirmButtonText: 'Move to bin',
            });
            if (!ok) return;
            try {
                const payload = await request(endpoints.bulkDelete, { method: 'POST', json: { ids } });
                toast('success', payload.message);
                await loadItems(state.page);
            } catch (error) {
                toast('error', error.message);
            }
        }

        if (action === 'restore') {
            try {
                const payload = await request(endpoints.bulkRestore, { method: 'POST', json: { ids } });
                toast('success', payload.message);
                await loadItems(state.page);
            } catch (error) {
                toast('error', error.message);
            }
        }

        if (action === 'force') {
            const ok = await confirmAction({
                title: `Permanently delete ${ids.length} file(s)?`,
                text: 'This cannot be undone.',
                confirmButtonText: 'Delete forever',
                icon: 'error',
            });
            if (!ok) return;
            try {
                const payload = await request(endpoints.bulkForceDelete, { method: 'POST', json: { ids } });
                toast('success', payload.message);
                await loadItems(state.page);
            } catch (error) {
                toast('error', error.message);
            }
        }
    });

    els.modal.addEventListener('click', async (event) => {
        if (event.target.closest('[data-close-modal]')) {
            closePreview();
            return;
        }
        const actionBtn = event.target.closest('[data-preview-action]');
        if (actionBtn && state.previewIndex >= 0) {
            await runItemAction(actionBtn.getAttribute('data-preview-action'), state.items[state.previewIndex]);
        }
    });

    document.getElementById('gallery-preview-prev')?.addEventListener('click', () => {
        if (state.previewIndex <= 0) return;
        openPreview(state.items[state.previewIndex - 1]);
    });
    document.getElementById('gallery-preview-next')?.addEventListener('click', () => {
        if (state.previewIndex < 0 || state.previewIndex >= state.items.length - 1) return;
        openPreview(state.items[state.previewIndex + 1]);
    });

    document.addEventListener('keydown', (event) => {
        if (els.modal.hidden) return;
        if (event.key === 'Escape') closePreview();
        if (event.key === 'ArrowLeft') document.getElementById('gallery-preview-prev')?.click();
        if (event.key === 'ArrowRight') document.getElementById('gallery-preview-next')?.click();
    });

    // Init — ensure modal starts closed even if CSS caches lag.
    closePreview();
    loadItems(1);
}());
