document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('exams-table-body');
    const statGridEl = document.getElementById('exam-stat-grid');
    const activeChipsEl = document.getElementById('active-filter-chips');
    let currentTrash = 'active';
    const selection = new window.EmsListUi.ListSelection({
        bodySelector: '#exams-table-body',
        selectAllSelector: '#exams-select-all',
        bulkBarSelector: '#exams-bulk-bar',
        countSelector: '#exams-selected-count',
        checkboxSelector: '.list-row-check',
        activeActionsSelector: '#exams-bulk-actions-active',
        binActionsSelector: '#exams-bulk-actions-bin',
    });

    const statusClassMap = {
        published: 'exam-status-published',
        draft: 'exam-status-draft',
        active: 'exam-status-active',
        inactive: 'exam-status-inactive',
        suspended: 'exam-status-suspended',
    };

    const chipLabels = {
        category_id: { label: 'Category' },
        exam_format: { label: 'Format' },
        status: {
            label: 'Status',
            map: {
                published: 'Published',
                draft: 'Draft',
                active: 'Active',
                inactive: 'Inactive',
                suspended: 'Suspended',
            },
        },
        exam_mode: {
            label: 'Mode',
            map: { standard: 'Standard', practice: 'Practice', proctored: 'Proctored' },
        },
        difficulty_level: {
            label: 'Difficulty',
            map: { easy: 'Easy', medium: 'Medium', hard: 'Hard' },
        },
        sort: {
            label: 'Sort',
            map: {
                'updated_at:desc': 'Recently Updated',
                'title:asc': 'Title A→Z',
                'title:desc': 'Title Z→A',
                'duration:desc': 'Longest',
                'questions_count:desc': 'Most Qs',
                'pass_percentage:asc': 'Lowest Pass%',
            },
        },
    };

    const escapeHtml = (v) => (window.EmsDom?.escapeHtml
        ? window.EmsDom.escapeHtml(v)
        : String(v ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;'));

    const stripHtml = (v) => (window.EmsDom?.stripHtml
        ? window.EmsDom.stripHtml(v)
        : String(v ?? '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim());

    const truncate = (text, max = 140) => (window.EmsDom?.truncate
        ? window.EmsDom.truncate(text, max)
        : (() => {
            const value = String(text ?? '');
            if (!value) return '';
            return value.length > max ? `${value.slice(0, max - 1)}…` : value;
        })());

    const hasFilterValue = (val) => {
        if (Array.isArray(val)) {
            return val.some((item) => item !== '' && item !== null && item !== undefined);
        }
        return val !== '' && val !== null && val !== undefined;
    };

    const resolveOptionLabel = (selectEl, value) => {
        if (!selectEl) return String(value);
        const option = selectEl.querySelector(`option[value="${CSS.escape(String(value))}"]`);
        return option ? option.textContent.trim() : String(value);
    };

    const formatDate = (value) => {
        if (!value) return 'Not scheduled';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Not scheduled';
        return new Intl.DateTimeFormat(undefined, {
            month: 'short', day: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        }).format(date);
    };

    const updateChips = ({ filters = {}, sort = 'updated_at', direction = 'desc' } = {}) => {
        if (!activeChipsEl) return;

        const chips = [];
        const categorySelect = document.getElementById('drawer-category-filter');
        const formatSelect = document.getElementById('drawer-format-filter');

        Object.entries(filters).forEach(([key, val]) => {
            if (!hasFilterValue(val)) return;

            const values = Array.isArray(val) ? val.filter(hasFilterValue) : [val];
            let display;
            if (key === 'category_id') {
                display = values.map((v) => resolveOptionLabel(categorySelect, v)).join(', ');
            } else if (key === 'exam_format') {
                display = values.map((v) => resolveOptionLabel(formatSelect, v)).join(', ');
            } else if (values.length > 1) {
                display = values.map((v) => chipLabels[key]?.map?.[v] || v).join(', ');
            } else {
                display = chipLabels[key]?.map?.[values[0]] || values[0];
            }

            const label = chipLabels[key]?.label || key;
            chips.push(`
                <button type="button" class="exam-filter-chip" data-chip-key="${escapeHtml(key)}">
                    <span>${escapeHtml(label)}: <strong>${escapeHtml(display)}</strong></span>
                    <svg class="exam-filter-chip__x h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `);
        });

        const sortKey = `${sort}:${direction}`;
        if (sortKey !== 'updated_at:desc') {
            const display = chipLabels.sort.map[sortKey] || sortKey;
            chips.push(`
                <button type="button" class="exam-filter-chip" data-chip-key="sort">
                    <span>Sort: <strong>${escapeHtml(display)}</strong></span>
                    <svg class="exam-filter-chip__x h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `);
        }

        activeChipsEl.innerHTML = chips.join('');
        activeChipsEl.classList.toggle('hidden', chips.length === 0);
    };

    const renderStats = (stats = {}) => {
        if (!statGridEl) return;
        statGridEl.innerHTML = [
            { title: 'Visible Exams', value: stats.total || 0 },
            { title: 'Published', value: stats.published || 0 },
            { title: 'Draft / Active', value: `${stats.draft || 0} / ${stats.active || 0}` },
            { title: 'Avg Duration', value: `${stats.avg_duration || 0} min` },
        ].map((s) => `
            <article class="exam-stat-card">
                <p class="exam-stat-title">${escapeHtml(s.title)}</p>
                <p class="exam-stat-value">${escapeHtml(s.value)}</p>
            </article>
        `).join('');
    };

    const examsTable = new AjaxTable({
        containerSelector: '#ajax-table-container',
        apiUrl: window.examsApiUrl,
        tableBodySelector: '#exams-table-body',
        paginationSelector: '#exams-pagination',
        searchSelector: '#exams-search',
        perPageSelector: '#exams-per-page',
        filterDrawerSelector: '#filter-drawer',
        filterToggleSelector: '#btn-toggle-filters',
        filterDrawerFormSelector: '#filter-drawer-form',
        loadingSelector: '#exams-loading',
        emptySelector: '#exams-empty',
        defaultSort: 'updated_at',
        defaultDirection: 'desc',
        skeletonColumns: 5,
        onFetchSuccess: (response) => {
            renderStats(response.stats || {});
            selection.clear();
            window.EmsListUi.syncSortButtons(examsTable);
        },
        onFiltersChange: (state) => updateChips(state),
        rowTemplate: (row) => {
            const showUrl = `${window.examsIndexUrl}/${row.id}`;
            const editUrl = `${showUrl}/edit`;
            const statusCls = statusClassMap[row.status] || 'exam-status-draft';
            const categoryName = row.category ? row.category.name : 'Uncategorized';
            const ownerName = row.created_by ? row.created_by.name : 'System';
            const descriptionPreview = truncate(stripHtml(row.description), 140) || 'No description';
            const tagsHtml = Array.isArray(row.tags)
                ? row.tags.map((t) => `<span class="exam-meta-chip">${escapeHtml(t)}</span>`).join('')
                : '';

            const isBin = currentTrash === 'bin';
            return `
                <tr class="exam-list-row list-row">
                    <td class="px-3 py-4 align-top">
                        <input type="checkbox" class="list-row-check" data-id="${escapeHtml(row.id)}" value="${escapeHtml(row.id)}" aria-label="Select exam ${escapeHtml(row.title)}">
                    </td>
                    <td class="px-6 py-4 align-top">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="exam-title-preview text-sm font-semibold text-slate-900 dark:text-white">${escapeHtml(row.title)}</h3>
                                <span class="exam-status-badge ${statusCls}">${escapeHtml(row.status)}</span>
                            </div>
                            <p class="exam-description-preview text-xs leading-relaxed text-slate-500 dark:text-slate-400">${escapeHtml(descriptionPreview)}</p>
                            <div class="flex flex-wrap gap-1.5">
                                <span class="exam-meta-chip">${escapeHtml(categoryName)}</span>
                                <span class="exam-meta-chip">${escapeHtml(row.exam_mode)}</span>
                                <span class="exam-meta-chip">${escapeHtml(row.duration)} min</span>
                                <span class="exam-meta-chip">Pass ${escapeHtml(row.pass_percentage)}%</span>
                                ${row.difficulty_level ? `<span class="exam-meta-chip">${escapeHtml(row.difficulty_level)}</span>` : ''}
                            </div>
                            ${tagsHtml ? `<div class="flex flex-wrap gap-1">${tagsHtml}</div>` : ''}
                            <p class="text-xs text-slate-400 dark:text-slate-500">Owner: ${escapeHtml(ownerName)}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 align-top">
                        <div class="space-y-1 text-xs text-slate-600 dark:text-slate-300">
                            <p><span class="font-semibold text-slate-700 dark:text-slate-200">Starts:</span> ${escapeHtml(formatDate(row.scheduled_start))}</p>
                            <p><span class="font-semibold text-slate-700 dark:text-slate-200">Ends:</span> ${escapeHtml(formatDate(row.scheduled_end))}</p>
                            <p class="pt-1 text-slate-400 dark:text-slate-500">Updated ${escapeHtml(formatDate(row.updated_at))}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 align-top">
                        <div class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                            <p><span class="font-semibold text-slate-700 dark:text-slate-200">${escapeHtml(row.questions_count ?? 0)}</span> questions</p>
                            <p>Max attempts: <span class="font-semibold text-slate-700 dark:text-slate-200">${escapeHtml(row.max_attempts)}</span></p>
                            <div class="flex flex-wrap gap-1.5">
                                ${row.shuffle_questions ? '<span class="exam-meta-chip">Shuffle Q</span>' : ''}
                                ${row.shuffle_options ? '<span class="exam-meta-chip">Shuffle Opt</span>' : ''}
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 align-top whitespace-nowrap text-right text-sm">
                        <div class="flex items-center justify-end gap-2">
                            ${isBin ? '' : `<a href="${showUrl}"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100 hover:text-indigo-800 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:border-indigo-500/40 dark:hover:bg-indigo-500/20 dark:hover:text-indigo-200"
                               title="View Details"
                               aria-label="View exam details">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="${editUrl}"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:border-sky-500/40 dark:hover:bg-sky-500/20 dark:hover:text-sky-200"
                               title="Edit"
                               aria-label="Edit exam">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </a>
                            <button type="button"
                                    class="js-delete-exam inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/20 dark:hover:text-rose-200"
                                    data-id="${escapeHtml(row.id)}"
                                    title="Delete"
                                    aria-label="Delete exam">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>`}
                            ${isBin ? `<button type="button" class="js-restore-exam list-action-btn list-action-btn--restore" data-id="${escapeHtml(row.id)}" title="Restore" aria-label="Restore exam">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        },
    });

    window.EmsListUi.bindSortButtons(examsTable);
    const originalFetch = examsTable.fetch.bind(examsTable);
    examsTable.fetch = function patchedFetch() {
        this.filters = { ...this.filters, trash: currentTrash };
        return originalFetch();
    };

    document.querySelector('.list-view-tabs')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-trash]');
        if (!button) return;
        currentTrash = button.dataset.trash === 'bin' ? 'bin' : 'active';
        document.querySelectorAll('.list-view-tabs [data-trash]').forEach((tab) => {
            const active = tab === button;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        selection.setMode(currentTrash);
        examsTable.page = 1;
        examsTable.fetch();
    });

    activeChipsEl?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-chip-key]');
        if (!btn) return;
        examsTable.clearFilter(btn.dataset.chipKey);
    });

    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const restoreBtn = e.target.closest('.js-restore-exam');
            if (restoreBtn) {
                const form = document.getElementById('restore-exam-form');
                form.action = `${window.examsRestoreUrl}/${restoreBtn.dataset.id}/restore`;
                form.submit();
                return;
            }
            const btn = e.target.closest('.js-delete-exam');
            if (!btn) return;
            const id = btn.dataset.id;
            Swal.fire({
                title: 'Delete Exam?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-exam-form');
                    form.action = `${window.examsIndexUrl}/${id}`;
                    form.submit();
                }
            });
        });
    }

    document.getElementById('btn-bulk-delete')?.addEventListener('click', () => {
        Swal.fire({
            title: 'Move selected exams to bin?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Move to Bin',
            confirmButtonColor: '#dc2626',
        }).then((result) => {
            if (result.isConfirmed) selection.submit('#bulk-delete-exam-form');
        });
    });

    document.getElementById('btn-bulk-restore')?.addEventListener('click', () => {
        selection.submit('#bulk-restore-exam-form');
    });

    document.getElementById('exams-bulk-status')?.addEventListener('change', (event) => {
        if (!event.target.value) return;
        const form = document.getElementById('bulk-status-exam-form');
        form.querySelector('[name="status"]').value = event.target.value;
        selection.submit(form);
    });

    if (new URLSearchParams(window.location.search).get('tab') === 'bin') {
        document.querySelector('.list-view-tabs [data-trash="bin"]')?.click();
    } else {
        selection.setMode('active');
    }
});
