/**
 * Lightweight AJAX table: pagination, sort, search (debounced), optional filters.
 */
window.EmsDataTable = function (options) {
    const {
        endpoint,
        tableBodySelector,
        paginationSelector,
        searchInputSelector,
        sortSelector,
        filterSelector,
        rowRenderer,
        debounceMs = 350,
    } = options;

    const tbody = document.querySelector(tableBodySelector);
    const paginationEl = document.querySelector(paginationSelector);
    const searchInput = document.querySelector(searchInputSelector);
    const sortSelect = sortSelector ? document.querySelector(sortSelector) : null;
    const filterEl = filterSelector ? document.querySelector(filterSelector) : null;

    const state = {
        page: 1,
        per_page: 15,
        search: '',
        sort: 'id',
        direction: 'desc',
        filters: {},
    };

    let debounceTimer;

    function buildQuery() {
        const params = new URLSearchParams({
            page: state.page,
            per_page: state.per_page,
            search: state.search,
            sort: state.sort,
            direction: state.direction,
        });
        Object.entries(state.filters).forEach(([k, v]) => {
            if (v !== '' && v != null) params.append(`filters[${k}]`, v);
        });
        return params.toString();
    }

    async function load() {
        if (!tbody) return;
        tbody.innerHTML =
            '<tr><td colspan="99" class="px-4 py-6 text-center text-gray-500">Loading…</td></tr>';
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(`${endpoint}?${buildQuery()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            credentials: 'same-origin',
        });
        if (!res.ok) {
            tbody.innerHTML =
                '<tr><td colspan="99" class="px-4 py-6 text-center text-red-500">Failed to load data.</td></tr>';
            return;
        }
        const json = await res.json();
        tbody.innerHTML = '';
        json.data.forEach((row) => {
            tbody.insertAdjacentHTML('beforeend', rowRenderer(row));
        });
        renderPagination(json.meta);
    }

    function renderPagination(meta) {
        if (!paginationEl) return;
        if (!meta || meta.last_page <= 1) {
            paginationEl.innerHTML = '';
            return;
        }
        const parts = [];
        for (let p = 1; p <= meta.last_page; p++) {
            if (p === meta.current_page) {
                parts.push(`<span class="px-3 py-1 rounded bg-indigo-600 text-white text-sm">${p}</span>`);
            } else {
                parts.push(
                    `<button type="button" data-page="${p}" class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">${p}</button>`
                );
            }
        }
        paginationEl.innerHTML = `<div class="flex flex-wrap gap-1 items-center">${parts.join('')}</div>`;
        paginationEl.querySelectorAll('[data-page]').forEach((btn) => {
            btn.addEventListener('click', () => {
                state.page = parseInt(btn.getAttribute('data-page'), 10);
                load();
            });
        });
    }

    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            state.search = searchInput.value;
            state.page = 1;
            load();
        }, debounceMs);
    });

    sortSelect?.addEventListener('change', () => {
        const [col, dir] = sortSelect.value.split(':');
        state.sort = col;
        state.direction = dir || 'desc';
        state.page = 1;
        load();
    });

    filterEl?.addEventListener('change', () => {
        state.filters[filterEl.name.replace('filters[', '').replace(']', '')] = filterEl.value;
        state.page = 1;
        load();
    });

    return { load, state };
};
