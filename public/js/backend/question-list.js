document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('questions-table-body');

    // Badge Render Helpers
    const getTypeBadge = (type) => {
        const types = {
            'mcq': { label: 'Multiple Choice', class: 'question-type-mcq' },
            'true_false': { label: 'True / False', class: 'question-type-true-false' },
            'short_answer': { label: 'Short Answer', class: 'question-type-short-answer' }
        };
        const activeType = types[type] || { label: type, class: '' };
        return `<span class="question-type-badge ${activeType.class}">${activeType.label}</span>`;
    };

    const getDiffBadge = (diff) => {
        const diffs = {
            'easy': { class: 'question-diff-easy' },
            'medium': { class: 'question-diff-medium' },
            'hard': { class: 'question-diff-hard' }
        };
        const activeDiff = diffs[diff] || { class: '' };
        return `<span class="question-diff-badge ${activeDiff.class}">${diff}</span>`;
    };

    const stripHtml = (html) => {
        let tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    };

    // Instantiate Reusable AjaxTable Component
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
        rowTemplate: (q) => {
            const bodyPreview = stripHtml(q.body).substring(0, 100) + (stripHtml(q.body).length > 100 ? '...' : '');
            const catName = q.category ? q.category.name : '<span class="text-slate-400 italic">None</span>';
            const showUrl = `${window.questionsIndexUrl}/${q.id}`;
            const editUrl = `${window.questionsIndexUrl}/${q.id}/edit`;
            
            // Format marks: if it is multiple type, list all selected marks or show range
            let marksDisplay = `${q.marks} pts`;
            if (q.marks_type === 'multiple' && Array.isArray(q.marks_list) && q.marks_list.length > 0) {
                marksDisplay = q.marks_list.join(', ') + ' pts';
            }
            
            return `
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1">
                            <div class="text-sm font-medium text-slate-900 dark:text-white q-body-preview">${bodyPreview}</div>
                            <div class="flex items-center gap-2 mt-1">
                                ${getDiffBadge(q.difficulty)}
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${getTypeBadge(q.type)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        ${catName}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-700 dark:text-slate-300">
                        ${marksDisplay}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <div class="flex items-center justify-end gap-2">
                            <!-- Show Action Button -->
                            <a href="${showUrl}" 
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100 hover:text-indigo-800 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:border-indigo-500/40 dark:hover:bg-indigo-500/20 dark:hover:text-indigo-200" 
                               title="View Details">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <!-- Edit Action Button -->
                            <a href="${editUrl}" 
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:border-sky-500/40 dark:hover:bg-sky-500/20 dark:hover:text-sky-200" 
                               title="Edit">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </a>
                            <!-- Delete Action Button -->
                            <button type="button" 
                                    class="js-delete-question inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/20 dark:hover:text-rose-200" 
                                    data-id="${q.id}" 
                                    title="Delete">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
    });

    // Handle delete action using event delegation on tableBody
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const btn = e.target.closest('.js-delete-question');
            if (btn) {
                const id = btn.dataset.id;
                Swal.fire({
                    title: 'Delete Question?',
                    text: "This action cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('delete-question-form');
                        form.action = `${window.questionsIndexUrl}/${id}`;
                        form.submit();
                    }
                });
            }
        });
    }
});
