function debounce(fn, delay = 250) {
    let timer;
    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => fn(...args), delay);
    };
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getModuleConfig(selector) {
    return document.querySelector(selector);
}

function confirmFormMarkup(action, message) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

    return `<form method="POST" action="${String(action).replace(/"/g, '&quot;')}" class="inline" data-confirm="${escapeHtml(message)}">
        <input type="hidden" name="_token" value="${escapeHtml(token)}">
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit" class="text-rose-600 transition hover:text-rose-700">Delete</button>
    </form>`;
}

export function wireConfirmForms(scope = document) {
    scope.querySelectorAll('form[data-confirm]').forEach((form) => {
        if (form.dataset.confirmBound === '1') {
            return;
        }

        form.dataset.confirmBound = '1';
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || 'Are you sure?')) {
                event.preventDefault();
            }
        });
    });
}

window.EmsDataTable = function EmsDataTable(options) {
    const {
        endpoint,
        tableBodySelector,
        paginationSelector,
        searchInputSelector,
        sortSelector,
        filterSelector,
        extraState = {},
        queryBuilder,
        rowRenderer,
        debounceMs = 350,
    } = options;

    const tbody = document.querySelector(tableBodySelector);
    const paginationEl = document.querySelector(paginationSelector);
    const searchInput = searchInputSelector ? document.querySelector(searchInputSelector) : null;
    const sortSelect = sortSelector ? document.querySelector(sortSelector) : null;
    const filterEl = filterSelector ? document.querySelector(filterSelector) : null;

    const state = {
        page: 1,
        per_page: 15,
        search: '',
        sort: 'id',
        direction: 'desc',
        filters: {},
        ...extraState,
    };

    function buildQuery() {
        if (typeof queryBuilder === 'function') {
            return queryBuilder(state);
        }

        const params = new URLSearchParams({
            page: state.page,
            per_page: state.per_page,
            search: state.search,
            sort: state.sort,
            direction: state.direction,
        });

        Object.entries(state.filters).forEach(([key, value]) => {
            if (value !== '' && value != null) {
                params.append(`filters[${key}]`, value);
            }
        });

        return params;
    }

    async function load() {
        if (!tbody) {
            return;
        }

        tbody.innerHTML = '<tr><td colspan="99" class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">Loading...</td></tr>';

        const response = await fetch(`${endpoint}?${buildQuery().toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            tbody.innerHTML = '<tr><td colspan="99" class="px-4 py-6 text-center text-rose-500">Failed to load data.</td></tr>';
            return;
        }

        const json = await response.json();
        tbody.innerHTML = '';

        if (!json.data.length) {
            tbody.innerHTML = '<tr><td colspan="99" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No records found.</td></tr>';
            if (paginationEl) {
                paginationEl.innerHTML = '';
            }
            return;
        }

        json.data.forEach((row) => {
            tbody.insertAdjacentHTML('beforeend', rowRenderer(row));
        });

        wireConfirmForms(tbody);
        renderPagination(json.meta);
    }

    function renderPagination(meta) {
        if (!paginationEl) {
            return;
        }

        if (!meta || meta.last_page <= 1) {
            paginationEl.innerHTML = '';
            return;
        }

        const items = [];
        for (let page = 1; page <= meta.last_page; page += 1) {
            if (page === meta.current_page) {
                items.push(`<span class="inline-flex min-w-10 items-center justify-center rounded-full bg-slate-950 px-3 py-2 text-sm font-semibold text-white dark:bg-white dark:text-slate-950">${page}</span>`);
            } else {
                items.push(`<button type="button" data-page="${page}" class="inline-flex min-w-10 items-center justify-center rounded-full border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-950 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white">${page}</button>`);
            }
        }

        paginationEl.innerHTML = `<div class="flex flex-wrap gap-2">${items.join('')}</div>`;
        paginationEl.querySelectorAll('[data-page]').forEach((button) => {
            button.addEventListener('click', () => {
                state.page = Number.parseInt(button.dataset.page || '1', 10);
                load();
            });
        });
    }

    searchInput?.addEventListener(
        'input',
        debounce(() => {
            state.search = searchInput.value;
            state.page = 1;
            load();
        }, debounceMs)
    );

    sortSelect?.addEventListener('change', () => {
        const [sort, direction = 'desc'] = sortSelect.value.split(':');
        state.sort = sort;
        state.direction = direction;
        state.page = 1;
        load();
    });

    filterEl?.addEventListener('change', () => {
        const key = (filterEl.name || '').replace('filters[', '').replace(']', '') || 'filter';
        state.filters[key] = filterEl.value;
        state.page = 1;
        load();
    });

    return { load, state };
};

function initOrganizationsTable() {
    if (!document.getElementById('org-table-body')) {
        return;
    }

    const config = getModuleConfig('[data-organizations-config]');
    const base = config?.dataset.organizationsBase;
    const table = window.EmsDataTable({
        endpoint: config?.dataset.organizationsEndpoint,
        tableBodySelector: '#org-table-body',
        paginationSelector: '#org-pagination',
        searchInputSelector: '#org-search',
        sortSelector: '#org-sort',
        rowRenderer(row) {
            const show = `${base}/${row.id}`;
            const edit = `${show}/edit`;

            return `<tr>
                <td class="font-medium text-slate-900 dark:text-white">${escapeHtml(row.name)}</td>
                <td class="text-slate-500 dark:text-slate-400">${escapeHtml(row.slug)}</td>
                <td><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-200">${escapeHtml(row.status)}</span></td>
                <td>${row.users_count ?? 0}</td>
                <td class="text-right">
                    <div class="flex justify-end gap-3 text-sm">
                        <a href="${show}" class="text-indigo-600 transition hover:text-indigo-700">View</a>
                        <a href="${edit}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Edit</a>
                    </div>
                </td>
            </tr>`;
        },
    });

    table.state.sort = 'id';
    table.state.direction = 'desc';
    table.load();
}

function initCategoriesTable() {
    if (!document.getElementById('cat-table-body')) {
        return;
    }

    const mainOnly = document.getElementById('cat-main-only');
    const config = getModuleConfig('[data-categories-config]');
    const base = config?.dataset.categoriesBase;

    const table = window.EmsDataTable({
        endpoint: config?.dataset.categoriesEndpoint,
        tableBodySelector: '#cat-table-body',
        paginationSelector: '#cat-pagination',
        searchInputSelector: '#cat-search',
        extraState: { sort: 'name', direction: 'asc', main_only: false },
        queryBuilder(state) {
            return new URLSearchParams({
                page: state.page,
                per_page: state.per_page,
                search: state.search,
                sort: state.sort,
                direction: state.direction,
                main_only: state.main_only ? '1' : '0',
            });
        },
        rowRenderer(row) {
            const edit = `${base}/${row.id}/edit`;
            const parent = row.parent ? row.parent.name : '-';
            const destroy = confirmFormMarkup(`${base}/${row.id}`, 'Delete this category?');

            return `<tr>
                <td class="font-medium text-slate-900 dark:text-white">${escapeHtml(row.name)}</td>
                <td>${escapeHtml(parent)}</td>
                <td>${escapeHtml(row.status)}</td>
                <td class="text-right">
                    <div class="flex justify-end gap-3 text-sm">
                        <a href="${edit}" class="text-sky-600 transition hover:text-sky-700">Edit</a>
                        ${destroy}
                    </div>
                </td>
            </tr>`;
        },
    });

    mainOnly?.addEventListener('change', () => {
        table.state.main_only = mainOnly.checked;
        table.state.page = 1;
        table.load();
    });

    table.load();
}

function initMembersTable() {
    if (!document.getElementById('mem-table-body')) {
        return;
    }

    const config = getModuleConfig('[data-members-config]');
    const base = config?.dataset.membersBase;
    const currentUserId = Number.parseInt(config?.dataset.currentUserId || '0', 10);

    window.EmsDataTable({
        endpoint: config?.dataset.membersEndpoint,
        tableBodySelector: '#mem-table-body',
        paginationSelector: '#mem-pagination',
        searchInputSelector: '#mem-search',
        rowRenderer(row) {
            const remove = row.id === currentUserId
                ? ''
                : `<form method="POST" action="${base}/${row.id}" class="inline" data-confirm="Remove from organization?">
                    <input type="hidden" name="_token" value="${escapeHtml(document.querySelector('meta[name="csrf-token"]')?.content || '')}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="text-rose-600 transition hover:text-rose-700">Remove</button>
                </form>`;

            return `<tr>
                <td class="font-medium text-slate-900 dark:text-white">${escapeHtml(row.name)}</td>
                <td>${escapeHtml(row.email)}</td>
                <td>${escapeHtml(row.role)}</td>
                <td class="text-right text-sm">${remove}</td>
            </tr>`;
        },
    }).load();
}

function initQuestionsTable() {
    if (!document.getElementById('q-table-body')) {
        return;
    }

    const config = getModuleConfig('[data-questions-config]');
    const base = config?.dataset.questionsBase;

    window.EmsDataTable({
        endpoint: config?.dataset.questionsEndpoint,
        tableBodySelector: '#q-table-body',
        paginationSelector: '#q-pagination',
        searchInputSelector: '#q-search',
        sortSelector: '#q-sort',
        filterSelector: '#q-filter-cat',
        rowRenderer(row) {
            const show = `${base}/${row.id}`;
            const edit = `${show}/edit`;
            const preview = (row.body || '').replace(/<[^>]+>/g, '').slice(0, 80);
            const category = row.category ? row.category.name : '-';

            return `<tr>
                <td class="text-slate-900 dark:text-white">${escapeHtml(preview)}</td>
                <td>${escapeHtml(row.type)}</td>
                <td>${escapeHtml(row.difficulty)}</td>
                <td>${escapeHtml(category)}</td>
                <td class="text-right">
                    <div class="flex justify-end gap-3 text-sm">
                        <a href="${show}" class="text-emerald-600 transition hover:text-emerald-700">View</a>
                        <a href="${edit}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Edit</a>
                    </div>
                </td>
            </tr>`;
        },
    }).load();
}

function initExamsTable() {
    if (!document.getElementById('exam-table-body')) {
        return;
    }

    const config = getModuleConfig('[data-exams-config]');
    const base = config?.dataset.examsBase;

    window.EmsDataTable({
        endpoint: config?.dataset.examsEndpoint,
        tableBodySelector: '#exam-table-body',
        paginationSelector: '#exam-pagination',
        searchInputSelector: '#exam-search',
        sortSelector: '#exam-sort',
        rowRenderer(row) {
            const show = `${base}/${row.id}`;
            const edit = `${show}/edit`;

            return `<tr>
                <td class="font-medium text-slate-900 dark:text-white">${escapeHtml(row.title)}</td>
                <td>${escapeHtml(row.status)}</td>
                <td>${escapeHtml(row.duration)} min</td>
                <td class="text-right">
                    <div class="flex justify-end gap-3 text-sm">
                        <a href="${show}" class="text-sky-600 transition hover:text-sky-700">View</a>
                        <a href="${edit}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Edit</a>
                    </div>
                </td>
            </tr>`;
        },
    }).load();
}

function initDisclosureToggles() {
    document.querySelectorAll('[data-toggle-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.querySelector(button.dataset.toggleTarget || '');
            target?.classList.toggle('hidden');
        });
    });
}

function initQuestionForms() {
    document.querySelectorAll('[data-question-form]').forEach((form) => {
        const typeSelect = form.querySelector('[data-question-type]');
        const optionsSection = form.querySelector('[data-question-options]');

        if (!typeSelect || !optionsSection) {
            return;
        }

        const sync = () => {
            optionsSection.classList.toggle('hidden', typeSelect.value !== 'mcq');
        };

        typeSelect.addEventListener('change', sync);
        sync();
    });
}

function loadScriptOnce(src) {
    const existing = document.querySelector(`script[data-dynamic-src="${src}"]`);
    if (existing) {
        return existing.dataset.loaded === '1'
            ? Promise.resolve()
            : new Promise((resolve, reject) => {
                existing.addEventListener('load', resolve, { once: true });
                existing.addEventListener('error', reject, { once: true });
            });
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.dataset.dynamicSrc = src;
        script.addEventListener('load', () => {
            script.dataset.loaded = '1';
            resolve();
        });
        script.addEventListener('error', reject, { once: true });
        document.body.appendChild(script);
    });
}

async function initRichTextEditors() {
    const editors = [...document.querySelectorAll('[data-rich-text]')];
    if (!editors.length) {
        return;
    }

    if (typeof window.tinymce === 'undefined') {
        try {
            await loadScriptOnce('https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js');
        } catch (error) {
            return;
        }
    }

    editors.forEach((editor) => {
        if (!editor.id || editor.dataset.editorReady === '1') {
            return;
        }

        editor.dataset.editorReady = '1';
        window.tinymce.init({
            selector: `#${editor.id}`,
            height: Number.parseInt(editor.dataset.editorHeight || '220', 10),
            menubar: false,
            plugins: 'lists link',
            toolbar: editor.dataset.editorToolbar || 'undo redo | bold italic | bullist numlist | link',
            promotion: false,
        });
    });
}

export function initDataTablesAndForms() {
    initDisclosureToggles();
    initOrganizationsTable();
    initCategoriesTable();
    initMembersTable();
    initQuestionsTable();
    initExamsTable();
    initQuestionForms();
    initRichTextEditors();
    wireConfirmForms();
}
