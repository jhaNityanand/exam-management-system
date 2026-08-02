/**
 * Organization Settings — FAQ tab (modal CRUD, filters, pagination).
 */
(function () {
    'use strict';

    const config = window.orgFaqConfig || {};
    if (!config.indexUrl) return;

    const modal = document.getElementById('faq-modal');
    const form = document.getElementById('faq-form');
    const tableBody = document.getElementById('faq-table-body');
    const paginationEl = document.getElementById('faq-pagination');
    const filtersForm = document.getElementById('faq-filters');
    const saveBtn = document.getElementById('faq-save-btn');

    let state = {
        page: 1,
        search: '',
        status: '',
        category_id: '',
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

    const setSelectValue = (selector, value) => {
        const el = form?.querySelector(selector);
        if (!el) return;
        const next = value == null ? '' : String(value);
        if (el.tomselect) {
            el.tomselect.setValue(next, true);
            return;
        }
        el.value = next;
    };

    const openModal = (faq = null) => {
        if (!modal || !form) return;
        clearErrors();
        form.reset();
        form.querySelector('#faq_id').value = faq?.id || '';
        const question = faq?.question || '';
        const answer = faq?.answer || '';
        form.querySelector('#faq_question').value = question;
        form.querySelector('#faq_answer').value = answer;
        setSelectValue('#faq_category_id', faq?.faq_category_id || '');
        setSelectValue('#faq_status', faq?.status || 'active');
        form.querySelector('#faq_sort_order').value = faq?.sort_order ?? 0;
        form.querySelector('#faq_is_featured').checked = Boolean(faq?.is_featured);
        document.getElementById('faq-modal-title').textContent = faq?.id ? 'Edit FAQ' : 'Add FAQ';
        // Sync character counters
        const qCount = document.getElementById('faq_question_count');
        const aCount = document.getElementById('faq_answer_count');
        if (qCount) qCount.textContent = question.length + ' / 500';
        if (aCount) aCount.textContent = answer.length.toLocaleString() + ' / 10 000';
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ems-dialog-open');
        // Focus first focusable field after animation frame
        requestAnimationFrame(() => form.querySelector('#faq_question')?.focus());
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ems-dialog-open');
    };

    const updateStats = (meta, rows) => {
        const totalEl = document.getElementById('faq-stat-total');
        const activeEl = document.getElementById('faq-stat-active');
        const featuredEl = document.getElementById('faq-stat-featured');
        if (totalEl && meta) totalEl.textContent = meta.total ?? '—';
        if (activeEl && rows) activeEl.textContent = rows.filter(r => r.status === 'active').length;
        if (featuredEl && rows) featuredEl.textContent = rows.filter(r => r.is_featured).length;
    };

    const renderRows = (rows) => {
        if (!tableBody) return;
        state.rowsById = {};
        rows.forEach((faq) => { state.rowsById[faq.id] = faq; });

        if (!rows.length) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="faq-table__empty">
                        <div class="faq-empty-state">
                            <div class="faq-empty-state__icon">
                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="faq-empty-state__title">No FAQs found</p>
                            <p class="faq-empty-state__desc">Try adjusting your filters or click <strong>Add FAQ</strong> to create one.</p>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        const EDIT_ICON = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>`;
        const DEL_ICON  = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>`;
        const STAR_ICON = `<svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>`;

        tableBody.innerHTML = rows.map((faq) => {
            const active = faq.status === 'active';
            const catName = escapeHtml(faq.category_name || '');
            return `
                <tr data-faq-id="${faq.id}">
                    <td>
                        <p class="faq-table__question">${escapeHtml(faq.question)}</p>
                        <div class="faq-table__meta">
                            ${faq.is_featured ? `<span class="faq-table__badge faq-table__badge--featured">${STAR_ICON} Featured</span>` : ''}
                        </div>
                    </td>
                    <td>${catName ? `<span class="faq-table__category-pill">${catName}</span>` : '<span style="color:#94a3b8">—</span>'}</td>
                    <td>
                        <span class="faq-table__badge faq-table__badge--status ${active ? 'faq-table__badge--active' : 'faq-table__badge--inactive'}">${escapeHtml(faq.status)}</span>
                    </td>
                    <td><span class="faq-table__order">${escapeHtml(faq.sort_order)}</span></td>
                    <td>
                        <div class="faq-table__actions">
                            <button type="button" class="faq-table__action faq-table__action--edit faq-edit-btn" data-id="${faq.id}" title="Edit">${EDIT_ICON} Edit</button>
                            <button type="button" class="faq-table__action faq-table__action--delete faq-delete-btn" data-id="${faq.id}" title="Delete">${DEL_ICON} Delete</button>
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
                <button type="button" class="faq-pagination__btn faq-page-btn" data-page="${meta.current_page - 1}" ${prevDisabled ? 'disabled' : ''}>Previous</button>
                <span class="faq-pagination__page">Page ${meta.current_page} / ${meta.last_page}</span>
                <button type="button" class="faq-pagination__btn faq-page-btn" data-page="${meta.current_page + 1}" ${nextDisabled ? 'disabled' : ''}>Next</button>
            </div>
        `;
    };

    const loadFaqs = async (page = state.page) => {
        state.page = page;
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="5" class="faq-table__loading"><div class="faq-skeleton"><div class="faq-skeleton__row"><div class="faq-skeleton__bar faq-skeleton__bar--q"></div><div class="faq-skeleton__bar faq-skeleton__bar--c"></div><div class="faq-skeleton__bar faq-skeleton__bar--s"></div></div><div class="faq-skeleton__row"><div class="faq-skeleton__bar faq-skeleton__bar--q"></div><div class="faq-skeleton__bar faq-skeleton__bar--c"></div><div class="faq-skeleton__bar faq-skeleton__bar--s"></div></div><div class="faq-skeleton__row"><div class="faq-skeleton__bar faq-skeleton__bar--q"></div><div class="faq-skeleton__bar faq-skeleton__bar--c"></div><div class="faq-skeleton__bar faq-skeleton__bar--s"></div></div></div></td></tr>`;
        }

        const params = new URLSearchParams({
            page: String(state.page),
            per_page: '10',
        });
        if (state.search) params.set('search', state.search);
        if (state.status) params.set('status', state.status);
        if (state.category_id) params.set('category_id', state.category_id);

        try {
            const res = await fetch(`${config.indexUrl}?${params.toString()}`, { headers: headers() });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to load FAQs');
            const rows = data.data || [];
            renderRows(rows);
            renderPagination(data.meta || {});
            updateStats(data.meta || {}, rows);
            state.loadedOnce = true;
        } catch (error) {
            if (tableBody) {
                tableBody.innerHTML = `<tr><td colspan="5" class="faq-table__empty" style="color:#ef4444">${escapeHtml(error.message)}</td></tr>`;
            }
        }
    };

    const syncFiltersFromForm = () => {
        state.search = filtersForm?.querySelector('#faq_filter_search')?.value?.trim() || '';
        state.status = filtersForm?.querySelector('#faq_filter_status')?.value || '';
        state.category_id = filtersForm?.querySelector('#faq_filter_category')?.value || '';
    };

    let searchTimer = null;
    const scheduleSearch = () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            syncFiltersFromForm();
            loadFaqs(1);
        }, 350);
    };

    document.getElementById('faq-add-btn')?.addEventListener('click', () => openModal());
    modal?.querySelectorAll('[data-faq-modal-close]').forEach((el) => el.addEventListener('click', closeModal));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    filtersForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        clearTimeout(searchTimer);
        syncFiltersFromForm();
        loadFaqs(1);
    });

    filtersForm?.querySelector('#faq_filter_search')?.addEventListener('input', scheduleSearch);
    filtersForm?.querySelector('#faq_filter_status')?.addEventListener('change', () => {
        clearTimeout(searchTimer);
        syncFiltersFromForm();
        loadFaqs(1);
    });
    filtersForm?.querySelector('#faq_filter_category')?.addEventListener('change', () => {
        clearTimeout(searchTimer);
        syncFiltersFromForm();
        loadFaqs(1);
    });

    document.getElementById('faq-filters-reset')?.addEventListener('click', () => {
        clearTimeout(searchTimer);
        filtersForm?.reset();
        state.search = '';
        state.status = '';
        state.category_id = '';
        loadFaqs(1);
    });

    paginationEl?.addEventListener('click', (e) => {
        const btn = e.target.closest('.faq-page-btn');
        if (!btn || btn.disabled) return;
        const page = Number(btn.getAttribute('data-page') || 1);
        if (page >= 1) loadFaqs(page);
    });

    tableBody?.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.faq-edit-btn');
        if (editBtn) {
            const id = editBtn.getAttribute('data-id');
            openModal(state.rowsById[id] || null);
            return;
        }

        const deleteBtn = e.target.closest('.faq-delete-btn');
        if (!deleteBtn) return;
        const id = deleteBtn.getAttribute('data-id');
        const confirm = await window.Swal?.fire?.({
            icon: 'warning',
            title: 'Delete FAQ?',
            text: 'This FAQ will be removed from the homepage.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc2626',
        });
        if (confirm && !confirm.isConfirmed) return;

        try {
            const res = await fetch(`${config.deleteUrl}/${id}`, {
                method: 'DELETE',
                headers: headers(),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Delete failed');
            window.EmsToast?.success?.(data.message || 'FAQ deleted.');
            loadFaqs(state.page);
        } catch (error) {
            window.Swal?.fire?.({ icon: 'error', title: 'Delete failed', text: error.message });
        }
    });

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();
        const id = form.querySelector('#faq_id')?.value;
        const payload = {
            question: form.querySelector('#faq_question')?.value?.trim(),
            answer: form.querySelector('#faq_answer')?.value?.trim(),
            faq_category_id: form.querySelector('#faq_category_id')?.value || null,
            status: form.querySelector('#faq_status')?.value || 'active',
            sort_order: Number(form.querySelector('#faq_sort_order')?.value || 0),
            is_featured: Boolean(form.querySelector('#faq_is_featured')?.checked),
        };

        const url = id ? `${config.updateUrl}/${id}` : config.storeUrl;
        const method = id ? 'PUT' : 'POST';
        const original = saveBtn?.textContent;
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';
        }

        try {
            const res = await fetch(url, {
                method,
                headers: headers(),
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (res.status === 422) {
                showErrors(data.errors || {});
                throw new Error(data.message || 'Validation failed');
            }
            if (!res.ok) throw new Error(data.message || 'Save failed');
            window.EmsToast?.success?.(data.message || 'FAQ saved.');
            closeModal();
            loadFaqs(id ? state.page : 1);
        } catch (error) {
            if (!form.querySelector('[data-error-for]:not([hidden])')) {
                window.Swal?.fire?.({ icon: 'error', title: 'Save failed', text: error.message });
            }
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = original || 'Save FAQ';
            }
        }
    });

    // Expose for Alpine tab switch / hash
    window.__emsLoadFaqs = () => loadFaqs(state.loadedOnce ? state.page : 1);

    document.querySelectorAll('button[role="tab"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if ((btn.textContent || '').trim() === 'FAQs') {
                window.__emsLoadFaqs();
            }
        });
    });

    if (window.location.hash === '#faqs') {
        window.__emsLoadFaqs();
    }
})();
