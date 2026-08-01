/**
 * Organization Settings — Members tab (modal CRUD, filters, pagination).
 * New members are always assigned the org_admin role by the API.
 */
(function () {
    'use strict';

    const config = window.orgMemberConfig || {};
    if (!config.indexUrl) return;

    const modal = document.getElementById('member-modal');
    const form = document.getElementById('member-form');
    const tableBody = document.getElementById('member-table-body');
    const paginationEl = document.getElementById('member-pagination');
    const filtersForm = document.getElementById('member-filters');
    const saveBtn = document.getElementById('member-save-btn');
    const passwordReq = document.getElementById('member_password_req');
    const passwordInput = document.getElementById('member_password');

    let state = {
        page: 1,
        search: '',
        status: '',
        loadedOnce: false,
        rowsById: {},
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const headers = () => ({
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': config.csrf,
        'X-Requested-With': 'XMLHttpRequest',
    });

    const clearErrors = () => {
        form?.querySelectorAll('[data-error-for]').forEach((el) => {
            el.hidden = true;
            el.textContent = '';
        });
    };

    const showErrors = (errors = {}) => {
        clearErrors();
        Object.entries(errors).forEach(([key, messages]) => {
            const el = form?.querySelector(`[data-error-for="${key}"]`);
            if (!el) return;
            el.textContent = Array.isArray(messages) ? messages[0] : String(messages);
            el.hidden = false;
        });
    };

    const openModal = (member = null) => {
        if (!modal || !form) return;
        clearErrors();
        form.reset();
        form.querySelector('#member_id').value = member?.id || '';
        form.querySelector('#member_name').value = member?.name || '';
        form.querySelector('#member_email').value = member?.email || '';
        form.querySelector('#member_status').value = member?.status || 'active';
        if (passwordInput) {
            passwordInput.value = '';
            // Server decides when a password is required (new email vs existing account).
            passwordInput.required = false;
        }
        if (passwordReq) passwordReq.hidden = Boolean(member?.id);
        const hint = document.getElementById('member_password_hint');
        if (hint) {
            hint.textContent = member?.id
                ? 'Leave blank to keep the current password. Password changes apply only when this is the member’s only organization.'
                : 'Required for brand-new accounts. Optional when inviting an existing user (their current password is kept).';
        }
        document.getElementById('member-modal-title').textContent = member?.id ? 'Edit member' : 'Add member';
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ems-dialog-open');
        requestAnimationFrame(() => form.querySelector('#member_name')?.focus());
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ems-dialog-open');
    };

    const updateStats = (meta, rows) => {
        const totalEl = document.getElementById('member-stat-total');
        const activeEl = document.getElementById('member-stat-active');
        const inactiveEl = document.getElementById('member-stat-inactive');
        if (totalEl && meta) totalEl.textContent = meta.total ?? '—';
        if (activeEl && rows) activeEl.textContent = rows.filter((r) => r.status === 'active').length;
        if (inactiveEl && rows) inactiveEl.textContent = rows.filter((r) => r.status !== 'active').length;
    };

    const renderRows = (rows) => {
        if (!tableBody) return;
        state.rowsById = {};
        rows.forEach((row) => { state.rowsById[row.id] = row; });

        if (!rows.length) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="faq-table__empty">
                        <div class="faq-empty-state">
                            <div class="faq-empty-state__icon">
                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <p class="faq-empty-state__title">No members found</p>
                            <p class="faq-empty-state__desc">Try adjusting your filters or click <strong>Add member</strong>.</p>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        const EDIT_ICON = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>`;
        const DEL_ICON = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>`;

        tableBody.innerHTML = rows.map((member) => {
            const active = member.status === 'active';
            return `
                <tr data-member-id="${member.id}">
                    <td>
                        <p class="faq-table__question">${escapeHtml(member.name)}</p>
                        <div class="faq-table__meta">
                            <span class="faq-table__badge">${escapeHtml(member.email)}</span>
                        </div>
                    </td>
                    <td><span class="faq-table__category-pill">${escapeHtml(member.role_label || member.role)}</span></td>
                    <td>
                        <span class="faq-table__badge faq-table__badge--status ${active ? 'faq-table__badge--active' : 'faq-table__badge--inactive'}">${escapeHtml(member.status)}</span>
                    </td>
                    <td>
                        <div class="faq-table__actions">
                            <button type="button" class="faq-table__action faq-table__action--edit member-edit-btn" data-id="${member.id}" title="Edit">${EDIT_ICON} Edit</button>
                            <button type="button" class="faq-table__action faq-table__action--delete member-delete-btn" data-id="${member.id}" title="Remove">${DEL_ICON} Delete</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    };

    const renderPagination = (meta) => {
        if (!paginationEl || !meta) return;
        if (!meta.total) {
            paginationEl.innerHTML = '';
            return;
        }

        const prevDisabled = meta.current_page <= 1;
        const nextDisabled = meta.current_page >= meta.last_page;

        paginationEl.innerHTML = `
            <p class="faq-pagination__meta">Showing <strong>${meta.from ?? 0}–${meta.to ?? 0}</strong> of <strong>${meta.total}</strong></p>
            <div class="faq-pagination__controls">
                <button type="button" class="faq-pagination__btn member-page-btn" data-page="${meta.current_page - 1}" ${prevDisabled ? 'disabled' : ''}>Previous</button>
                <span class="faq-pagination__page">Page ${meta.current_page} / ${meta.last_page}</span>
                <button type="button" class="faq-pagination__btn member-page-btn" data-page="${meta.current_page + 1}" ${nextDisabled ? 'disabled' : ''}>Next</button>
            </div>
        `;
    };

    const loadMembers = async (page = state.page) => {
        state.page = page;
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="4" class="faq-table__loading"><div class="faq-skeleton"><div class="faq-skeleton__row"><div class="faq-skeleton__bar faq-skeleton__bar--q"></div><div class="faq-skeleton__bar faq-skeleton__bar--c"></div><div class="faq-skeleton__bar faq-skeleton__bar--s"></div></div></div></td></tr>`;
        }

        const params = new URLSearchParams({
            page: String(state.page),
            per_page: '10',
        });
        if (state.search) params.set('search', state.search);
        if (state.status) params.set('status', state.status);

        try {
            const res = await fetch(`${config.indexUrl}?${params.toString()}`, { headers: headers() });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to load members');
            const rows = data.data || [];
            renderRows(rows);
            renderPagination(data.meta || {});
            updateStats(data.meta || {}, rows);
            state.loadedOnce = true;
        } catch (error) {
            if (tableBody) {
                tableBody.innerHTML = `<tr><td colspan="4" class="faq-table__empty" style="color:#ef4444">${escapeHtml(error.message)}</td></tr>`;
            }
        }
    };

    document.getElementById('member-add-btn')?.addEventListener('click', () => openModal(null));
    modal?.querySelectorAll('[data-member-modal-close]').forEach((el) => el.addEventListener('click', closeModal));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) closeModal();
    });

    filtersForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        state.search = (filtersForm.querySelector('[name="search"]')?.value || '').trim();
        state.status = filtersForm.querySelector('[name="status"]')?.value || '';
        loadMembers(1);
    });

    document.getElementById('member-filters-reset')?.addEventListener('click', () => {
        filtersForm?.reset();
        state.search = '';
        state.status = '';
        loadMembers(1);
    });

    paginationEl?.addEventListener('click', (e) => {
        const btn = e.target.closest('.member-page-btn');
        if (!btn || btn.disabled) return;
        const page = Number(btn.getAttribute('data-page'));
        if (page > 0) loadMembers(page);
    });

    tableBody?.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.member-edit-btn');
        if (editBtn) {
            const row = state.rowsById[editBtn.getAttribute('data-id')];
            if (row) openModal(row);
            return;
        }

        const deleteBtn = e.target.closest('.member-delete-btn');
        if (!deleteBtn) return;

        const id = deleteBtn.getAttribute('data-id');
        window.Swal?.fire?.({
            icon: 'warning',
            title: 'Remove this member?',
            text: 'They will lose admin access for this organization.',
            showCancelButton: true,
            confirmButtonText: 'Remove',
            confirmButtonColor: '#dc2626',
        }).then(async (result) => {
            if (!result?.isConfirmed) return;
            try {
                const res = await fetch(`${config.deleteUrl}/${id}`, {
                    method: 'DELETE',
                    headers: headers(),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Could not remove member.');
                window.EmsToast?.success?.(data.message || 'Member removed.');
                loadMembers(state.page);
            } catch (error) {
                window.Swal?.fire?.({ icon: 'error', title: 'Remove failed', text: error.message });
            }
        });
    });

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();

        const id = form.querySelector('#member_id')?.value;
        const payload = {
            name: form.querySelector('#member_name')?.value?.trim(),
            email: form.querySelector('#member_email')?.value?.trim(),
            status: form.querySelector('#member_status')?.value || 'active',
        };
        const password = form.querySelector('#member_password')?.value || '';
        if (password) payload.password = password;

        const original = saveBtn?.textContent;
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';
        }

        try {
            const url = id ? `${config.updateUrl}/${id}` : config.storeUrl;
            const method = id ? 'PUT' : 'POST';
            const res = await fetch(url, {
                method,
                headers: headers(),
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                if (data.errors) {
                    showErrors(data.errors);
                    // Field errors are already inline — avoid a redundant alert.
                    return;
                }
                throw new Error(data.message || 'Could not save member.');
            }
            closeModal();
            window.EmsToast?.success?.(data.message || 'Member saved.');
            loadMembers(id ? state.page : 1);
        } catch (error) {
            window.Swal?.fire?.({ icon: 'error', title: 'Save failed', text: error.message });
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = original || 'Save member';
            }
        }
    });

    window.__emsLoadMembers = () => {
        if (!state.loadedOnce) loadMembers(1);
    };
})();
