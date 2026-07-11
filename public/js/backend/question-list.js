document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('questions-table-body');

    const getTypeBadge = (type) => {
        const types = {
            mcq: { label: 'Multiple Choice', class: 'question-type-mcq' },
            true_false: { label: 'True / False', class: 'question-type-true-false' },
            short_answer: { label: 'Short Answer', class: 'question-type-short-answer' },
            long_answer: { label: 'Long Answer', class: 'question-type-long-answer' },
            fill_blank: { label: 'Fill in the Blanks', class: 'question-type-fill-blank' },
        };
        const activeType = types[type] || { label: type, class: '' };
        return `<span class="question-type-badge ${activeType.class}">${activeType.label}</span>`;
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

    const updateSortIndicators = (table) => {
        document.querySelectorAll('.q-sort-btn').forEach((btn) => {
            const key = btn.dataset.sortKey;
            btn.classList.remove('is-active', 'is-asc', 'is-desc');
            if (key === table.sort) {
                btn.classList.add('is-active', table.direction === 'asc' ? 'is-asc' : 'is-desc');
            }
        });
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
        skeletonColumns: 7,
        defaultSort: 'id',
        defaultDirection: 'desc',
        onFetchSuccess: () => updateSortIndicators(questionsTable),
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

            return `
                <tr class="question-list-row group transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/80">
                    <td class="px-3 py-2.5 align-middle whitespace-nowrap text-sm font-medium text-slate-500 dark:text-slate-400">
                        ${serial}
                    </td>
                    <td class="px-4 py-2.5 align-middle">
                        <div class="text-sm font-medium text-slate-900 dark:text-slate-100 q-body-preview" title="${plain.replace(/"/g, '&quot;')}">${bodyPreview}</div>
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
                            <a href="${showUrl}"
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
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        },
    });

    document.querySelectorAll('.q-sort-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.sortKey;
            if (!key) return;

            if (questionsTable.sort === key) {
                questionsTable.direction = questionsTable.direction === 'asc' ? 'desc' : 'asc';
            } else {
                questionsTable.sort = key;
                questionsTable.direction = 'asc';
            }

            questionsTable.page = 1;
            updateSortIndicators(questionsTable);
            questionsTable.fetch();
        });
    });

    updateSortIndicators(questionsTable);

    // ── Marks filter: clickable buttons (single vs multiple) ───────────────
    const marksTypeSelect = document.getElementById('drawer-marks-type-filter');
    const marksButtonsWrap = document.getElementById('marks-options-buttons');
    const marksValuesHost = document.getElementById('drawer-marks-filter-values');
    const marksSelectAllBtn = document.getElementById('marks-select-all-btn');
    const marksHint = document.getElementById('marks-filter-hint');
    const marksButtons = () => Array.from(marksButtonsWrap?.querySelectorAll('.marks-option-btn') || []);

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

        const mode = marksTypeSelect?.value || '';
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

        const mode = marksTypeSelect.value;
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
                marksHint.textContent = 'Choose a Marks Type above to enable marks filtering.';
            }
        }
    };

    marksTypeSelect?.addEventListener('change', syncMarksFilterMode);

    marksButtonsWrap?.addEventListener('click', (e) => {
        const btn = e.target.closest('.marks-option-btn');
        if (!btn || btn.disabled) return;

        const mode = marksTypeSelect?.value;
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
        if (marksTypeSelect?.value !== 'multiple') return;
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
        updateSortIndicators(questionsTable);
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
});
