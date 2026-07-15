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
        uploading: false,
        previewIndex: -1,
        dragDepth: 0,
    };

    const els = {
        grid: document.getElementById('gallery-grid'),
        skeleton: document.getElementById('gallery-skeleton'),
        empty: document.getElementById('gallery-empty'),
        emptyTitle: document.getElementById('gallery-empty-title'),
        emptyText: document.getElementById('gallery-empty-text'),
        emptyDrop: document.getElementById('gallery-empty-drop'),
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
        dragOverlay: document.getElementById('gallery-drag-overlay'),
        modal: document.getElementById('gallery-preview-modal'),
        previewImage: document.getElementById('gallery-preview-image'),
        previewBroken: document.getElementById('gallery-preview-broken'),
        previewFile: document.getElementById('gallery-preview-file'),
        previewTitle: document.getElementById('gallery-preview-title'),
        previewSub: document.getElementById('gallery-preview-sub'),
        previewActions: document.getElementById('gallery-preview-actions'),
        previewPrev: document.getElementById('gallery-preview-prev'),
        previewNext: document.getElementById('gallery-preview-next'),
        variantToggle: document.getElementById('gallery-variant-toggle'),
        viewToggle: document.getElementById('gallery-view-toggle'),
        trashToggle: document.getElementById('gallery-trash-toggle'),
        stats: document.getElementById('gallery-stats'),
        pending: document.getElementById('gallery-pending'),
        pendingGrid: document.getElementById('gallery-pending-grid'),
    };

    /** @type {Array<{id:string,originalFile:File,displayFile:File,previewUrl:string,kind:string,isImage:boolean,isEdited:boolean,status:string,progress:number,error:?string}>} */
    let pendingItems = [];
    let savingAll = false;

    // Which URL the preview modal is currently showing.
    let previewVariant = 'modified';

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

    function syncStatFilters() {
        if (!els.stats) return;
        const active = state.trash === 'bin' ? 'bin' : (els.kind.value || 'all');
        els.stats.querySelectorAll('[data-stat-filter]').forEach((btn) => {
            const value = btn.getAttribute('data-stat-filter');
            const on = value === active;
            btn.classList.toggle('is-active', on);
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    }

    function syncViewToggle() {
        els.viewToggle?.querySelectorAll('[data-view]').forEach((btn) => {
            const on = btn.getAttribute('data-view') === state.view;
            btn.classList.toggle('is-active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    function syncTrashToggle() {
        els.trashToggle?.querySelectorAll('[data-trash]').forEach((btn) => {
            const on = btn.getAttribute('data-trash') === state.trash;
            btn.classList.toggle('is-active', on);
        });
    }

    function showSkeleton(count = 10) {
        els.skeleton.hidden = false;
        els.grid.hidden = true;
        els.empty.hidden = true;
        els.skeleton.setAttribute('data-mode', state.view);
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
        els.selectAll.indeterminate = count > 0 && count < state.items.length;
    }

    function kindIcon(kind) {
        const k = (kind || 'file').toLowerCase();
        if (k === 'video') {
            return '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>';
        }
        if (k === 'document') {
            return '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
        }
        return '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>';
    }

    function cardMarkup(item) {
        const selected = state.selected.has(item.id) ? 'is-selected' : '';
        const checked = state.selected.has(item.id) ? 'checked' : '';
        const ext = (item.file_extension || item.kind || 'file').toUpperCase();
        const editedBadge = item.has_modification
            ? '<span class="gallery-card__edited">Edited</span>'
            : '';
        const thumb = item.is_image
            ? `<img src="${escapeHtml(resolveMediaUrl(item.file_url))}" alt="${escapeHtml(item.original_name)}" loading="lazy" data-thumb-fallback>
               <span class="gallery-card__badge">${escapeHtml(ext)}</span>${editedBadge}`
            : `<div class="gallery-card__kind">
                    <span class="gallery-card__kind-icon">${kindIcon(item.kind)}</span>
                    <span class="gallery-card__kind-label">${escapeHtml(ext)}</span>
               </div>`;

        const menuItems = state.trash === 'bin'
            ? `
                <button type="button" data-action="restore" data-id="${item.id}">Restore</button>
                <button type="button" data-action="force-delete" data-id="${item.id}">Delete forever</button>
              `
            : `
                <button type="button" data-action="view" data-id="${item.id}">View</button>
                ${item.is_image ? `<button type="button" data-action="edit" data-id="${item.id}">Edit image</button>` : ''}
                <button type="button" data-action="rename" data-id="${item.id}">Rename</button>
                <button type="button" data-action="copy-url" data-id="${item.id}">Copy URL</button>
                <button type="button" data-action="download" data-id="${item.id}">Download</button>
                ${item.has_modification ? `<button type="button" data-action="download-original" data-id="${item.id}">Download original</button>` : ''}
                ${item.has_modification ? `<button type="button" data-action="revert" data-id="${item.id}">Revert to original</button>` : ''}
                <button type="button" data-action="delete" data-id="${item.id}">Move to bin</button>
              `;

        return `
            <article class="gallery-card ${selected}" data-id="${item.id}">
                <label class="gallery-card__check">
                    <input type="checkbox" data-select="${item.id}" ${checked} aria-label="Select ${escapeHtml(item.original_name)}">
                </label>
                <div class="gallery-card__menu">
                    <button type="button" class="gallery-card__menu-btn" data-menu-toggle="${item.id}" aria-label="Actions" aria-haspopup="true">⋯</button>
                    <div class="gallery-card__dropdown" data-menu="${item.id}">${menuItems}</div>
                </div>
                <div class="gallery-card__thumb" data-action="view" data-id="${item.id}">${thumb}</div>
                <div class="gallery-card__body">
                    <div class="gallery-card__name" title="${escapeHtml(item.original_name)}">${escapeHtml(item.original_name)}</div>
                    <div class="gallery-card__meta">${item.has_modification ? 'Edited · ' : ''}${escapeHtml(item.human_size)}${item.dimensions ? ' · ' + escapeHtml(item.dimensions) : ''} · ${escapeHtml(item.created_at_human || '')}</div>
                </div>
            </article>
        `;
    }

    function emptyCopy() {
        const searching = els.search.value.trim().length > 0;
        const filtered = els.kind.value !== 'all';

        if (state.trash === 'bin') {
            return {
                title: searching || filtered ? 'No matches in bin' : 'Bin is empty',
                text: searching || filtered
                    ? 'Try a different search or clear filters.'
                    : 'Deleted files will appear here until permanently removed.',
                filtered: true,
            };
        }

        if (searching || filtered) {
            return {
                title: 'No matching media',
                text: 'Try a different search term or clear the type filter.',
                filtered: true,
            };
        }

        return {
            title: 'No media found',
            text: 'Upload files to get started. You can review and edit them before saving.',
            filtered: false,
        };
    }

    function renderEmpty() {
        const copy = emptyCopy();
        els.emptyTitle.textContent = copy.title;
        els.emptyText.textContent = copy.text;
        els.empty.classList.toggle('gallery-empty--filtered', copy.filtered);

        // While staging pending uploads, avoid stacking a second empty CTA underneath.
        if (pendingItems.length && !copy.filtered && state.trash !== 'bin') {
            els.empty.hidden = true;
            els.grid.innerHTML = '';
            els.pagination.innerHTML = '';
            return;
        }

        els.empty.hidden = false;
        els.grid.innerHTML = '';
        els.pagination.innerHTML = '';
        if (!copy.filtered) {
            els.dropzone.hidden = true;
        }
    }

    function renderItems() {
        els.grid.setAttribute('data-mode', state.view);
        syncViewToggle();

        if (!state.items.length) {
            renderEmpty();
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
                const badge = img.parentElement?.querySelector('.gallery-card__badge');
                img.replaceWith(fallback);
                if (badge) badge.remove();
            }, { once: true });
        });
        renderPagination();
        syncBulkBar();
    }

    function renderPagination() {
        if (state.lastPage <= 1) {
            els.pagination.innerHTML = state.total
                ? `<p class="text-sm text-slate-500">${state.total} file(s)</p>`
                : '';
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
        syncStatFilters();
        syncTrashToggle();

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
        const hasMod = !!item.has_modification;
        const showOriginal = previewVariant === 'original' && hasMod;
        const displayUrl = resolveMediaUrl(
            showOriginal ? (item.original_url || item.file_url) : item.file_url
        );
        const displayPath = showOriginal
            ? (item.original_file_path || item.file_path)
            : item.file_path;

        els.previewTitle.textContent = item.original_name || 'Untitled';
        els.previewSub.textContent = `${item.kind || 'file'} · ${item.source || 'gallery'}${item.uploader_name ? ' · ' + item.uploader_name : ''}${hasMod ? (showOriginal ? ' · viewing original' : ' · viewing edited') : ''}`;
        document.getElementById('meta-dimensions').textContent = item.dimensions || '—';
        document.getElementById('meta-size').textContent = item.human_size || '—';
        document.getElementById('meta-mime').textContent = item.mime_type || '—';
        document.getElementById('meta-date').textContent = item.created_at_formatted || '—';
        document.getElementById('meta-path').textContent = displayPath || '—';
        document.getElementById('meta-url').textContent = displayUrl || '—';

        if (els.variantToggle) {
            els.variantToggle.hidden = !hasMod || !item.is_image;
            els.variantToggle.querySelectorAll('[data-preview-variant]').forEach((btn) => {
                const on = btn.getAttribute('data-preview-variant') === (showOriginal ? 'original' : 'modified');
                btn.classList.toggle('is-active', on);
            });
        }

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
            els.previewImage.src = displayUrl;
            els.previewImage.alt = item.original_name || '';
        } else {
            els.previewImage.hidden = true;
            els.previewImage.removeAttribute('src');
            els.previewFile.hidden = false;
            els.previewFile.innerHTML = `
                <div class="gallery-card__kind" style="color:#cbd5e1">
                    <span class="gallery-card__kind-icon">${kindIcon(item.kind)}</span>
                    <p class="mt-3 text-lg font-semibold">${escapeHtml((item.file_extension || item.kind || 'file').toUpperCase())}</p>
                    <p class="mt-1 text-sm text-slate-300">${escapeHtml(item.original_name || '')}</p>
                </div>`;
        }

        const isBin = state.trash === 'bin' || item.is_trashed;
        els.previewActions.innerHTML = isBin
            ? `
                <button type="button" class="gallery-action-btn" data-preview-action="restore">Restore</button>
                <button type="button" class="gallery-action-btn gallery-action-btn--danger" data-preview-action="force-delete">Delete forever</button>
              `
            : `
                ${item.is_image ? '<button type="button" class="gallery-action-btn" data-preview-action="edit">Edit image</button>' : ''}
                <button type="button" class="gallery-action-btn" data-preview-action="download">Download</button>
                ${hasMod ? '<button type="button" class="gallery-action-btn" data-preview-action="download-original">Download original</button>' : ''}
                <button type="button" class="gallery-action-btn" data-preview-action="rename">Rename</button>
                <button type="button" class="gallery-action-btn" data-preview-action="copy-url">Copy URL</button>
                ${hasMod ? '<button type="button" class="gallery-action-btn" data-preview-action="revert">Revert to original</button>' : ''}
                <button type="button" class="gallery-action-btn gallery-action-btn--danger" data-preview-action="delete">Move to bin</button>
              `;

        if (els.previewPrev) els.previewPrev.hidden = state.previewIndex <= 0;
        if (els.previewNext) els.previewNext.hidden = state.previewIndex < 0 || state.previewIndex >= state.items.length - 1;
    }

    async function openPreview(item) {
        if (!item) return;
        previewVariant = item.has_modification ? 'modified' : 'original';
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

    async function saveEditedImage(item, file) {
        const formData = new FormData();
        formData.append('file', file);

        const response = await fetch(`${endpoints.editBase}/${item.id}/edit`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: formData,
        });

        let payload = null;
        try { payload = await response.json(); } catch { payload = null; }
        if (!response.ok) {
            throw new Error(payload?.message || payload?.errors?.file?.[0] || `Edit failed (${response.status})`);
        }
        return payload;
    }

    async function openEditorForItem(item) {
        if (!item?.is_image) return;
        if (!window.GalleryImageEditor?.open) {
            toast('error', 'Image editor failed to load.');
            return;
        }

        const sourceUrl = resolveMediaUrl(item.original_url || item.file_url);
        const edited = await window.GalleryImageEditor.open({
            root,
            src: sourceUrl,
            name: item.original_name,
        });

        if (!edited) {
            toast('success', 'Kept original image.');
            return;
        }

        try {
            const payload = await saveEditedImage(item, edited);
            toast('success', payload.message || 'Edited image saved.');
            if (payload.stats) updateStats(payload.stats);
            await loadItems(state.page);
            if (payload.data) {
                previewVariant = 'modified';
                openPreview(payload.data);
            }
        } catch (error) {
            toast('error', error.message || 'Failed to save edited image.');
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
        if (action === 'edit') {
            await openEditorForItem(item);
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
        if (action === 'download-original') {
            window.location.href = `/admin/gallery/${item.id}/download?variant=original`;
            return;
        }
        if (action === 'revert') {
            const ok = await confirmAction({
                title: 'Revert to original?',
                text: 'The edited version will be permanently deleted. The original upload will remain.',
                confirmButtonText: 'Revert',
            });
            if (!ok) return;
            try {
                const payload = await request(`/admin/gallery/${item.id}/revert`, { method: 'POST' });
                toast('success', payload.message || 'Reverted.');
                if (payload.stats) updateStats(payload.stats);
                await loadItems(state.page);
                if (payload.data) openPreview(payload.data);
            } catch (error) {
                toast('error', error.message);
            }
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


    function uid() {
        return 'p_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 9);
    }

    function detectClientKind(file) {
        const type = (file.type || '').toLowerCase();
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if (type.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext)) return 'image';
        if (type.startsWith('video/') || ['mp4', 'webm', 'ogg', 'mov'].includes(ext)) return 'video';
        if (['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z'].includes(ext)) return 'document';
        return 'file';
    }

    function formatBytes(bytes) {
        const n = Number(bytes) || 0;
        if (n < 1024) return n + ' B';
        if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
        return (n / 1048576).toFixed(2) + ' MB';
    }

    function revokePendingUrls(item) {
        if (item && item.previewUrl && String(item.previewUrl).startsWith('blob:')) {
            URL.revokeObjectURL(item.previewUrl);
        }
    }

    function findPending(id) {
        return pendingItems.find((row) => row.id === id) || null;
    }

    function renderPending() {
        if (!els.pending || !els.pendingGrid) return;
        if (!pendingItems.length) {
            els.pending.hidden = true;
            els.pendingGrid.innerHTML = '';
            return;
        }

        els.pending.hidden = false;
        els.pendingGrid.innerHTML = pendingItems.map((item) => {
            const ext = (item.displayFile.name.split('.').pop() || item.kind || 'file').toUpperCase();
            const thumb = item.isImage
                ? `<img src="${escapeHtml(item.previewUrl)}" alt="${escapeHtml(item.displayFile.name)}" loading="lazy">`
                : `<div class="gallery-card__kind"><span class="gallery-card__kind-icon">${kindIcon(item.kind)}</span><span class="gallery-card__kind-label">${escapeHtml(ext)}</span></div>`;
            const statusLabel = item.status === 'saving'
                ? `Saving ${item.progress}%`
                : (item.status === 'error' ? (item.error || 'Failed') : (item.isEdited ? 'Edited · ready' : 'Ready to save'));
            const busy = item.status === 'saving' || savingAll;

            return `
                <article class="gallery-pending-card ${item.status === 'error' ? 'is-error' : ''} ${item.status === 'saving' ? 'is-saving' : ''}" data-pending-id="${item.id}">
                    <div class="gallery-pending-card__thumb">${thumb}
                        ${item.isEdited ? '<span class="gallery-card__edited">Edited</span>' : ''}
                        <span class="gallery-card__badge">${escapeHtml(ext)}</span>
                    </div>
                    <div class="gallery-pending-card__body">
                        <div class="gallery-card__name" title="${escapeHtml(item.displayFile.name)}">${escapeHtml(item.displayFile.name)}</div>
                        <div class="gallery-card__meta">${formatBytes(item.displayFile.size)} · ${escapeHtml(statusLabel)}</div>
                        <div class="gallery-pending-card__progress" ${item.status === 'saving' ? '' : 'hidden'}>
                            <div class="gallery-pending-card__bar" style="transform:scaleX(${Math.max(0.05, (item.progress || 0) / 100)})"></div>
                        </div>
                        <div class="gallery-pending-card__actions">
                            ${item.isImage ? `<button type="button" class="gallery-pending-btn" data-pending-action="edit" data-pending-id="${item.id}" title="Edit" aria-label="Edit" ${busy ? 'disabled' : ''}><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 00.707-.293l9.414-9.414a2 2 0 000-2.828l-2.172-2.172a2 2 0 00-2.828 0L4.293 14.707A1 1 0 004 15.414V20z"/></svg></button>` : ''}
                            <button type="button" class="gallery-pending-btn gallery-pending-btn--danger" data-pending-action="delete" data-pending-id="${item.id}" title="Delete" aria-label="Delete" ${busy ? 'disabled' : ''}><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/></svg></button>
                            <button type="button" class="gallery-pending-btn gallery-pending-btn--primary" data-pending-action="save" data-pending-id="${item.id}" title="Save" aria-label="Save" ${busy ? 'disabled' : ''}><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg></button>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    }

    function stageFiles(fileList) {
        const files = [...(fileList || [])].filter(Boolean);
        if (!files.length) return;

        files.forEach((file) => {
            const kind = detectClientKind(file);
            const isImage = kind === 'image';
            pendingItems.push({
                id: uid(),
                originalFile: file,
                displayFile: file,
                previewUrl: isImage ? URL.createObjectURL(file) : '',
                kind,
                isImage,
                isEdited: false,
                status: 'pending',
                progress: 0,
                error: null,
            });
        });

        state.trash = 'active';
        syncTrashToggle();
        setDropzoneOpen(false);
        renderPending();
        els.fileInput.value = '';
        toast('success', files.length + ' file(s) added to pending uploads.');
    }

    function removePending(id) {
        const index = pendingItems.findIndex((row) => row.id === id);
        if (index < 0) return;
        revokePendingUrls(pendingItems[index]);
        pendingItems.splice(index, 1);
        renderPending();
    }

    function clearPending() {
        pendingItems.forEach(revokePendingUrls);
        pendingItems = [];
        renderPending();
    }

    async function editPendingItem(item) {
        if (!item || !item.isImage || !window.GalleryImageEditor || !window.GalleryImageEditor.open) {
            toast('error', 'Image editor is unavailable.');
            return;
        }

        const edited = await window.GalleryImageEditor.open({
            root,
            src: item.previewUrl || URL.createObjectURL(item.originalFile),
            name: item.originalFile.name,
        });
        if (!edited) return;

        if (item.previewUrl && item.previewUrl.startsWith('blob:') && item.isEdited) {
            URL.revokeObjectURL(item.previewUrl);
        }

        item.displayFile = edited;
        item.previewUrl = URL.createObjectURL(edited);
        item.isEdited = true;
        item.status = 'pending';
        item.error = null;
        item.progress = 0;
        renderPending();
        toast('success', 'Preview updated. Click Save to store it.');
    }

    function commitPendingItem(item) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', item.displayFile);
            formData.append('source', 'gallery');
            if (item.isEdited && item.originalFile && item.originalFile !== item.displayFile) {
                formData.append('original', item.originalFile);
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', endpoints.commit || endpoints.store);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.onprogress = (event) => {
                if (!event.lengthComputable) return;
                item.progress = Math.round((event.loaded / event.total) * 100);
                renderPending();
            };
            xhr.onload = () => {
                let body = null;
                try { body = JSON.parse(xhr.responseText || '{}'); } catch (e) { body = null; }
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(body);
                    return;
                }
                reject(new Error((body && (body.message || (body.errors && body.errors.file && body.errors.file[0]))) || ('Save failed (' + xhr.status + ')')));
            };
            xhr.onerror = () => reject(new Error('Network error while saving.'));
            xhr.send(formData);
        });
    }

    async function savePendingItem(id) {
        const item = findPending(id);
        if (!item || item.status === 'saving') return;
        item.status = 'saving';
        item.progress = 5;
        item.error = null;
        renderPending();
        try {
            const payload = await commitPendingItem(item);
            toast('success', (payload && payload.message) || 'File saved.');
            if (payload && payload.stats) updateStats(payload.stats);
            removePending(id);
            await loadItems(1);
        } catch (error) {
            item.status = 'error';
            item.error = error.message || 'Save failed';
            item.progress = 0;
            renderPending();
            toast('error', item.error);
        }
    }

    async function saveAllPending() {
        if (savingAll || !pendingItems.length) return;
        savingAll = true;
        const snapshot = pendingItems.slice();
        let saved = 0;
        for (const item of snapshot) {
            if (!findPending(item.id)) continue;
            item.status = 'saving';
            item.progress = 5;
            item.error = null;
            renderPending();
            try {
                const payload = await commitPendingItem(item);
                if (payload && payload.stats) updateStats(payload.stats);
                removePending(item.id);
                saved += 1;
            } catch (error) {
                item.status = 'error';
                item.error = error.message || 'Save failed';
                item.progress = 0;
                renderPending();
            }
        }
        savingAll = false;
        renderPending();
        if (saved > 0) {
            toast('success', saved + ' file(s) saved.');
            await loadItems(1);
        }
        if (pendingItems.some((row) => row.status === 'error')) {
            toast('error', 'Some files failed to save. Fix and retry.');
        }
    }


    function setDropzoneOpen(open) {
        els.dropzone.hidden = !open;
        if (open) els.dropzone.classList.remove('is-dragover');
    }

    function openUploader({ preferPicker = false } = {}) {
        if (state.trash === 'bin') {
            state.trash = 'active';
            syncTrashToggle();
            syncStatFilters();
            loadItems(1).then(() => {
                if (preferPicker || state.total > 0) {
                    if (state.total > 0 && !preferPicker) setDropzoneOpen(true);
                    else els.fileInput.click();
                } else {
                    els.fileInput.click();
                }
            });
            return;
        }

        if (!state.items.length && !pendingItems.length && !els.search.value.trim() && els.kind.value === 'all') {
            els.fileInput.click();
            return;
        }

        if (preferPicker) {
            els.fileInput.click();
            return;
        }

        setDropzoneOpen(true);
    }

    // Events
    let searchTimer = null;
    els.search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadItems(1), 280);
    });
    els.kind.addEventListener('change', () => {
        syncStatFilters();
        loadItems(1);
    });
    els.sort.addEventListener('change', () => loadItems(1));
    els.perPage.addEventListener('change', () => loadItems(1));

    document.getElementById('gallery-refresh')?.addEventListener('click', () => loadItems(state.page));

    els.viewToggle?.querySelectorAll('[data-view]').forEach((btn) => {
        btn.addEventListener('click', () => {
            state.view = btn.getAttribute('data-view') || 'grid';
            localStorage.setItem('gallery.view', state.view);
            renderItems();
        });
    });

    els.trashToggle?.querySelectorAll('[data-trash]').forEach((btn) => {
        btn.addEventListener('click', () => {
            state.trash = btn.getAttribute('data-trash') || 'active';
            state.selected.clear();
            setDropzoneOpen(false);
            syncTrashToggle();
            syncStatFilters();
            loadItems(1);
        });
    });

    els.stats?.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-stat-filter]');
        if (!btn) return;
        const filter = btn.getAttribute('data-stat-filter');

        if (filter === 'bin') {
            state.trash = 'bin';
            els.kind.value = 'all';
        } else {
            state.trash = 'active';
            els.kind.value = filter === 'all' ? 'all' : filter;
        }

        state.selected.clear();
        setDropzoneOpen(false);
        syncTrashToggle();
        syncStatFilters();
        loadItems(1);
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
                const shouldOpen = menu.getAttribute('data-menu') === id && !menu.classList.contains('is-open');
                menu.classList.toggle('is-open', shouldOpen);
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
        // Update selection chrome without full re-render (keeps menus/scroll stable).
        const card = checkbox.closest('.gallery-card');
        card?.classList.toggle('is-selected', checkbox.checked);
        syncBulkBar();
    });

    els.selectAll.addEventListener('change', () => {
        if (els.selectAll.checked) {
            state.items.forEach((item) => state.selected.add(item.id));
        } else {
            state.selected.clear();
        }
        renderItems();
    });

    document.getElementById('gallery-clear-selection')?.addEventListener('click', () => {
        state.selected.clear();
        renderItems();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.gallery-card__menu')) {
            document.querySelectorAll('[data-menu]').forEach((menu) => menu.classList.remove('is-open'));
        }
    });

    document.getElementById('gallery-open-upload')?.addEventListener('click', () => openUploader());
    document.getElementById('gallery-empty-upload')?.addEventListener('click', (event) => {
        event.stopPropagation();
        els.fileInput.click();
    });
    document.getElementById('gallery-browse-btn')?.addEventListener('click', () => els.fileInput.click());
    document.getElementById('gallery-browse-btn-secondary')?.addEventListener('click', () => els.fileInput.click());
    document.getElementById('gallery-close-dropzone')?.addEventListener('click', () => setDropzoneOpen(false));
    document.getElementById('gallery-pending-save-all')?.addEventListener('click', () => saveAllPending());
    document.getElementById('gallery-pending-clear')?.addEventListener('click', () => {
        if (!pendingItems.length) return;
        clearPending();
        toast('success', 'Pending uploads cleared.');
    });
    els.pendingGrid?.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-pending-action]');
        if (!btn) return;
        const id = btn.getAttribute('data-pending-id');
        const action = btn.getAttribute('data-pending-action');
        const item = findPending(id);
        if (!item) return;
        if (action === 'delete') {
            removePending(id);
            return;
        }
        if (action === 'edit') {
            await editPendingItem(item);
            return;
        }
        if (action === 'save') {
            await savePendingItem(id);
        }
    });
    els.fileInput.addEventListener('change', () => stageFiles(els.fileInput.files));

    els.variantToggle?.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-preview-variant]');
        if (!btn || state.previewIndex < 0) return;
        previewVariant = btn.getAttribute('data-preview-variant') || 'modified';
        fillPreview(state.items[state.previewIndex]);
    });

    els.emptyDrop?.addEventListener('click', (event) => {
        if (els.empty.classList.contains('gallery-empty--filtered')) return;
        if (event.target.closest('#gallery-empty-upload')) return;
        els.fileInput.click();
    });

    els.emptyDrop?.addEventListener('keydown', (event) => {
        if (els.empty.classList.contains('gallery-empty--filtered')) return;
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            els.fileInput.click();
        }
    });

    ['dragenter', 'dragover'].forEach((type) => {
        els.dropzone.addEventListener(type, (event) => {
            event.preventDefault();
            els.dropzone.classList.add('is-dragover');
        });
        els.emptyDrop?.addEventListener(type, (event) => {
            event.preventDefault();
            if (!els.empty.classList.contains('gallery-empty--filtered')) {
                els.emptyDrop.classList.add('is-dragover');
            }
        });
    });
    ['dragleave', 'drop'].forEach((type) => {
        els.dropzone.addEventListener(type, (event) => {
            event.preventDefault();
            els.dropzone.classList.remove('is-dragover');
        });
        els.emptyDrop?.addEventListener(type, (event) => {
            event.preventDefault();
            els.emptyDrop.classList.remove('is-dragover');
        });
    });
    els.dropzone.addEventListener('drop', (event) => {
        stageFiles(event.dataTransfer?.files || []);
    });
    els.emptyDrop?.addEventListener('drop', (event) => {
        if (els.empty.classList.contains('gallery-empty--filtered')) return;
        stageFiles(event.dataTransfer?.files || []);
    });

    // Page-level drag & drop when not over a filtered empty state.
    function hasFiles(event) {
        return Array.from(event.dataTransfer?.types || []).includes('Files');
    }

    window.addEventListener('dragenter', (event) => {
        if (!hasFiles(event) || savingAll || state.trash === 'bin') return;
        event.preventDefault();
        state.dragDepth += 1;
        if (els.dragOverlay) {
            els.dragOverlay.hidden = false;
            els.dragOverlay.setAttribute('aria-hidden', 'false');
        }
    });
    window.addEventListener('dragover', (event) => {
        if (!hasFiles(event)) return;
        event.preventDefault();
    });
    window.addEventListener('dragleave', (event) => {
        if (!hasFiles(event)) return;
        state.dragDepth = Math.max(0, state.dragDepth - 1);
        if (state.dragDepth === 0 && els.dragOverlay) {
            els.dragOverlay.hidden = true;
            els.dragOverlay.setAttribute('aria-hidden', 'true');
        }
    });
    window.addEventListener('drop', (event) => {
        if (!hasFiles(event)) return;
        event.preventDefault();
        state.dragDepth = 0;
        if (els.dragOverlay) {
            els.dragOverlay.hidden = true;
            els.dragOverlay.setAttribute('aria-hidden', 'true');
        }
        if (state.trash === 'bin' || savingAll) return;
        stageFiles(event.dataTransfer?.files || []);
    });

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

    // Init
    closePreview();
    setDropzoneOpen(false);
    renderPending();
    syncViewToggle();
    syncTrashToggle();
    syncStatFilters();
    loadItems(1);
}());
