document.addEventListener('DOMContentLoaded', () => {
    /* ── DOM refs ─────────────────────────────────────────── */
    const tableBody    = document.getElementById('exam-list-table-body');
    const loadingEl    = document.getElementById('exam-list-loading');
    const emptyEl      = document.getElementById('exam-list-empty');
    const paginationEl = document.getElementById('exam-list-pagination');
    const statGridEl   = document.getElementById('exam-stat-grid');
    const searchInput  = document.getElementById('exam-list-search');
    const sortSelect   = document.getElementById('exam-list-sort');

    // Drawer
    const drawerOverlay   = document.getElementById('filter-overlay');
    const filterDrawer    = document.getElementById('filter-drawer');
    const btnOpenFilter   = document.getElementById('btn-open-filter');
    const btnCloseFilter  = document.getElementById('btn-close-filter');
    const btnApplyFilters = document.getElementById('btn-apply-filters');
    const btnResetFilters = document.getElementById('btn-reset-filters');

    // Chips display
    const activeChipsEl   = document.getElementById('active-filter-chips');
    const filterActiveDot = document.getElementById('filter-active-dot');

    if (!tableBody || !paginationEl || !statGridEl) return;

    const baseUrl = window.examListConfig?.baseUrl || '/admin/exams';

    /* ── State ────────────────────────────────────────────── */
    const pending = { status: '', mode: '', difficulty: '', sort: 'updated_at:desc' };

    const state = {
        page:       1,
        perPage:    10, // Default 10 rows
        search:     '',
        status:     '',
        mode:       '',
        difficulty: '',
        sort:       'updated_at:desc',
    };

    let debounceTimer = null;

    const statusClassMap = {
        published: 'exam-status-published',
        draft:     'exam-status-draft',
        active:    'exam-status-active',
        inactive:  'exam-status-inactive',
        suspended: 'exam-status-suspended',
    };

    /* ── Helpers ──────────────────────────────────────────── */
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

    /* ── Drawer ───────────────────────────────────────────── */
    const openDrawer = () => {
        // Sync pending from current state
        pending.status     = state.status;
        pending.mode       = state.mode;
        pending.difficulty = state.difficulty;
        pending.sort       = state.sort;

        syncPillsToState();
        if (sortSelect) sortSelect.value = pending.sort;

        filterDrawer.classList.add('is-open');
        drawerOverlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    };

    const closeDrawer = () => {
        filterDrawer.classList.remove('is-open');
        drawerOverlay.classList.remove('is-open');
        document.body.style.overflow = '';
    };

    btnOpenFilter?.addEventListener('click', openDrawer);
    btnCloseFilter?.addEventListener('click', closeDrawer);
    drawerOverlay?.addEventListener('click', closeDrawer);

    btnApplyFilters?.addEventListener('click', () => {
        const checkedStatus     = filterDrawer.querySelector('input[name="filter-status"]:checked');
        const checkedMode       = filterDrawer.querySelector('input[name="filter-mode"]:checked');
        const checkedDifficulty = filterDrawer.querySelector('input[name="filter-difficulty"]:checked');

        state.status     = checkedStatus?.value     || '';
        state.mode       = checkedMode?.value       || '';
        state.difficulty = checkedDifficulty?.value || '';
        state.sort       = sortSelect?.value || 'updated_at:desc';
        state.page       = 1;

        closeDrawer();
        updateChips();
        render();
    });

    btnResetFilters?.addEventListener('click', () => {
        resetPills();
        if (sortSelect) sortSelect.value = 'updated_at:desc';
    });

    /* ── Pill toggle inside drawer ────────────────────────── */
    const pillGroups = filterDrawer?.querySelectorAll('[id$="-group"]');
    pillGroups?.forEach((group) => {
        group.addEventListener('click', (e) => {
            const label = e.target.closest('.exam-filter-pill');
            if (!label) return;
            const radio = label.querySelector('input[type="radio"]');
            if (!radio) return;
            radio.checked = true;
            [...group.querySelectorAll('.exam-filter-pill')].forEach((l) => l.classList.remove('is-active'));
            label.classList.add('is-active');
        });
    });

    const syncPillsToState = () => {
        const map = {
            'filter-status-group':     pending.status,
            'filter-mode-group':       pending.mode,
            'filter-difficulty-group': pending.difficulty,
        };
        Object.entries(map).forEach(([groupId, value]) => {
            const group = document.getElementById(groupId);
            if (!group) return;
            [...group.querySelectorAll('.exam-filter-pill')].forEach((label) => {
                const radio = label.querySelector('input[type="radio"]');
                const match = (radio?.value || '') === value;
                radio && (radio.checked = match);
                label.classList.toggle('is-active', match);
            });
        });
    };

    const resetPills = () => {
        pillGroups?.forEach((group) => {
            const labels = [...group.querySelectorAll('.exam-filter-pill')];
            labels.forEach((l, i) => {
                const radio = l.querySelector('input[type="radio"]');
                if (radio) radio.checked = (i === 0);
                l.classList.toggle('is-active', i === 0);
            });
        });
    };

    /* ── Active chips bar ─────────────────────────────────── */
    const chipLabels = {
        status:     { label: 'Status',     map: { published:'Published', draft:'Draft', active:'Active', inactive:'Inactive', suspended:'Suspended' } },
        mode:       { label: 'Mode',       map: { standard:'Standard', practice:'Practice', proctored:'Proctored' } },
        difficulty: { label: 'Difficulty', map: { beginner:'Beginner', intermediate:'Intermediate', advanced:'Advanced' } },
        sort:       { label: 'Sort',       map: { 'updated_at:desc':'Recently Updated', 'title:asc':'Title A→Z', 'title:desc':'Title Z→A', 'duration:desc':'Longest', 'questions_count:desc':'Most Qs', 'pass_percentage:asc':'Lowest Pass%' } },
    };

    const updateChips = () => {
        if (!activeChipsEl) return;
        const chips = [];

        ['status', 'mode', 'difficulty'].forEach((key) => {
            const val = state[key];
            if (!val) return;
            const display = chipLabels[key]?.map[val] || val;
            chips.push(`
                <button type="button" class="exam-filter-chip" data-chip-key="${key}">
                    <span>${escapeHtml(chipLabels[key].label)}: <strong>${escapeHtml(display)}</strong></span>
                    <svg class="exam-filter-chip__x h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `);
        });

        if (state.sort !== 'updated_at:desc') {
            const display = chipLabels.sort.map[state.sort] || state.sort;
            chips.push(`
                <button type="button" class="exam-filter-chip" data-chip-key="sort">
                    <span>Sort: <strong>${escapeHtml(display)}</strong></span>
                    <svg class="exam-filter-chip__x h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `);
        }

        const hasFilters = chips.length > 0;
        activeChipsEl.innerHTML = chips.join('');
        activeChipsEl.classList.toggle('hidden', !hasFilters);
        filterActiveDot?.classList.toggle('hidden', !hasFilters);
    };

    activeChipsEl?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-chip-key]');
        if (!btn) return;
        const key = btn.dataset.chipKey;
        if (key === 'sort') state.sort = 'updated_at:desc';
        else state[key] = '';
        state.page = 1;
        updateChips();
        render();
    });

    /* ── Row renderer ─────────────────────────────────────── */
    const renderRow = (row) => {
        const showUrl   = `${baseUrl}/${row.id}`;
        const editUrl   = `${showUrl}/edit`;
        const statusCls = statusClassMap[row.status] || 'exam-status-draft';
        const categoryName = row.category ? row.category.name : 'Uncategorized';
        const ownerName = row.created_by ? row.created_by.name : 'System';

        const tagsHtml = row.tags
            ? row.tags.map((t) => `<span class="exam-meta-chip">${escapeHtml(t)}</span>`).join('')
            : '';

        return `
            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-900/30">
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
                            ${row.shuffle_options   ? '<span class="exam-meta-chip">Shuffle Opt</span>' : ''}
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 align-top text-right">
                    <div class="inline-flex items-center gap-2">
                        <a href="${showUrl}"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:bg-sky-500/20"
                           title="View">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z"/>
                            </svg>
                        </a>
                        <a href="${editUrl}"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20"
                           title="Edit">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </a>
                        <button type="button"
                                class="js-delete-exam inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20"
                                data-id="${escapeHtml(row.id)}"
                                title="Delete">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    };

    /* ── Pagination ───────────────────────────────────────── */
    const renderPagination = (meta) => {
        if (meta.total <= state.perPage) {
            paginationEl.innerHTML = `<p class="text-sm text-slate-500 dark:text-slate-400">Showing <span class="font-medium text-slate-700 dark:text-slate-200">${meta.total}</span> records</p>`;
            return;
        }

        paginationEl.innerHTML = `
            <p class="hidden text-sm text-slate-500 dark:text-slate-400 sm:block">
                Showing <span class="font-medium text-slate-700 dark:text-slate-200">${meta.from}</span>
                to <span class="font-medium text-slate-700 dark:text-slate-200">${meta.to}</span>
                of <span class="font-medium text-slate-700 dark:text-slate-200">${meta.total}</span>
            </p>
            <div class="flex items-center gap-2">
                <button type="button" class="panel-button-secondary py-1.5 js-page-btn" data-page="${meta.currentPage - 1}" ${meta.currentPage === 1 ? 'disabled' : ''}>Prev</button>
                <button type="button" class="panel-button-secondary py-1.5 js-page-btn" data-page="${meta.currentPage + 1}" ${meta.currentPage === meta.lastPage ? 'disabled' : ''}>Next</button>
            </div>
        `;
    };

    /* ── Loading helpers ──────────────────────────────────── */
    const setLoading = (value) => {
        loadingEl.classList.toggle('hidden', !value);
        tableBody.classList.toggle('hidden', value);
        emptyEl.classList.add('hidden');
    };

    /* ── Render ───────────────────────────────────────────── */
    const render = () => {
        setLoading(true);

        const url = new URL('/admin/internal-api/exams-table', window.location.origin);
        if (state.search) url.searchParams.set('search', state.search);
        if (state.status) url.searchParams.set('filters[status]', state.status);
        if (state.mode) url.searchParams.set('filters[exam_mode]', state.mode);
        if (state.difficulty) url.searchParams.set('filters[difficulty_level]', state.difficulty);

        const [sortField, sortDir = 'desc'] = state.sort.split(':');
        url.searchParams.set('sort', sortField);
        url.searchParams.set('direction', sortDir);
        url.searchParams.set('page', state.page);
        url.searchParams.set('per_page', state.perPage);

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load exams.');
            return response.json();
        })
        .then(res => {
            setLoading(false);
            const pageItems = res.data || [];
            const meta = res.meta || {};
            const stats = res.stats || {};

            // Update stats grid
            statGridEl.innerHTML = [
                { title: 'Visible Exams',   value: stats.total || 0 },
                { title: 'Published',       value: stats.published || 0 },
                { title: 'Draft / Active',  value: `${stats.draft || 0} / ${stats.active || 0}` },
                { title: 'Avg Duration',    value: `${stats.avg_duration || 0} min` },
            ].map((s) => `
                <article class="exam-stat-card">
                    <p class="exam-stat-title">${escapeHtml(s.title)}</p>
                    <p class="exam-stat-value">${escapeHtml(s.value)}</p>
                </article>
            `).join('');

            if (!pageItems.length) {
                tableBody.innerHTML = '';
                emptyEl.classList.remove('hidden');
                paginationEl.innerHTML = '';
                return;
            }

            tableBody.innerHTML = pageItems.map(renderRow).join('');

            renderPagination({
                currentPage: meta.current_page || 1,
                lastPage: meta.last_page || 1,
                total: meta.total || 0,
                from: (meta.current_page - 1) * meta.per_page + 1,
                to: Math.min(meta.current_page * meta.per_page, meta.total),
            });
        })
        .catch(err => {
            console.error('AJAX Load error:', err);
            setLoading(false);
            tableBody.innerHTML = '';
            paginationEl.innerHTML = '';
            emptyEl.classList.remove('hidden');
            emptyEl.querySelector('p').textContent = 'An error occurred while loading exams from the server.';
        });
    };

    /* ── Events ───────────────────────────────────────────── */
    searchInput?.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            state.search = e.target.value || '';
            state.page   = 1;
            render();
        }, 260);
    });

    paginationEl.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-page-btn');
        if (!btn || btn.disabled) return;
        const page = Number.parseInt(btn.dataset.page || '', 10);
        if (!Number.isNaN(page) && page > 0) { state.page = page; render(); }
    });

    tableBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-delete-exam');
        if (!btn) return;
        const id = Number.parseInt(btn.dataset.id || '', 10);
        if (!id) return;

        if (window.Swal) {
            window.Swal.fire({
                title: 'Delete Exam?',
                text: "This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor:  '#64748b',
                confirmButtonText: 'Yes, delete it!',
            }).then((res) => {
                if (res.isConfirmed) {
                    const form = document.getElementById('delete-exam-form');
                    form.action = `${baseUrl}/${id}`;
                    form.submit();
                }
            });
            return;
        }
        if (window.confirm('Delete this exam record?')) {
            const form = document.getElementById('delete-exam-form');
            form.action = `${baseUrl}/${id}`;
            form.submit();
        }
    });

    /* ── Keyboard close ───────────────────────────────────── */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && filterDrawer?.classList.contains('is-open')) closeDrawer();
    });

    /* ── Boot ─────────────────────────────────────────────── */
    render();
    updateChips();
});
