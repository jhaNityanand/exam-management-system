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
    const saveLabel = saveBtn?.querySelector('[data-save-label]');
    const passwordReq = document.getElementById('member_password_req');
    const passwordInput = document.getElementById('member_password');
    const passwordToggle = document.getElementById('member-password-toggle');

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

    const initialsFromName = (name) => {
        const parts = String(name || '')
            .trim()
            .split(/\s+/)
            .filter(Boolean);
        if (!parts.length) return '?';
        if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    };

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

    const setStatus = (status) => {
        const value = status === 'inactive' ? 'inactive' : 'active';
        const radio = form?.querySelector(`input[name="status"][value="${value}"]`);
        if (radio) radio.checked = true;
    };

    const getStatus = () => form?.querySelector('input[name="status"]:checked')?.value || 'active';

    const openModal = (member = null) => {
        if (!modal || !form) return;
        clearErrors();
        form.reset();
        form.querySelector('#member_id').value = member?.id || '';
        form.querySelector('#member_name').value = member?.name || '';
        form.querySelector('#member_email').value = member?.email || '';
        setStatus(member?.status || 'active');
        if (passwordInput) {
            passwordInput.value = '';
            passwordInput.type = 'password';
            passwordInput.required = false;
        }
        if (passwordToggle) {
            passwordToggle.textContent = 'Show';
            passwordToggle.setAttribute('aria-pressed', 'false');
        }
        if (passwordReq) passwordReq.hidden = Boolean(member?.id);
        const hint = document.getElementById('member_password_hint');
        if (hint) {
            hint.textContent = member?.id
                ? 'Leave blank to keep the current password. Password changes apply only when this is the member’s only organization.'
                : 'Required for brand-new accounts. Optional when inviting an existing user (their current password is kept).';
        }
        document.getElementById('member-modal-title').textContent = member?.id ? 'Edit member' : 'Add member';
        const subtitle = document.getElementById('member-modal-subtitle');
        if (subtitle) {
            subtitle.textContent = member?.id
                ? 'Update this member’s profile and access status.'
                : 'Create or invite an organization admin.';
        }
        if (saveLabel) saveLabel.textContent = member?.id ? 'Save changes' : 'Add member';
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
                    <td colspan="4" class="org-members-table__empty">
                        <div class="org-members-empty">
                            <div class="org-members-empty__icon" aria-hidden="true">
                                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <p class="org-members-empty__title">No members found</p>
                            <p class="org-members-empty__desc">Try adjusting filters or add a new organization admin.</p>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        const EDIT_ICON = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>`;
        const DEL_ICON = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>`;

        tableBody.innerHTML = rows.map((member) => {
            const active = member.status === 'active';
            const initials = escapeHtml(initialsFromName(member.name));
            return `
                <tr data-member-id="${member.id}">
                    <td>
                        <div class="org-members-person">
                            <span class="org-members-avatar" aria-hidden="true">${initials}</span>
                            <div class="org-members-person__meta">
                                <p class="org-members-person__name">${escapeHtml(member.name)}</p>
                                <p class="org-members-person__email">${escapeHtml(member.email)}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="org-members-role">${escapeHtml(member.role_label || member.role)}</span>
                    </td>
                    <td>
                        <span class="org-members-status ${active ? 'org-members-status--active' : 'org-members-status--inactive'}">
                            <span class="org-members-status__dot" aria-hidden="true"></span>
                            ${escapeHtml(member.status)}
                        </span>
                    </td>
                    <td>
                        <div class="org-members-actions">
                            <button type="button" class="org-members-action org-members-action--edit member-edit-btn" data-id="${member.id}" title="Edit member">
                                ${EDIT_ICON}<span>Edit</span>
                            </button>
                            <button type="button" class="org-members-action org-members-action--delete member-delete-btn" data-id="${member.id}" title="Remove member">
                                ${DEL_ICON}<span>Remove</span>
                            </button>
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
            <p class="org-members-pagination__meta">Showing <strong>${meta.from ?? 0}–${meta.to ?? 0}</strong> of <strong>${meta.total}</strong></p>
            <div class="org-members-pagination__controls">
                <button type="button" class="org-members-pagination__btn member-page-btn" data-page="${meta.current_page - 1}" ${prevDisabled ? 'disabled' : ''}>Previous</button>
                <span class="org-members-pagination__page">Page ${meta.current_page} / ${meta.last_page}</span>
                <button type="button" class="org-members-pagination__btn member-page-btn" data-page="${meta.current_page + 1}" ${nextDisabled ? 'disabled' : ''}>Next</button>
            </div>
        `;
    };

    const loadingMarkup = () => `
        <tr>
            <td colspan="4" class="org-members-table__loading">
                <div class="org-members-skeleton" aria-hidden="true">
                    <div class="org-members-skeleton__row"></div>
                    <div class="org-members-skeleton__row"></div>
                    <div class="org-members-skeleton__row"></div>
                </div>
            </td>
        </tr>`;

    const loadMembers = async (page = state.page) => {
        state.page = page;
        if (tableBody) tableBody.innerHTML = loadingMarkup();

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
                tableBody.innerHTML = `<tr><td colspan="4" class="org-members-table__empty" style="color:#ef4444">${escapeHtml(error.message)}</td></tr>`;
            }
        }
    };

    document.getElementById('member-add-btn')?.addEventListener('click', () => openModal(null));
    modal?.querySelectorAll('[data-member-modal-close]').forEach((el) => el.addEventListener('click', closeModal));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) closeModal();
    });

    passwordToggle?.addEventListener('click', () => {
        if (!passwordInput) return;
        const showing = passwordInput.type === 'text';
        passwordInput.type = showing ? 'password' : 'text';
        passwordToggle.textContent = showing ? 'Show' : 'Hide';
        passwordToggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
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

    // Instant status filter without requiring Apply.
    filtersForm?.querySelector('[name="status"]')?.addEventListener('change', () => {
        state.status = filtersForm.querySelector('[name="status"]')?.value || '';
        state.search = (filtersForm.querySelector('[name="search"]')?.value || '').trim();
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
            text: 'They will lose admin access for this organization. Their account is not deleted.',
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
            status: getStatus(),
        };
        const password = form.querySelector('#member_password')?.value || '';
        if (password) payload.password = password;

        const originalLabel = saveLabel?.textContent || 'Save member';
        if (saveBtn) saveBtn.disabled = true;
        if (saveLabel) saveLabel.textContent = 'Saving…';

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
            if (saveBtn) saveBtn.disabled = false;
            if (saveLabel) saveLabel.textContent = originalLabel;
        }
    });

    window.__emsLoadMembers = () => {
        if (!state.loadedOnce) loadMembers(1);
    };
})();
