/**
 * Candidates list — AjaxTable row rendering + bulk actions.
 * List is driven by exam attempts (any role), not candidate role alone.
 */
document.addEventListener('DOMContentLoaded', () => {
    const trashToggle = document.querySelector('.list-view-tabs');
    const drawerTrashInput = document.getElementById('drawer-trash-filter');
    const examDrawer = document.getElementById('drawer-exam-filter');
    let currentTrash = 'active';

    const selection = new window.EmsListUi.ListSelection({
        bodySelector: '#candidates-table-body',
        selectAllSelector: '#candidates-select-all',
        bulkBarSelector: '#candidates-bulk-bar',
        countSelector: '#candidates-selected-count',
        checkboxSelector: '.list-row-check',
        activeActionsSelector: '#candidates-bulk-actions-active',
        binActionsSelector: '#candidates-bulk-actions-bin',
    });

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);

    const formatDate = (iso) => {
        if (!iso) return '—';
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return '—';
        return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
    };

    const statusBadge = (status) => {
        const cls = status === 'active'
            ? 'cand-status-badge cand-status-badge--active'
            : 'cand-status-badge cand-status-badge--inactive';
        return `<span class="${cls}">${escapeHtml(status || '—')}</span>`;
    };

    const roleLabel = (role) => {
        if (!role) return '—';
        const map = {
            admin: 'Super Admin',
            org_admin: 'Admin',
            editor: 'Editor',
            viewer: 'Viewer',
            candidate: 'Candidate',
        };
        return map[role] || String(role).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    };

    const roleBadge = (role) => {
        const label = roleLabel(role);
        if (label === '—') {
            return '<span class="text-slate-400 dark:text-slate-500">—</span>';
        }
        return `<span class="cand-role-badge">${escapeHtml(label)}</span>`;
    };

    const selectedExamId = () => examDrawer?.value || '';

    const syncExamDrawer = (examId) => {
        const value = examId || '';
        if (!examDrawer || examDrawer.value === value) return;
        if (examDrawer.tomselect) {
            examDrawer.tomselect.setValue(value, true);
        } else {
            examDrawer.value = value;
        }
    };

    const syncTrashUi = (trash) => {
        currentTrash = trash === 'bin' ? 'bin' : 'active';
        if (drawerTrashInput) drawerTrashInput.value = currentTrash;
        trashToggle?.querySelectorAll('button[data-trash]').forEach((btn) => {
            const active = btn.dataset.trash === currentTrash;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        selection.setMode(currentTrash);
    };

    const candidatesTable = new AjaxTable({
        containerSelector: '#ajax-table-container',
        apiUrl: window.candidatesApiUrl,
        tableBodySelector: '#candidates-table-body',
        paginationSelector: '#candidates-pagination',
        searchSelector: '#candidates-search',
        perPageSelector: '#candidates-per-page',
        filterDrawerSelector: '#filter-drawer',
        filterToggleSelector: '#btn-toggle-filters',
        filterDrawerFormSelector: '#filter-drawer-form',
        loadingSelector: '#candidates-loading',
        emptySelector: '#candidates-empty',
        skeletonColumns: 11,
        defaultSort: 'id',
        defaultDirection: 'desc',
        onFetchSuccess: () => {
            selection.clear();
            window.EmsListUi.syncSortButtons(candidatesTable);
            window.EmsComingSoonModal?.bind?.(document.getElementById('candidates-table-body'));
        },
        rowTemplate: (row, index, meta) => {
            const showUrl = `${window.candidatesIndexUrl}/${row.id}`;
            const editUrl = `${showUrl}/edit`;
            const page = Number(meta?.current_page || candidatesTable.page || 1);
            const perPage = Number(meta?.per_page || candidatesTable.per_page || 10);
            const total = Number(meta?.total ?? 0);
            const offset = (page - 1) * perPage + index;
            const serial = candidatesTable.sort === 'id' && candidatesTable.direction === 'desc'
                ? Math.max(total - offset, 0)
                : offset + 1;

            const initialsFn = window.EmsUserAvatar?.initials || ((n) => String(n || 'C').slice(0, 2).toUpperCase());
            const colorFn = window.EmsUserAvatar?.color || (() => '#4f46e5');
            const initials = escapeHtml(row.initials || initialsFn(row.name, 'C'));
            const avatarColor = escapeHtml(row.avatar_color || colorFn(row.id || row.name || 'user'));
            const avatar = row.avatar_url
                ? `<img src="${escapeHtml(row.avatar_url)}" alt="" class="cand-list-avatar__img">`
                : `<span class="cand-list-avatar__initials">${initials}</span>`;

            const username = row.username
                ? `<div class="text-xs text-slate-400 dark:text-slate-500">@${escapeHtml(row.username)}</div>`
                : '';

            const verified = row.email_verified_at
                ? '<span class="cand-verified-badge">Verified</span>'
                : '<span class="cand-verified-badge cand-verified-badge--no">Unverified</span>';

            const attempts = Number(row.attempts_count || 0);

            const isBin = currentTrash === 'bin';
            const actions = isBin
                ? `<button type="button" class="js-restore-candidate list-action-btn list-action-btn--restore" data-id="${row.id}" title="Restore" aria-label="Restore candidate">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                   </button>`
                : `
                    <a href="${showUrl}" class="q-action-btn q-action-btn--view" title="View Details" aria-label="View Details">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </a>
                    <a href="${editUrl}" class="q-action-btn q-action-btn--edit" title="Edit" aria-label="Edit">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                    </a>
                    <button type="button" class="q-action-btn q-action-btn--invoice" data-coming-soon-modal="invoice-coming-soon-modal" title="Invoice" aria-label="Invoice">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                    <button type="button" class="js-delete-candidate q-action-btn q-action-btn--delete" data-id="${row.id}" title="Delete" aria-label="Delete">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                `;

            return `
                <tr class="list-row group">
                    <td class="px-3 py-2.5 align-middle w-10">
                        <input type="checkbox" class="list-row-check" data-id="${row.id}" value="${row.id}" aria-label="Select candidate">
                    </td>
                    <td class="px-4 py-2.5 align-middle text-slate-500 dark:text-slate-400">${serial}</td>
                    <td class="px-4 py-2.5 align-middle">
                        <div class="cand-list-user">
                            <div class="cand-list-avatar" style="${row.avatar_url ? '' : `background:${avatarColor}`}">${avatar}</div>
                            <div class="min-w-0">
                                <a href="${showUrl}" class="font-semibold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 truncate block">
                                    ${escapeHtml(row.name)}
                                </a>
                                ${username}
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-2.5 align-middle">
                        <span class="text-sm text-slate-700 dark:text-slate-200 break-all">${escapeHtml(row.email)}</span>
                    </td>
                    <td class="px-4 py-2.5 align-middle text-slate-600 dark:text-slate-300">${escapeHtml(row.phone || '—')}</td>
                    <td class="px-4 py-2.5 align-middle">${roleBadge(row.role)}</td>
                    <td class="px-4 py-2.5 align-middle">
                        <span class="cand-attempts-pill" title="Exam attempts">${attempts}</span>
                    </td>
                    <td class="px-4 py-2.5 align-middle">${statusBadge(row.status)}</td>
                    <td class="px-4 py-2.5 align-middle">${verified}</td>
                    <td class="px-4 py-2.5 align-middle text-slate-500 dark:text-slate-400">${formatDate(row.created_at)}</td>
                    <td class="px-4 py-2.5 align-middle text-right">
                        <div class="inline-flex items-center justify-end gap-1.5">${actions}</div>
                    </td>
                </tr>
            `;
        },
    });

    const originalFetch = candidatesTable.fetch.bind(candidatesTable);
    candidatesTable.fetch = function patchedFetch() {
        const examId = selectedExamId();
        this.filters = {
            ...this.filters,
            trash: currentTrash,
            exam_id: examId || undefined,
        };
        if (!examId) {
            delete this.filters.exam_id;
        }
        return originalFetch();
    };

    window.EmsListUi.bindSortButtons(candidatesTable);

    const applyExamFilter = (examId) => {
        syncExamDrawer(examId);
        candidatesTable.page = 1;
        candidatesTable.fetch();
    };

    const applyTrashFilter = (trash) => {
        syncTrashUi(trash);
        candidatesTable.filters = { ...candidatesTable.filters, trash: currentTrash };
        candidatesTable.page = 1;
        candidatesTable.fetch();
    };

    trashToggle?.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-trash]');
        if (!btn) return;
        applyTrashFilter(btn.dataset.trash);
    });

    const filterForm = document.getElementById('filter-drawer-form');
    filterForm?.addEventListener('reset', () => {
        window.setTimeout(() => {
            if (drawerTrashInput) drawerTrashInput.value = currentTrash;
            syncExamDrawer('');
        }, 0);
    });
    filterForm?.addEventListener('submit', () => {
        if (drawerTrashInput) drawerTrashInput.value = currentTrash;
    });

    // After drawer applies filters, AjaxTable updates filters from form — keep drawer select synced
    const originalOnFiltersChange = candidatesTable.onFiltersChange;
    candidatesTable.onFiltersChange = function patchedFiltersChange(filters) {
        if (filters && Object.prototype.hasOwnProperty.call(filters, 'exam_id')) {
            syncExamDrawer(filters.exam_id || '');
        }
        if (typeof originalOnFiltersChange === 'function') {
            return originalOnFiltersChange.call(this, filters);
        }
        return undefined;
    };

    document.getElementById('btn-refresh-candidates')?.addEventListener('click', function onRefresh() {
        const btn = this;
        if (candidatesTable.loading) return;
        btn.classList.add('is-spinning');
        btn.disabled = true;
        candidatesTable.page = 1;
        candidatesTable.fetch();
        const watch = setInterval(() => {
            if (!candidatesTable.loading) {
                clearInterval(watch);
                btn.classList.remove('is-spinning');
                btn.disabled = false;
            }
        }, 120);
    });

    const postForm = (action, method) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action;
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = window.candidatesCsrf;
        form.appendChild(token);
        if (method && method !== 'POST') {
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = method;
            form.appendChild(methodInput);
        }
        document.body.appendChild(form);
        form.submit();
    };

    document.getElementById('candidates-table-body')?.addEventListener('click', (e) => {
        const del = e.target.closest('.js-delete-candidate');
        const restore = e.target.closest('.js-restore-candidate');

        if (del) {
            const id = del.getAttribute('data-id');
            const run = () => postForm(`${window.candidatesIndexUrl}/${id}`, 'DELETE');
            if (window.Swal) {
                Swal.fire({
                    title: 'Delete candidate?',
                    text: 'This will move the candidate to the bin.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    confirmButtonText: 'Yes, delete',
                }).then((r) => { if (r.isConfirmed) run(); });
            } else if (confirm('Move this candidate to the bin?')) {
                run();
            }
        }

        if (restore) {
            postForm(`${window.candidatesIndexUrl}/${restore.getAttribute('data-id')}/restore`, 'PATCH');
        }
    });

    document.getElementById('btn-bulk-delete')?.addEventListener('click', () => {
        if (selection.ids.size === 0) return;
        const run = () => selection.submit('#candidates-bulk-destroy-form');
        if (window.Swal) {
            Swal.fire({
                title: 'Move to bin?',
                text: `${selection.ids.size} candidate(s) will be moved to the bin.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Yes, continue',
            }).then((r) => { if (r.isConfirmed) run(); });
        } else if (confirm('Move selected candidates to the bin?')) {
            run();
        }
    });

    document.getElementById('btn-bulk-restore')?.addEventListener('click', () => {
        selection.submit('#candidates-bulk-restore-form');
    });

    document.getElementById('candidates-bulk-status')?.addEventListener('change', (e) => {
        const status = e.target.value;
        if (!status || selection.ids.size === 0) {
            e.target.value = '';
            return;
        }
        document.getElementById('bulk-status-value').value = status;
        selection.submit('#candidates-bulk-status-form');
    });

    syncTrashUi('active');
});
