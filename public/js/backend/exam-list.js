document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('exams-table-body');
    const statGridEl = document.getElementById('exam-stat-grid');
    const activeChipsEl = document.getElementById('active-filter-chips');

    const statusClassMap = {
        published: 'exam-status-published',
        draft: 'exam-status-draft',
        active: 'exam-status-active',
        inactive: 'exam-status-inactive',
        suspended: 'exam-status-suspended',
    };

    const chipLabels = {
        category_id: { label: 'Category' },
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

    const escapeHtml = (v) => String(v ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

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

        Object.entries(filters).forEach(([key, val]) => {
            if (!val) return;
            let display = chipLabels[key]?.map?.[val] || val;
            if (key === 'category_id' && categorySelect) {
                const option = categorySelect.querySelector(`option[value="${CSS.escape(String(val))}"]`);
                display = option ? option.textContent.trim() : val;
            }
            const label = chipLabels[key]?.label || key;
            chips.push(`
                <button type="button" class="exam-filter-chip" data-chip-key="${escapeHtml(key)}">
                    <span>${escapeHtml(label)}: <strong>${escapeHtml(display)}</strong></span>
                    <svg class="exam-filter-chip__x h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <svg class="exam-filter-chip__x h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        skeletonColumns: 4,
        onFetchSuccess: (response) => renderStats(response.stats || {}),
        onFiltersChange: (state) => updateChips(state),
        rowTemplate: (row) => {
            const showUrl = `${window.examsIndexUrl}/${row.id}`;
            const editUrl = `${showUrl}/edit`;
            const statusCls = statusClassMap[row.status] || 'exam-status-draft';
            const categoryName = row.category ? row.category.name : 'Uncategorized';
            const ownerName = row.created_by ? row.created_by.name : 'System';
            const tagsHtml = Array.isArray(row.tags)
                ? row.tags.map((t) => `<span class="exam-meta-chip">${escapeHtml(t)}</span>`).join('')
                : '';

            return `
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                    <td class="px-6 py-4 align-top">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="exam-title-preview text-sm font-semibold text-slate-900 dark:text-white">${escapeHtml(row.title)}</h3>
                                <span class="exam-status-badge ${statusCls}">${escapeHtml(row.status)}</span>
                            </div>
                            <p class="exam-description-preview text-xs leading-relaxed text-slate-500 dark:text-slate-400">${escapeHtml(row.description)}</p>
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
                            <a href="${showUrl}"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100 hover:text-indigo-800 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:border-indigo-500/40 dark:hover:bg-indigo-500/20 dark:hover:text-indigo-200"
                               title="View Details">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="${editUrl}"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:border-sky-500/40 dark:hover:bg-sky-500/20 dark:hover:text-sky-200"
                               title="Edit">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </a>
                            <button type="button"
                                    class="js-delete-exam inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/20 dark:hover:text-rose-200"
                                    data-id="${escapeHtml(row.id)}"
                                    title="Delete">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        },
    });

    activeChipsEl?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-chip-key]');
        if (!btn) return;
        examsTable.clearFilter(btn.dataset.chipKey);
    });

    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
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
});
