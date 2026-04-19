document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('questions-table-body');
    const loadingEl = document.getElementById('questions-loading');
    const emptyEl = document.getElementById('questions-empty');
    const paginationEl = document.getElementById('questions-pagination');
    const searchInput = document.getElementById('questions-search');
    const categorySelect = document.getElementById('questions-category-filter');
    const totalCountEl = document.getElementById('questions-total-count');

    let currentPage = 1;
    let currentSearch = '';
    let currentCategory = '';

    // Dummy Data
    const dummyQuestions = [
        { id: 1, type: 'mcq', difficulty: 'hard', marks: 10, body: '<p>What is the principle of Quantum Entanglement?</p>', category: { id: 4, name: 'Quantum Physics' } },
        { id: 2, type: 'true_false', difficulty: 'easy', marks: 5, body: '<p>The mitochondria is the powerhouse of the cell.</p>', category: { id: 6, name: 'Biology' } },
        { id: 3, type: 'short_answer', difficulty: 'medium', marks: 15, body: '<p>Explain the concept of an interface in Object Oriented Programming.</p>', category: { id: 15, name: 'Programming' } },
        { id: 4, type: 'mcq', difficulty: 'easy', marks: 5, body: '<p>What is the value of Pi to two decimal places?</p>', category: { id: 11, name: 'Geometry' } },
        { id: 5, type: 'mcq', difficulty: 'medium', marks: 10, body: '<p>Which of the following sorting algorithms has the best worst-case performance?</p>', category: { id: 17, name: 'Data Structures' } }
    ];

    const fetchQuestions = async () => {
        setLoading(true);
        setTimeout(() => {
            let filtered = [...dummyQuestions];
            
            if (currentSearch) {
                const q = currentSearch.toLowerCase();
                filtered = filtered.filter(item => stripHtml(item.body).toLowerCase().includes(q) || item.category.name.toLowerCase().includes(q));
            }
            
            if (currentCategory) {
                // In dummy, let's just do exact or simulate parent child if we wanted, but exact is fine for demo
                filtered = filtered.filter(item => item.category.id == currentCategory);
            }

            const perPage = 10;
            const total = filtered.length;
            const lastPage = Math.ceil(total / perPage) || 1;
            
            // Pagination slice
            const from = (currentPage - 1) * perPage;
            const to = from + perPage;
            const paginatedData = filtered.slice(from, to);

            const meta = {
                current_page: currentPage,
                last_page: lastPage,
                per_page: perPage,
                total: total
            };

            renderTable(paginatedData);
            renderPagination(meta);
            totalCountEl.textContent = total;
            
            setLoading(false);
        }, 500); // Simulate network delay
    };

    const setLoading = (isLoading) => {
        if (isLoading) {
            loadingEl.classList.remove('hidden');
            tableBody.classList.add('hidden');
            emptyEl.classList.add('hidden');
        } else {
            loadingEl.classList.add('hidden');
            tableBody.classList.remove('hidden');
        }
    };

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

    const renderTable = (questions) => {
        if (questions.length === 0) {
            tableBody.innerHTML = '';
            tableBody.classList.add('hidden');
            emptyEl.classList.remove('hidden');
            return;
        }

        emptyEl.classList.add('hidden');
        
        tableBody.innerHTML = questions.map(q => {
            const bodyPreview = stripHtml(q.body).substring(0, 100) + '...';
            const catName = q.category ? q.category.name : '<span class="text-slate-400 italic">None</span>';
            const editUrl = `${window.questionsIndexUrl}/${q.id}/edit`;
            // Add a visual indicator for boolean allows_multiple if mcq?
            
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
                        ${q.marks} pts
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <div class="flex items-center justify-end gap-2">
                            <a href="${editUrl}" 
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:border-sky-500/40 dark:hover:bg-sky-500/20 dark:hover:text-sky-200" 
                               title="Edit">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </a>
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
        }).join('');
    };

    const renderPagination = (meta) => {
        if (meta.total === 0) {
            paginationEl.innerHTML = '';
            return;
        }

        const from = (meta.current_page - 1) * meta.per_page + 1;
        const to = Math.min(meta.current_page * meta.per_page, meta.total);

        paginationEl.innerHTML = `
            <div class="hidden sm:block">
                <p class="text-sm text-slate-700 dark:text-slate-400">
                    Showing <span class="font-medium">${from}</span> to <span class="font-medium">${to}</span> of <span class="font-medium">${meta.total}</span> results
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="js-page-btn panel-button-secondary py-1" data-page="${meta.current_page - 1}" ${meta.current_page === 1 ? 'disabled' : ''}>Previous</button>
                <button type="button" class="js-page-btn panel-button-secondary py-1" data-page="${meta.current_page + 1}" ${meta.current_page === meta.last_page ? 'disabled' : ''}>Next</button>
            </div>
        `;
    };

    // Event Listeners
    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            currentSearch = e.target.value;
            currentPage = 1;
            fetchQuestions();
        }, 300);
    });

    categorySelect.addEventListener('change', (e) => {
        currentCategory = e.target.value;
        currentPage = 1;
        fetchQuestions();
    });

    paginationEl.addEventListener('click', (e) => {
        if (e.target.classList.contains('js-page-btn')) {
            const page = parseInt(e.target.dataset.page);
            if (page && !e.target.disabled) {
                currentPage = page;
                fetchQuestions();
            }
        }
    });

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

    // Init
    fetchQuestions();
});
