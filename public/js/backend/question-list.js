document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('questions-table-body');
    const questionTypeMeta = window.questionTypeMeta || {};
    const sourceFilter = document.getElementById('questions-source-filter');
    let currentTrash = 'active';
    const selection = new window.EmsListUi.ListSelection({
        bodySelector: '#questions-table-body',
        selectAllSelector: '#questions-select-all',
        bulkBarSelector: '#questions-bulk-bar',
        countSelector: '#questions-selected-count',
        checkboxSelector: '.list-row-check',
        activeActionsSelector: '#questions-bulk-actions-active',
        binActionsSelector: '#questions-bulk-actions-bin',
    });

    const getTypeBadge = (type) => {
        const activeType = questionTypeMeta[type] || { label: type, class: '' };
        return `<span class="question-type-badge ${activeType.class || ''}">${activeType.label || type}</span>`;
    };

    const getDiffBadge = (diff) => {
        const diffs = {
            easy: { label: 'Easy', class: 'question-diff-easy' },
            medium: { label: 'Medium', class: 'question-diff-medium' },
            hard: { label: 'Hard', class: 'question-diff-hard' },
            very_hard: { label: 'Very Hard', class: 'question-diff-very-hard' },
        };
        const activeDiff = diffs[diff] || { label: diff || '—', class: '' };
        return `<span class="question-diff-badge ${activeDiff.class}">${activeDiff.label}</span>`;
    };

    const stripHtml = (html) => {
        const tmp = document.createElement('div');
        tmp.innerHTML = html || '';
        return tmp.textContent || tmp.innerText || '';
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);

    const getSourceBadge = (question) => {
        if (!question.import_question_id) {
            return '<span class="question-source-badge question-source-badge--manual">Manual</span>';
        }

        return `<button type="button"
                    class="question-source-badge question-source-badge--imported js-import-details"
                    data-import-id="${question.import_question_id}"
                    title="View source import details">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0-12 4 4m-4-4L8 7M5 13v6a2 2 0 002 2h10a2 2 0 002-2v-6"/>
                    </svg>
                    Imported
                </button>`;
    };

    const questionsTable = new AjaxTable({
        containerSelector: '#ajax-table-container',
        apiUrl: window.questionsApiUrl,
        tableBodySelector: '#questions-table-body',
        paginationSelector: '#questions-pagination',
        searchSelector: '#questions-search',
        perPageSelector: '#questions-per-page',
        filterDrawerSelector: '#filter-drawer',
        filterToggleSelector: '#btn-toggle-filters',
        filterDrawerFormSelector: '#filter-drawer-form',
        loadingSelector: '#questions-loading',
        emptySelector: '#questions-empty',
        totalCountSelector: '#questions-total-count',
        skeletonColumns: 9,
        defaultSort: 'id',
        defaultDirection: 'desc',
        onFetchSuccess: () => {
            selection.clear();
            window.EmsListUi.syncSortButtons(questionsTable);
        },
        rowTemplate: (q, index, meta) => {
            const plain = stripHtml(q.body).trim();
            const bodyPreview = plain.length > 110 ? `${plain.substring(0, 110)}…` : plain;
            const catName = q.category
                ? `<span class="text-slate-700 dark:text-slate-200">${q.category.name}</span>`
                : '<span class="text-slate-400 italic dark:text-slate-500">None</span>';
            const showUrl = `${window.questionsIndexUrl}/${q.id}`;
            const editUrl = `${window.questionsIndexUrl}/${q.id}/edit`;
            const page = Number(meta?.current_page || questionsTable.page || 1);
            const perPage = Number(meta?.per_page || questionsTable.per_page || 10);
            const total = Number(meta?.total ?? 0);
            const offset = (page - 1) * perPage + index;
            // Desc by id (S.No): count down from total (1020, 1019…); asc: 1, 2, 3…
            const serial = questionsTable.sort === 'id' && questionsTable.direction === 'desc'
                ? Math.max(total - offset, 0)
                : offset + 1;

            let marksDisplay = `${q.marks} pts`;
            if (q.marks_type === 'multiple' && Array.isArray(q.marks_list) && q.marks_list.length > 0) {
                marksDisplay = `${q.marks_list.join(', ')} pts`;
            }

            const isBin = currentTrash === 'bin';
            return `
                <tr class="question-list-row list-row">
                    <td class="px-3 py-2.5 align-middle">
                        <input type="checkbox" class="list-row-check" data-id="${q.id}" value="${q.id}" aria-label="Select question ${q.id}">
                    </td>
                    <td class="px-3 py-2.5 align-middle whitespace-nowrap text-sm font-medium text-slate-500 dark:text-slate-400">
                        ${serial}
                    </td>
                    <td class="px-4 py-2.5 align-middle">
                        <div class="text-sm font-medium text-slate-900 dark:text-slate-100 q-body-preview" title="${plain.replace(/"/g, '&quot;')}">${bodyPreview}</div>
                    </td>
                    <td class="px-4 py-2.5 align-middle whitespace-nowrap">
                        ${getSourceBadge(q)}
                    </td>
                    <td class="px-4 py-2.5 align-middle whitespace-nowrap">
                        ${getTypeBadge(q.type)}
                    </td>
                    <td class="px-4 py-2.5 align-middle whitespace-nowrap text-sm">
                        ${catName}
                    </td>
                    <td class="px-4 py-2.5 align-middle whitespace-nowrap">
                        ${getDiffBadge(q.difficulty)}
                    </td>
                    <td class="px-4 py-2.5 align-middle whitespace-nowrap text-sm font-medium text-slate-700 dark:text-slate-200">
                        ${marksDisplay}
                    </td>
                    <td class="px-4 py-2.5 align-middle whitespace-nowrap text-right text-sm">
                        <div class="flex items-center justify-end gap-1.5">
                            ${isBin ? '' : `<a href="${showUrl}"
                               class="q-action-btn q-action-btn--view"
                               title="View Details"
                               aria-label="View Details">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="${editUrl}"
                               class="q-action-btn q-action-btn--edit"
                               title="Edit"
                               aria-label="Edit">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </a>
                            <button type="button"
                                    class="js-delete-question q-action-btn q-action-btn--delete"
                                    data-id="${q.id}"
                                    title="Delete"
                                    aria-label="Delete">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>`}
                            ${isBin ? `<button type="button" class="js-restore-question list-action-btn list-action-btn--restore" data-id="${q.id}" title="Restore" aria-label="Restore question">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        },
    });

    window.EmsListUi.bindSortButtons(questionsTable);

    const originalFetch = questionsTable.fetch.bind(questionsTable);
    questionsTable.fetch = function patchedFetch() {
        this.filters = {
            ...this.filters,
            trash: currentTrash,
            import_source: sourceFilter?.value || 'all',
        };
        return originalFetch();
    };

    sourceFilter?.addEventListener('change', () => {
        questionsTable.page = 1;
        questionsTable.fetch();
    });

    document.addEventListener('questions:imported', () => {
        currentTrash = 'active';
        questionsTable.page = 1;
        questionsTable.fetch();
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
        questionsTable.page = 1;
        questionsTable.fetch();
    });

    // ── Marks filter: clickable buttons (single vs multiple) ───────────────
    const marksTypeSelect = document.getElementById('drawer-marks-type-filter');
    const marksButtonsWrap = document.getElementById('marks-options-buttons');
    const marksValuesHost = document.getElementById('drawer-marks-filter-values');
    const marksSelectAllBtn = document.getElementById('marks-select-all-btn');
    const marksHint = document.getElementById('marks-filter-hint');
    const marksButtons = () => Array.from(marksButtonsWrap?.querySelectorAll('.marks-option-btn') || []);
    const getMarksMode = () => {
        if (!marksTypeSelect) return '';
        const value = marksTypeSelect.tomselect
            ? marksTypeSelect.tomselect.getValue()
            : Array.from(marksTypeSelect.selectedOptions || []).map((option) => option.value);
        const selectedTypes = Array.isArray(value) ? value : [value].filter(Boolean);
        return selectedTypes.length === 1 ? selectedTypes[0] : '';
    };

    const getSelectedMarks = () => marksButtons()
        .filter((btn) => btn.classList.contains('is-selected'))
        .map((btn) => btn.dataset.marks);

    const setMarkSelected = (btn, selected) => {
        btn.classList.toggle('is-selected', selected);
        btn.setAttribute('aria-pressed', selected ? 'true' : 'false');
    };

    const clearMarksSelection = () => {
        marksButtons().forEach((btn) => setMarkSelected(btn, false));
        syncMarksHiddenInputs();
    };

    const syncMarksHiddenInputs = () => {
        if (!marksValuesHost) return;
        marksValuesHost.innerHTML = '';

        const mode = getMarksMode();
        const selected = getSelectedMarks();

        if (mode === 'multiple') {
            selected.forEach((value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'filters[marks][]';
                input.value = value;
                marksValuesHost.appendChild(input);
            });
            return;
        }

        if (mode === 'single' && selected[0]) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'filters[marks]';
            input.value = selected[0];
            marksValuesHost.appendChild(input);
        }
    };

    const syncMarksFilterMode = () => {
        if (!marksTypeSelect || !marksButtonsWrap) return;

        const mode = getMarksMode();
        marksButtonsWrap.dataset.mode = mode;
        clearMarksSelection();

        const disabled = mode !== 'single' && mode !== 'multiple';
        marksButtons().forEach((btn) => {
            btn.disabled = disabled;
        });

        if (mode === 'single') {
            marksSelectAllBtn?.classList.add('hidden');
            if (marksHint) {
                marksHint.textContent = 'Click one marks value to filter.';
            }
        } else if (mode === 'multiple') {
            marksSelectAllBtn?.classList.remove('hidden');
            if (marksHint) {
                marksHint.textContent = 'Click one or more marks. Use Select All to choose every option.';
            }
        } else {
            marksSelectAllBtn?.classList.add('hidden');
            if (marksHint) {
                marksHint.textContent = 'Select exactly one Marks Type to enable marks filtering.';
            }
        }
    };

    marksTypeSelect?.addEventListener('change', syncMarksFilterMode);

    marksButtonsWrap?.addEventListener('click', (e) => {
        const btn = e.target.closest('.marks-option-btn');
        if (!btn || btn.disabled) return;

        const mode = getMarksMode();
        const willSelect = !btn.classList.contains('is-selected');

        if (mode === 'single') {
            marksButtons().forEach((other) => setMarkSelected(other, other === btn && willSelect));
        } else if (mode === 'multiple') {
            setMarkSelected(btn, willSelect);
        }

        syncMarksHiddenInputs();
        if (marksSelectAllBtn && mode === 'multiple') {
            const allSelected = marksButtons().every((b) => b.classList.contains('is-selected'));
            marksSelectAllBtn.textContent = allSelected && marksButtons().length
                ? 'Clear All'
                : 'Select All';
        }
    });

    marksSelectAllBtn?.addEventListener('click', () => {
        if (getMarksMode() !== 'multiple') return;
        const allSelected = marksButtons().every((btn) => btn.classList.contains('is-selected'));
        marksButtons().forEach((btn) => setMarkSelected(btn, !allSelected));
        marksSelectAllBtn.textContent = allSelected ? 'Select All' : 'Clear All';
        syncMarksHiddenInputs();
    });

    // Keep marks mode in sync after filter reset
    const filterForm = document.getElementById('filter-drawer-form');
    filterForm?.querySelector('[type="reset"]')?.addEventListener('click', () => {
        setTimeout(() => {
            syncMarksFilterMode();
            if (marksSelectAllBtn) marksSelectAllBtn.textContent = 'Select All';
        }, 0);
    });

    syncMarksFilterMode();

    // ── Refresh list ───────────────────────────────────────────────────────
    const refreshBtn = document.getElementById('btn-refresh-questions');
    refreshBtn?.addEventListener('click', () => {
        if (questionsTable.loading) return;
        refreshBtn.classList.add('is-refreshing');
        refreshBtn.disabled = true;

        // Reload like first page load: page 1 + default S.No sort (id desc)
        questionsTable.page = 1;
        questionsTable.sort = questionsTable.defaultSort;
        questionsTable.direction = questionsTable.defaultDirection;
        window.EmsListUi.syncSortButtons(questionsTable);
        questionsTable.fetch();

        const watch = setInterval(() => {
            if (!questionsTable.loading) {
                clearInterval(watch);
                refreshBtn.classList.remove('is-refreshing');
                refreshBtn.disabled = false;
            }
        }, 120);
    });

    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const importBadge = e.target.closest('.js-import-details');
            if (importBadge) {
                const importId = importBadge.dataset.importId;
                Swal.fire({
                    title: 'Loading import details…',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                fetch(`${window.questionImportsUrl}/${importId}`, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                })
                    .then(async (response) => {
                        const payload = await response.json();
                        if (!response.ok) throw new Error(payload.message || 'Unable to load import details.');
                        return payload.import;
                    })
                    .then((item) => {
                        const size = Number(item.file_size || 0);
                        const sizeLabel = size >= 1048576
                            ? `${(size / 1048576).toFixed(2)} MB`
                            : `${Math.max(0, size / 1024).toFixed(1)} KB`;
                        const importedAt = item.imported_at
                            ? new Date(item.imported_at).toLocaleString()
                            : '—';
                        const errors = Array.isArray(item.errors) ? item.errors : [];
                        const errorHtml = errors.length
                            ? `<div class="question-import-detail__errors">
                                <strong>Errors</strong>
                                <ul>${errors.slice(0, 10).map((error) => (
                                    `<li>Row ${escapeHtml(error.row)}: ${escapeHtml((error.errors || []).join(', '))}</li>`
                                )).join('')}</ul>
                                ${errors.length > 10 ? `<small>Showing 10 of ${errors.length} errors.</small>` : ''}
                            </div>`
                            : '';

                        Swal.fire({
                            title: 'Question Import Details',
                            width: 680,
                            showConfirmButton: false,
                            showCloseButton: true,
                            html: `<div class="question-import-detail">
                                <div class="question-import-detail__file">
                                    <span>${escapeHtml(item.file_type)}</span>
                                    <div><strong>${escapeHtml(item.original_file_name)}</strong><small>${sizeLabel}</small></div>
                                </div>
                                <div class="question-import-detail__grid">
                                    <div><span>Status</span><strong>${escapeHtml(String(item.status).replaceAll('_', ' '))}</strong></div>
                                    <div><span>Imported by</span><strong>${escapeHtml(item.created_by || 'Unknown')}</strong></div>
                                    <div><span>Total rows</span><strong>${item.total_rows}</strong></div>
                                    <div><span>Successful</span><strong class="is-success">${item.successful_rows}</strong></div>
                                    <div><span>Failed</span><strong class="is-error">${item.failed_rows}</strong></div>
                                    <div><span>Imported at</span><strong>${escapeHtml(importedAt)}</strong></div>
                                </div>
                                ${errorHtml}
                                <a class="question-import-detail__download" href="${escapeHtml(item.download_url)}">Download original file</a>
                            </div>`,
                        });
                    })
                    .catch((error) => {
                        Swal.fire('Import details unavailable', error.message, 'error');
                    });
                return;
            }

            const restoreBtn = e.target.closest('.js-restore-question');
            if (restoreBtn) {
                const form = document.getElementById('restore-question-form');
                form.action = `${window.questionsRestoreUrl}/${restoreBtn.dataset.id}/restore`;
                form.submit();
                return;
            }
            const btn = e.target.closest('.js-delete-question');
            if (!btn) return;

            const id = btn.dataset.id;
            Swal.fire({
                title: 'Delete Question?',
                text: 'This will soft-delete the question. You can restore it later from the database if needed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it',
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-question-form');
                    form.action = `${window.questionsIndexUrl}/${id}`;
                    form.submit();
                }
            });
        });
    }

    document.getElementById('btn-bulk-delete')?.addEventListener('click', () => {
        Swal.fire({
            title: 'Move selected questions to bin?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Move to Bin',
            confirmButtonColor: '#dc2626',
        }).then((result) => {
            if (result.isConfirmed) selection.submit('#bulk-delete-question-form');
        });
    });

    document.getElementById('btn-bulk-restore')?.addEventListener('click', () => {
        selection.submit('#bulk-restore-question-form');
    });

    document.getElementById('questions-bulk-status')?.addEventListener('change', (event) => {
        if (!event.target.value) return;
        const form = document.getElementById('bulk-status-question-form');
        form.querySelector('[name="status"]').value = event.target.value;
        selection.submit(form);
    });

    if (new URLSearchParams(window.location.search).get('tab') === 'bin') {
        document.querySelector('.list-view-tabs [data-trash="bin"]')?.click();
    } else {
        selection.setMode('active');
    }
});
