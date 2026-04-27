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
        perPage:    6,
        search:     '',
        status:     '',
        mode:       '',
        difficulty: '',
        sort:       'updated_at:desc',
    };

    /* ── Dummy data ───────────────────────────────────────── */
    const dummyExams = [
        { id:1,  title:'Full Stack Engineering Certification',    description:'Covers frontend architecture, backend APIs, performance profiling, and deployment readiness for production teams.',        status:'published', exam_mode:'proctored', duration:120, pass_percentage:70, max_attempts:2, question_count:72, category_name:'Computer Science', difficulty_level:'advanced',     scheduled_start:'2026-05-03T09:00:00', scheduled_end:'2026-05-03T14:00:00', shuffle_questions:true,  shuffle_options:true,  updated_at:'2026-04-24T10:00:00', owner_name:'Ava Martin',     tags:'backend,certification' },
        { id:2,  title:'Physics Foundation Benchmark',            description:'Assesses classical mechanics, optics, and basic electromagnetic reasoning for entry-level candidates.',                    status:'draft',     exam_mode:'standard', duration:75,  pass_percentage:55, max_attempts:3, question_count:45, category_name:'Physics',          difficulty_level:'intermediate', scheduled_start:null,                  scheduled_end:null,                  shuffle_questions:false, shuffle_options:true,  updated_at:'2026-04-23T13:32:00', owner_name:'Noah Patel',     tags:'physics,science'   },
        { id:3,  title:'Biology Practice Sprint',                 description:'Short-format assessment for quick revision on cells, genetics, and ecology fundamentals.',                                status:'active',    exam_mode:'practice', duration:40,  pass_percentage:50, max_attempts:5, question_count:28, category_name:'Biology',          difficulty_level:'beginner',     scheduled_start:'2026-04-28T08:30:00', scheduled_end:'2026-06-30T23:59:00', shuffle_questions:true,  shuffle_options:false, updated_at:'2026-04-22T09:18:00', owner_name:'Mia Johnson',    tags:'biology,practice'  },
        { id:4,  title:'Aptitude Screening — Batch A',            description:'Timed aptitude exam for shortlisting candidates before technical interview rounds.',                                      status:'published', exam_mode:'standard', duration:60,  pass_percentage:60, max_attempts:1, question_count:36, category_name:'General Aptitude', difficulty_level:'intermediate', scheduled_start:'2026-05-01T10:00:00', scheduled_end:'2026-05-15T18:00:00', shuffle_questions:true,  shuffle_options:true,  updated_at:'2026-04-21T16:05:00', owner_name:'Liam Walker',    tags:'aptitude,screening'},
        { id:5,  title:'Data Structures Advanced Evaluation',     description:'Evaluates algorithmic thinking, time complexity optimization, and data structure selection for senior track candidates.',  status:'inactive',  exam_mode:'proctored',duration:90,  pass_percentage:68, max_attempts:2, question_count:52, category_name:'Data Structures',  difficulty_level:'advanced',     scheduled_start:null,                  scheduled_end:null,                  shuffle_questions:false, shuffle_options:false, updated_at:'2026-04-19T11:20:00', owner_name:'Ethan Ramirez',  tags:'dsa,advanced'      },
        { id:6,  title:'Mathematics Placement Test',              description:'Used for academic stream placement with emphasis on algebra, geometry, and statistics.',                                   status:'published', exam_mode:'standard', duration:100, pass_percentage:65, max_attempts:2, question_count:60, category_name:'Mathematics',      difficulty_level:'intermediate', scheduled_start:'2026-05-10T07:30:00', scheduled_end:'2026-05-10T18:30:00', shuffle_questions:true,  shuffle_options:false, updated_at:'2026-04-18T08:15:00', owner_name:'Sophia Allen',   tags:'math,placement'    },
        { id:7,  title:'API Design and Security Audit',           description:'Scenario-based exam focusing on REST standards, auth flows, and secure API lifecycle management.',                        status:'draft',     exam_mode:'proctored',duration:85,  pass_percentage:72, max_attempts:1, question_count:40, category_name:'Web Development',  difficulty_level:'advanced',     scheduled_start:null,                  scheduled_end:null,                  shuffle_questions:true,  shuffle_options:true,  updated_at:'2026-04-17T14:48:00', owner_name:'Olivia Cruz',    tags:'api,security'      },
        { id:8,  title:'Logical Reasoning Warm-Up',               description:'Low-stakes practice exam for fresh candidates preparing for reasoning and puzzle rounds.',                                 status:'active',    exam_mode:'practice', duration:30,  pass_percentage:45, max_attempts:9, question_count:20, category_name:'Reasoning',        difficulty_level:'beginner',     scheduled_start:'2026-04-27T09:00:00', scheduled_end:'2026-12-31T23:00:00', shuffle_questions:true,  shuffle_options:true,  updated_at:'2026-04-16T12:11:00', owner_name:'James Chen',     tags:'reasoning,warmup'  },
        { id:9,  title:'Network Administration Gate',             description:'Intermediate level objective test on routing, switching, and incident response controls.',                                 status:'suspended', exam_mode:'standard', duration:70,  pass_percentage:58, max_attempts:2, question_count:34, category_name:'Networking',       difficulty_level:'intermediate', scheduled_start:'2026-05-05T10:30:00', scheduled_end:'2026-05-05T16:30:00', shuffle_questions:false, shuffle_options:false, updated_at:'2026-04-15T15:24:00', owner_name:'Isabella Reed',  tags:'networking,admin'  },
        { id:10, title:'Commerce Fundamentals Checkpoint',        description:'Assesses accounting and economics basics for business-oriented entry roles.',                                             status:'published', exam_mode:'standard', duration:55,  pass_percentage:52, max_attempts:3, question_count:33, category_name:'Commerce',         difficulty_level:'beginner',     scheduled_start:null,                  scheduled_end:null,                  shuffle_questions:false, shuffle_options:true,  updated_at:'2026-04-14T09:36:00', owner_name:'Lucas Scott',    tags:'commerce,basics'   },
        { id:11, title:'Frontend UI Architecture Drill',          description:'Hands-on and conceptual exam around component architecture, accessibility, and state management.',                        status:'active',    exam_mode:'practice', duration:65,  pass_percentage:60, max_attempts:4, question_count:38, category_name:'Programming',      difficulty_level:'intermediate', scheduled_start:'2026-04-29T08:00:00', scheduled_end:'2026-06-01T22:00:00', shuffle_questions:true,  shuffle_options:true,  updated_at:'2026-04-13T10:12:00', owner_name:'Emma Brooks',    tags:'frontend,ui'       },
        { id:12, title:'Chemistry Competitive Mock',              description:'Mock exam with sectional timing and negative marking for competitive prep batches.',                                       status:'draft',     exam_mode:'standard', duration:110, pass_percentage:64, max_attempts:2, question_count:66, category_name:'Chemistry',        difficulty_level:'advanced',     scheduled_start:null,                  scheduled_end:null,                  shuffle_questions:true,  shuffle_options:true,  updated_at:'2026-04-12T13:57:00', owner_name:'William Diaz',   tags:'chemistry,mock'    },
    ];

    let records = [...dummyExams];
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

    const normalize = (v) => String(v ?? '').toLowerCase();

    const formatDate = (value) => {
        if (!value) return 'Not scheduled';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Not scheduled';
        return new Intl.DateTimeFormat(undefined, {
            month: 'short', day: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        }).format(date);
    };

    const sortRecords = (data, key, dir) => {
        const m = dir === 'asc' ? 1 : -1;
        return [...data].sort((a, b) => {
            const fa = a[key], fb = b[key];
            if (typeof fa === 'number' && typeof fb === 'number') return (fa - fb) * m;
            return String(fa ?? '').localeCompare(String(fb ?? '')) * m;
        });
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
        // Read pending pill state
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
        sort:       { label: 'Sort',       map: { 'updated_at:desc':'Recently Updated', 'title:asc':'Title A→Z', 'title:desc':'Title Z→A', 'duration:desc':'Longest', 'question_count:desc':'Most Qs', 'pass_percentage:asc':'Lowest Pass%' } },
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

    /* ── Stats ────────────────────────────────────────────── */
    const updateStats = (data) => {
        const published   = data.filter((d) => d.status === 'published').length;
        const drafts      = data.filter((d) => d.status === 'draft').length;
        const active      = data.filter((d) => d.status === 'active').length;
        const avgDuration = data.length
            ? Math.round(data.reduce((s, d) => s + d.duration, 0) / data.length)
            : 0;

        statGridEl.innerHTML = [
            { title: 'Visible Exams',   value: data.length },
            { title: 'Published',       value: published },
            { title: 'Draft / Active',  value: `${drafts} / ${active}` },
            { title: 'Avg Duration',    value: `${avgDuration} min` },
        ].map((s) => `
            <article class="exam-stat-card">
                <p class="exam-stat-title">${escapeHtml(s.title)}</p>
                <p class="exam-stat-value">${escapeHtml(s.value)}</p>
            </article>
        `).join('');
    };

    /* ── Row renderer ─────────────────────────────────────── */
    const renderRow = (row) => {
        const showUrl   = `${baseUrl}/${row.id}`;
        const editUrl   = `${showUrl}/edit`;
        const statusCls = statusClassMap[row.status] || 'exam-status-draft';

        const tagsHtml = row.tags
            ? row.tags.split(',').map((t) => `<span class="exam-meta-chip">${escapeHtml(t.trim())}</span>`).join('')
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
                            <span class="exam-meta-chip">${escapeHtml(row.category_name || 'Uncategorized')}</span>
                            <span class="exam-meta-chip">${escapeHtml(row.exam_mode)}</span>
                            <span class="exam-meta-chip">${escapeHtml(row.duration)} min</span>
                            <span class="exam-meta-chip">Pass ${escapeHtml(row.pass_percentage)}%</span>
                            ${row.difficulty_level ? `<span class="exam-meta-chip">${escapeHtml(row.difficulty_level)}</span>` : ''}
                        </div>
                        ${tagsHtml ? `<div class="flex flex-wrap gap-1">${tagsHtml}</div>` : ''}
                        <p class="text-xs text-slate-400 dark:text-slate-500">Owner: ${escapeHtml(row.owner_name || 'System')}</p>
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
                        <p><span class="font-semibold text-slate-700 dark:text-slate-200">${escapeHtml(row.question_count)}</span> questions</p>
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
                                title="Delete (demo)">
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

    /* ── Filter + sort ────────────────────────────────────── */
    const applyFiltersAndSorting = () => {
        const q = normalize(state.search);
        const filtered = records.filter((r) => {
            const matchSearch     = !q || normalize(r.title).includes(q) || normalize(r.category_name).includes(q) || normalize(r.owner_name).includes(q) || normalize(r.exam_mode).includes(q);
            const matchStatus     = !state.status     || r.status            === state.status;
            const matchMode       = !state.mode       || r.exam_mode         === state.mode;
            const matchDifficulty = !state.difficulty || r.difficulty_level  === state.difficulty;
            return matchSearch && matchStatus && matchMode && matchDifficulty;
        });

        const [sortField, sortDir = 'desc'] = state.sort.split(':');
        return sortRecords(filtered, sortField, sortDir);
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

        window.setTimeout(() => {
            const filtered  = applyFiltersAndSorting();
            const total     = filtered.length;
            const lastPage  = Math.max(1, Math.ceil(total / state.perPage));
            if (state.page > lastPage) state.page = lastPage;

            const fromIndex = (state.page - 1) * state.perPage;
            const pageItems = filtered.slice(fromIndex, fromIndex + state.perPage);

            updateStats(filtered);
            setLoading(false);

            if (!pageItems.length) {
                tableBody.innerHTML = '';
                emptyEl.classList.remove('hidden');
                paginationEl.innerHTML = '';
                return;
            }

            tableBody.innerHTML = pageItems.map(renderRow).join('');
            renderPagination({
                currentPage: state.page,
                lastPage,
                total,
                from: fromIndex + 1,
                to:   Math.min(fromIndex + state.perPage, total),
            });
        }, 240);
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

        const proceed = () => { records = records.filter((r) => r.id !== id); render(); };

        if (window.Swal) {
            window.Swal.fire({
                title: 'Remove this exam?',
                text:  'Demo mode: this only updates the local list.',
                icon:  'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor:  '#64748b',
                confirmButtonText: 'Delete from list',
            }).then((res) => {
                if (res.isConfirmed) {
                    proceed();
                    window.Swal.fire({ icon: 'success', title: 'Removed', timer: 1300, showConfirmButton: false });
                }
            });
            return;
        }
        if (window.confirm('Remove this exam from the demo list?')) proceed();
    });

    /* ── Keyboard close ───────────────────────────────────── */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && filterDrawer?.classList.contains('is-open')) closeDrawer();
    });

    /* ── Boot ─────────────────────────────────────────────── */
    render();
    updateChips();
});
