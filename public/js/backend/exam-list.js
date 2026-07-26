document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('exams-table-body');
    const statGridEl = document.getElementById('exam-stat-grid');
    const activeChipsEl = document.getElementById('active-filter-chips');
    const emptyTitleEl = document.getElementById('exams-empty-title');
    const emptyCopyEl = document.getElementById('exams-empty-copy');
    const emptyClearBtn = document.getElementById('exams-empty-clear');
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
        visibility: {
            label: 'Visibility',
            map: { public: 'Public', private: 'Private', invite_only: 'Invite Only' },
        },
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
        duration_min: { label: 'Duration ≥' },
        duration_max: { label: 'Duration ≤' },
        questions_min: { label: 'Questions ≥' },
        questions_max: { label: 'Questions ≤' },
        parts_min: { label: 'Parts ≥' },
        parts_max: { label: 'Parts ≤' },
        marks_min: { label: 'Marks ≥' },
        marks_max: { label: 'Marks ≤' },
        created_from: { label: 'Created from' },
        created_to: { label: 'Created to' },
        sort: {
            label: 'Sort',
            map: {
                'updated_at:desc': 'Recently Updated',
                'title:asc': 'Title A→Z',
                'title:desc': 'Title Z→A',
                'parts_count:desc': 'Most Parts',
                'questions_count:desc': 'Most Questions',
                'total_marks:desc': 'Highest Marks',
                'duration:desc': 'Longest',
                'pass_percentage:asc': 'Lowest Pass%',
                'scheduled_start:asc': 'Earliest Schedule',
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
        if (!value) return '—';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '—';
        return new Intl.DateTimeFormat(undefined, {
            month: 'short', day: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        }).format(date);
    };

    const formatDateShort = (value) => {
        if (!value) return 'Any time';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Any time';
        return new Intl.DateTimeFormat(undefined, {
            month: 'short', day: '2-digit', year: 'numeric',
        }).format(date);
    };

    const updateChips = ({ filters = {}, sort = 'updated_at', direction = 'desc' } = {}) => {
        if (!activeChipsEl) return;

        const chips = [];
        const categorySelect = document.getElementById('drawer-category-filter');
        const formatSelect = document.getElementById('drawer-format-filter');

        Object.entries(filters).forEach(([key, val]) => {
            if (key === 'trash' || !hasFilterValue(val)) return;

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

    const updateEmptyState = (response = {}) => {
        const total = Number(response?.meta?.total ?? 0);
        const filters = { ...(examsTable?.filters || {}) };
        delete filters.trash;
        const hasFilters = Object.values(filters).some(hasFilterValue)
            || Boolean(examsTable?.search)
            || (examsTable?.sort && `${examsTable.sort}:${examsTable.direction}` !== 'updated_at:desc');

        if (emptyTitleEl) {
            emptyTitleEl.textContent = hasFilters ? 'No matching exams' : 'No exams yet';
        }
        if (emptyCopyEl) {
            emptyCopyEl.textContent = hasFilters
                ? 'Try clearing filters or refining your search.'
                : 'Create your first exam to get started.';
        }
        emptyClearBtn?.classList.toggle('hidden', !hasFilters || total > 0);
    };

    const renderStats = (stats = {}) => {
        if (!statGridEl) return;
        statGridEl.removeAttribute('aria-busy');
        statGridEl.innerHTML = [
            { title: 'Visible Exams', value: stats.total || 0 },
            { title: 'Published / Active', value: `${stats.published || 0} / ${stats.active || 0}` },
            { title: 'Avg Parts', value: stats.avg_parts ?? 0 },
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
        skeletonColumns: 9,
        onFetchSuccess: (response) => {
            renderStats(response.stats || {});
            updateEmptyState(response);
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
            const descriptionPreview = truncate(stripHtml(row.description), 90);
            const partsCount = Number(row.parts_count ?? 0);
            const partNames = Array.isArray(row.part_names) ? row.part_names.filter(Boolean) : [];
            const partsTitle = partNames.length ? escapeHtml(partNames.join(', ')) : '';
            const questionsCount = Number(row.questions_count ?? row.total_questions ?? 0);
            const totalMarks = Number(row.total_marks ?? 0);
            const duration = Number(row.duration ?? 0);
            const passPct = row.pass_percentage != null ? `${row.pass_percentage}%` : '—';
            const isBin = currentTrash === 'bin';
            const scheduleLabel = row.scheduled_start || row.scheduled_end
                ? `${formatDateShort(row.scheduled_start)} → ${formatDateShort(row.scheduled_end)}`
                : 'Any time';

            return `
                <tr class="exam-list-row list-row">
                    <td class="px-3 py-2.5 align-middle">
                        <input type="checkbox" class="list-row-check" data-id="${escapeHtml(row.id)}" value="${escapeHtml(row.id)}" aria-label="Select exam ${escapeHtml(row.title)}">
                    </td>
                    <td class="px-4 py-2.5 align-middle">
                        <div class="exam-list-exam-cell">
                            <a href="${showUrl}" class="exam-title-preview exam-list-title-link">${escapeHtml(row.title)}</a>
                            ${descriptionPreview ? `<p class="exam-description-preview text-xs text-slate-500 dark:text-slate-400">${escapeHtml(descriptionPreview)}</p>` : ''}
                            <div class="exam-list-meta-row">
                                <span class="exam-meta-chip">${escapeHtml(categoryName)}</span>
                                <span class="exam-meta-chip">${escapeHtml(row.exam_mode || '—')}</span>
                                ${row.difficulty_level ? `<span class="exam-meta-chip">${escapeHtml(row.difficulty_level)}</span>` : ''}
                                <span class="exam-meta-chip">Pass ${escapeHtml(passPct)}</span>
                            </div>
                            <p class="exam-list-owner">Owner: ${escapeHtml(ownerName)}</p>
                        </div>
                    </td>
                    <td class="px-4 py-2.5 align-middle">
                        <span class="exam-status-badge ${statusCls}">${escapeHtml(row.status || 'draft')}</span>
                    </td>
                    <td class="px-4 py-2.5 align-middle">
                        <span class="exam-parts-badge" ${partsTitle ? `title="${partsTitle}"` : ''}>${escapeHtml(partsCount)} part${partsCount === 1 ? '' : 's'}</span>
                    </td>
                    <td class="px-4 py-2.5 align-middle text-sm font-semibold text-slate-800 dark:text-slate-100">${escapeHtml(questionsCount)}</td>
                    <td class="px-4 py-2.5 align-middle text-sm font-semibold text-slate-800 dark:text-slate-100">${escapeHtml(totalMarks)}</td>
                    <td class="px-4 py-2.5 align-middle text-sm text-slate-700 dark:text-slate-200">${escapeHtml(duration)} min</td>
                    <td class="px-4 py-2.5 align-middle">
                        <div class="exam-list-schedule">
                            <span>${escapeHtml(scheduleLabel)}</span>
                            <span class="exam-list-schedule__updated">Updated ${escapeHtml(formatDateShort(row.updated_at))}</span>
                        </div>
                    </td>
                    <td class="px-4 py-2.5 align-middle whitespace-nowrap text-right text-sm">
                        <div class="flex items-center justify-end gap-1.5">
                            ${isBin ? '' : `<a href="${showUrl}" class="list-action-btn" title="View Details" aria-label="View exam details">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <button type="button" class="list-action-btn list-action-btn--attempts" data-exam-attempts="${escapeHtml(row.id)}" data-exam-title="${escapeHtml(row.title || '')}" title="Exam Attempts" aria-label="View exam attempts">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87M12 11a4 4 0 100-8 4 4 0 000 8zm6 3a3 3 0 100-6 3 3 0 000 6z"></path>
                                </svg>
                            </button>
                            <a href="${editUrl}" class="list-action-btn" title="Edit" aria-label="Edit exam">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </a>
                            <button type="button" class="js-delete-exam list-action-btn list-action-btn--danger" data-id="${escapeHtml(row.id)}" title="Move to Bin" aria-label="Move exam to bin">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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

    const refreshBtn = document.getElementById('btn-refresh-exams');
    refreshBtn?.addEventListener('click', () => {
        if (examsTable.loading) return;
        refreshBtn.classList.add('is-refreshing');
        refreshBtn.disabled = true;

        examsTable.page = 1;
        examsTable.sort = examsTable.defaultSort;
        examsTable.direction = examsTable.defaultDirection;
        window.EmsListUi.syncSortButtons(examsTable);
        examsTable.fetch();

        const watch = setInterval(() => {
            if (!examsTable.loading) {
                clearInterval(watch);
                refreshBtn.classList.remove('is-refreshing');
                refreshBtn.disabled = false;
            }
        }, 120);
    });

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

    emptyClearBtn?.addEventListener('click', () => {
        Object.keys(examsTable.filters || {}).forEach((key) => {
            if (key !== 'trash') examsTable.clearFilter(key);
        });
        if (examsTable.elements?.search) examsTable.elements.search.value = '';
        examsTable.search = '';
        examsTable.page = 1;
        examsTable.sort = examsTable.defaultSort;
        examsTable.direction = examsTable.defaultDirection;
        examsTable.fetch();
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
                title: 'Move exam to Bin?',
                text: 'You can restore it later from the Bin tab.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Move to Bin',
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
