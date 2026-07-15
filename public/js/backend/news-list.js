document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('news-table-body');
    const bulkBar = document.getElementById('news-bulk-bar');
    const selectAll = document.getElementById('news-select-all');
    const selectedCountEl = document.getElementById('news-selected-count');
    const bulkActionsActive = document.getElementById('news-bulk-actions-active');
    const bulkActionsBin = document.getElementById('news-bulk-actions-bin');
    const trashToggle = document.querySelector('.blog-trash-toggle');
    const drawerTrashInput = document.getElementById('drawer-trash-filter');
    const filterToggleBtn = document.getElementById('btn-toggle-filters');
    const filterBadge = document.getElementById('news-filter-badge');
    let currentTrash = 'active';
    const selectedIds = new Set();

    const statusClass = (status) => `news-status-badge--${status || 'draft'}`;

    const countActiveFilters = () => {
        const form = document.getElementById('filter-drawer-form');
        if (!form) return 0;
        let count = 0;
        const status = form.querySelector('[name="filters[status]"]')?.value;
        if (status) count += 1;
        const author = form.querySelector('[name="filters[author_id]"]')?.value;
        if (author) count += 1;
        const tag = form.querySelector('[name="filters[tag_id]"]')?.value;
        if (tag) count += 1;
        const dateFrom = form.querySelector('[name="filters[date_from]"]')?.value;
        const dateTo = form.querySelector('[name="filters[date_to]"]')?.value;
        if (dateFrom || dateTo) count += 1;
        const createdFrom = form.querySelector('[name="filters[created_from]"]')?.value;
        const createdTo = form.querySelector('[name="filters[created_to]"]')?.value;
        if (createdFrom || createdTo) count += 1;
        ['filters[is_featured]', 'filters[is_breaking]', 'filters[is_trending]'].forEach((name) => {
            if (form.querySelector(`[name="${name}"]`)?.value) count += 1;
        });

        const categorySelect = form.querySelector('#drawer-category-filter');
        if (categorySelect?.tomselect) {
            if ((categorySelect.tomselect.getValue() || []).length) count += 1;
        } else if (categorySelect) {
            const selected = [...categorySelect.selectedOptions].filter((o) => o.value);
            if (selected.length) count += 1;
        }
        return count;
    };

    const updateFilterBadge = () => {
        const count = countActiveFilters();
        if (filterBadge) {
            filterBadge.textContent = String(count);
            filterBadge.classList.toggle('is-visible', count > 0);
        }
        filterToggleBtn?.classList.toggle('is-active', count > 0);
    };
    const updateBulkBar = () => {
        const count = selectedIds.size;
        if (bulkBar) bulkBar.hidden = count === 0 && !selectAll;
        if (selectedCountEl) selectedCountEl.textContent = String(count);
        if (bulkBar) bulkBar.hidden = count === 0;
        if (selectAll) {
            const checkboxes = tableBody?.querySelectorAll('.blog-row-check') || [];
            selectAll.checked = checkboxes.length > 0 && count === checkboxes.length;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }
    };

    const syncTrashUi = (trash) => {
        currentTrash = trash;
        if (drawerTrashInput) drawerTrashInput.value = trash;
        trashToggle?.querySelectorAll('button').forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.trash === trash);
        });
        if (bulkActionsActive) bulkActionsActive.hidden = trash === 'bin';
        if (bulkActionsBin) bulkActionsBin.hidden = trash !== 'bin';
        selectedIds.clear();
        updateBulkBar();
    };

    const newsTable = new AjaxTable({
        containerSelector: '#ajax-table-container',
        apiUrl: window.newsApiUrl,
        tableBodySelector: '#news-table-body',
        paginationSelector: '#news-pagination',
        searchSelector: '#news-search',
        perPageSelector: '#news-per-page',
        filterDrawerSelector: '#filter-drawer',
        filterToggleSelector: '#btn-toggle-filters',
        filterDrawerFormSelector: '#filter-drawer-form',
        loadingSelector: '#news-loading',
        emptySelector: '#news-empty',
        skeletonColumns: 10,
        defaultSort: 'id',
        defaultDirection: 'desc',
        onFetchSuccess: () => {
            selectedIds.clear();
            updateBulkBar();
            updateFilterBadge();
            document.querySelectorAll('.blog-sort-btn').forEach((btn) => {
                btn.classList.toggle('is-active', btn.dataset.sortKey === newsTable.sort);
            });
        },
        rowTemplate: (item, index, meta) => {
            const showUrl = `${window.newsIndexUrl}/${item.id}`;
            const editUrl = `${showUrl}/edit`;
            const page = Number(meta?.current_page || newsTable.page || 1);
            const perPage = Number(meta?.per_page || newsTable.per_page || 10);
            const total = Number(meta?.total ?? 0);
            const offset = (page - 1) * perPage + index;
            const serial = newsTable.sort === 'id' && newsTable.direction === 'desc'
                ? Math.max(total - offset, 0)
                : offset + 1;

            const banner = item.banner_thumbnail_url
                ? `<img src="${item.banner_thumbnail_url}" alt="" class="blog-banner-thumb">`
                : `<div class="blog-banner-placeholder" aria-hidden="true">—</div>`;

            const excerpt = (item.excerpt || '').replace(/<[^>]+>/g, '').trim();
            const excerptPreview = excerpt.length > 80 ? `${excerpt.substring(0, 80)}…` : excerpt;

            const tags = (item.tag_names || []).slice(0, 3).map((t) => `<span class="news-tag-pill">${t}</span>`).join('');
            const moreTags = (item.tag_names || []).length > 3 ? `<span class="news-tag-pill">+${item.tag_names.length - 3}</span>` : '';
            const flags = [
                item.is_breaking ? '<span class="news-flag-pill news-flag-pill--breaking">Breaking</span>' : '',
                item.is_trending ? '<span class="news-flag-pill news-flag-pill--trending">Trending</span>' : '',
                item.is_featured ? '<span class="news-flag-pill news-flag-pill--featured">Featured</span>' : '',
            ].join('');

            const isBin = currentTrash === 'bin';

            return `
                <tr class="news-list-row group transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/80">
                    <td class="px-3 py-2.5 align-middle">
                        <input type="checkbox" class="blog-row-check rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" data-id="${item.id}" value="${item.id}">
                    </td>
                    <td class="px-3 py-2.5 align-middle text-sm text-slate-500 dark:text-slate-400">${serial}</td>
                    <td class="px-4 py-2.5 align-middle">${banner}</td>
                    <td class="px-4 py-2.5 align-middle">
                        <a href="${showUrl}" class="text-sm font-semibold text-slate-900 dark:text-slate-100 blog-title-preview hover:text-indigo-600 dark:hover:text-indigo-400">${item.title || '—'}</a>
                        ${flags ? `<div class="flex flex-wrap gap-1 mt-1">${flags}</div>` : ''}
                        ${excerptPreview ? `<p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">${excerptPreview}</p>` : ''}
                    </td>
                    <td class="px-4 py-2.5 align-middle text-sm">${item.category_name || '<span class="text-slate-400 italic">None</span>'}</td>
                    <td class="px-4 py-2.5 align-middle text-sm whitespace-nowrap">${item.author_name || '—'}</td>
                    <td class="px-4 py-2.5 align-middle">${tags}${moreTags}</td>
                    <td class="px-4 py-2.5 align-middle whitespace-nowrap">
                        <span class="news-status-badge ${statusClass(item.status)}">${item.status_label || item.status}</span>
                    </td>
                    <td class="px-4 py-2.5 align-middle text-sm whitespace-nowrap text-slate-600 dark:text-slate-300">${item.published_at_formatted || '—'}</td>
                    <td class="px-4 py-2.5 align-middle text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="${showUrl}" class="blog-action-btn blog-action-btn--view" title="View"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                            ${isBin ? '' : `<a href="${editUrl}" class="blog-action-btn blog-action-btn--edit" title="Edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></a>`}
                            ${isBin
                                ? `<button type="button" class="js-restore-blog blog-action-btn blog-action-btn--restore" data-id="${item.id}" title="Restore"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>`
                                : `<button type="button" class="js-delete-blog blog-action-btn blog-action-btn--delete" data-id="${item.id}" title="Delete"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>`
                            }
                        </div>
                    </td>
                </tr>
            `;
        },
    });

    const applyTrashFilter = (trash) => {
        syncTrashUi(trash);
        newsTable.filters = { ...newsTable.filters, trash };
        newsTable.page = 1;
        newsTable.fetch();
    };

    const originalFetch = newsTable.fetch.bind(newsTable);
    newsTable.fetch = function patchedFetch() {
        this.filters = { ...this.filters, trash: currentTrash };
        return originalFetch();
    };

    trashToggle?.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-trash]');
        if (!btn) return;
        applyTrashFilter(btn.dataset.trash);
    });

    const filterForm = document.getElementById('filter-drawer-form');

    filterForm?.addEventListener('reset', () => {
        // Native reset fires before values clear; wait a tick then clear Tom Select.
        window.setTimeout(() => {
            const category = document.getElementById('drawer-category-filter');
            if (category?.tomselect) category.tomselect.clear(true);
            const author = document.getElementById('drawer-author-filter');
            if (author?.tomselect) author.tomselect.clear(true);
            const tag = document.getElementById('drawer-tag-filter');
            if (tag?.tomselect) tag.tomselect.clear(true);
            if (drawerTrashInput) drawerTrashInput.value = currentTrash;
            updateFilterBadge();
        }, 0);
    });

    filterForm?.addEventListener('submit', () => {
        if (drawerTrashInput) drawerTrashInput.value = currentTrash;
        window.setTimeout(updateFilterBadge, 0);
    });

    updateFilterBadge();

    document.querySelectorAll('.blog-sort-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.sortKey;
            if (!key) return;
            if (newsTable.sort === key) {
                newsTable.direction = newsTable.direction === 'asc' ? 'desc' : 'asc';
            } else {
                newsTable.sort = key;
                newsTable.direction = 'asc';
            }
            newsTable.page = 1;
            newsTable.fetch();
        });
    });

    document.getElementById('btn-refresh-news')?.addEventListener('click', function onRefresh() {
        const btn = this;
        if (newsTable.loading) return;
        btn.classList.add('is-refreshing');
        btn.disabled = true;
        newsTable.page = 1;
        newsTable.sort = newsTable.defaultSort;
        newsTable.direction = newsTable.defaultDirection;
        newsTable.fetch();
        const watch = setInterval(() => {
            if (!newsTable.loading) {
                clearInterval(watch);
                btn.classList.remove('is-refreshing');
                btn.disabled = false;
            }
        }, 120);
    });

    tableBody?.addEventListener('change', (e) => {
        const checkbox = e.target.closest('.blog-row-check');
        if (!checkbox) return;
        const id = checkbox.dataset.id;
        if (checkbox.checked) selectedIds.add(id);
        else selectedIds.delete(id);
        updateBulkBar();
    });

    selectAll?.addEventListener('change', () => {
        const checkboxes = tableBody?.querySelectorAll('.blog-row-check') || [];
        checkboxes.forEach((cb) => {
            cb.checked = selectAll.checked;
            if (selectAll.checked) selectedIds.add(cb.dataset.id);
            else selectedIds.delete(cb.dataset.id);
        });
        updateBulkBar();
    });

    const submitBulk = (formId) => {
        const form = document.getElementById(formId);
        if (!form || selectedIds.size === 0) return;
        form.querySelectorAll('input[name="ids[]"]').forEach((el) => el.remove());
        selectedIds.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        form.submit();
    };

    document.getElementById('btn-bulk-delete')?.addEventListener('click', () => {
        Swal.fire({
            title: 'Delete selected posts?',
            text: 'Selected news items will be moved to the bin.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, delete',
        }).then((result) => {
            if (result.isConfirmed) submitBulk('bulk-delete-news-form');
        });
    });

    document.getElementById('btn-bulk-restore')?.addEventListener('click', () => {
        submitBulk('bulk-restore-news-form');
    });

    tableBody?.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.js-delete-blog');
        if (deleteBtn) {
            const id = deleteBtn.dataset.id;
            Swal.fire({
                title: 'Delete news item?',
                text: 'This will move the post to the bin.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Yes, delete',
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-news-form');
                    form.action = `${window.newsIndexUrl}/${id}`;
                    form.submit();
                }
            });
            return;
        }

        const restoreBtn = e.target.closest('.js-restore-blog');
        if (restoreBtn) {
            const id = restoreBtn.dataset.id;
            const form = document.getElementById('restore-news-form');
            form.action = `${window.newsRestoreUrl}/${id}/restore`;
            form.submit();
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'bin') {
        applyTrashFilter('bin');
    } else {
        syncTrashUi('active');
    }
});
