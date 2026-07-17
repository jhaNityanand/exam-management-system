/**
 * Reusable AJAX-powered DataTable component manager.
 * Handles state, data fetching, input debouncing, custom row rendering,
 * pagination generation, per-page updates, and a responsive offcanvas filter drawer.
 */
class AjaxTable {
    constructor(config = {}) {
        // Selector Config
        this.containerSelector = config.containerSelector || '#ajax-table-container';
        this.apiUrl = config.apiUrl || '';
        this.tableBodySelector = config.tableBodySelector || '#questions-table-body';
        this.paginationSelector = config.paginationSelector || '#questions-pagination';
        this.searchSelector = config.searchSelector || '#questions-search';
        this.perPageSelector = config.perPageSelector || '#questions-per-page';
        this.filterDrawerSelector = config.filterDrawerSelector || '#filter-drawer';
        this.filterToggleSelector = config.filterToggleSelector || '#btn-toggle-filters';
        this.filterDrawerFormSelector = config.filterDrawerFormSelector || '#filter-drawer-form';
        this.loadingSelector = config.loadingSelector || '#questions-loading';
        this.emptySelector = config.emptySelector || '#questions-empty';
        this.totalCountSelector = config.totalCountSelector || '#questions-total-count';
        
        // Callbacks & Tuning
        this.rowTemplate = config.rowTemplate || ((item) => `<tr><td>${JSON.stringify(item)}</td></tr>`);
        this.onFetchSuccess = config.onFetchSuccess || null;
        this.onFiltersChange = config.onFiltersChange || null;
        this.debounceTime = config.debounceTime || 350;
        this.defaultParams = config.defaultParams || {};
        this.skeletonRows = config.skeletonRows ?? null;
        this.skeletonColumns = config.skeletonColumns ?? 5;
        this.skeletonTemplate = config.skeletonTemplate || null;
        this.autoFetch = config.autoFetch !== false;
        this.preferSkeleton = config.preferSkeleton !== false;

        // Local state
        this.page = 1;
        this.per_page = 10;
        this.search = '';
        this.filters = {};
        this.defaultSort = config.defaultSort || 'id';
        this.defaultDirection = config.defaultDirection || 'desc';
        this.sort = this.defaultSort;
        this.direction = this.defaultDirection;
        
        // Element cache
        this.elements = {};
        this.searchDebounceTimer = null;

        this.init();
    }

    init() {
        // Cache elements
        this.elements.tableBody = document.querySelector(this.tableBodySelector);
        this.elements.pagination = document.querySelector(this.paginationSelector);
        this.elements.search = document.querySelector(this.searchSelector);
        this.elements.perPage = document.querySelector(this.perPageSelector);
        this.elements.drawer = document.querySelector(this.filterDrawerSelector);
        this.elements.drawerForm = document.querySelector(this.filterDrawerFormSelector);
        this.elements.loading = document.querySelector(this.loadingSelector);
        this.elements.empty = document.querySelector(this.emptySelector);
        this.elements.totalCount = document.querySelector(this.totalCountSelector);
        this.elements.toggle = document.querySelector(this.filterToggleSelector);

        // Retrieve initial per_page state
        if (this.elements.perPage) {
            this.per_page = parseInt(this.elements.perPage.value) || 10;
        }

        // Set up Event Listeners
        this.bindEvents();

        // Ensure first paint already shows a skeleton preview before JS fetch completes
        if (this.elements.tableBody && !this.elements.tableBody.querySelector('.ajax-table-skeleton-row')) {
            this.renderSkeleton();
        }

        if (this.autoFetch) {
            this.fetch();
        }
    }

    bindEvents() {
        // 1. Debounced Search Input
        if (this.elements.search) {
            this.elements.search.addEventListener('input', (e) => {
                clearTimeout(this.searchDebounceTimer);
                this.searchDebounceTimer = setTimeout(() => {
                    this.search = e.target.value.trim();
                    this.page = 1; // Reset to page 1 on new search
                    this.fetch();
                }, this.debounceTime);
            });
        }

        // 2. Per Page selector change
        if (this.elements.perPage) {
            this.elements.perPage.addEventListener('change', (e) => {
                this.per_page = parseInt(e.target.value) || 10;
                this.page = 1;
                this.fetch();
            });
        }

        // 3. Toggle Drawer open/close
        if (this.elements.toggle) {
            this.elements.toggle.addEventListener('click', () => this.openDrawer());
        }

        if (this.elements.drawer) {
            // Close buttons inside drawer
            const closeButtons = this.elements.drawer.querySelectorAll('[data-drawer-close], .offcanvas-close');
            closeButtons.forEach(btn => {
                btn.addEventListener('click', () => this.closeDrawer());
            });

            // Backdrop handling
            let backdrop = document.querySelector('#offcanvas-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'offcanvas-backdrop';
                backdrop.className = 'offcanvas-backdrop';
                document.body.appendChild(backdrop);
            }
            backdrop.addEventListener('click', () => this.closeDrawer());
        }

        // 4. Filter Form Submit (Apply)
        if (this.elements.drawerForm) {
            this.elements.drawerForm.addEventListener('submit', (e) => {
                e.preventDefault();

                const validation = window.EmsFilterDrawer?.validateAll?.(this.elements.drawerForm);
                if (validation && validation.valid === false) {
                    validation.container?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'warning',
                            title: 'Invalid date range',
                            text: validation.message,
                            confirmButtonText: 'OK',
                        });
                    } else {
                        window.alert(validation.message);
                    }
                    return;
                }

                this.applyFiltersFromForm();
                this.notifyFiltersChange();
                this.closeDrawer();
                this.page = 1;
                this.fetch();
            });

            // Reset filters
            const resetBtn = this.elements.drawerForm.querySelector('[type="reset"]');
            if (resetBtn) {
                resetBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.elements.drawerForm.reset();
                    
                    // Reset TomSelect dropdowns if any
                    const tomSelects = this.elements.drawerForm.querySelectorAll('.tomselected');
                    tomSelects.forEach(select => {
                        if (select.tomselect) {
                            select.tomselect.clear(true);
                        }
                    });

                    this.elements.drawerForm.querySelectorAll('[data-filter-date-range]').forEach((range) => {
                        range._resetFilterDateRange?.();
                    });

                    // Restore default sort select value if present
                    const sortSelect = this.elements.drawerForm.querySelector('[name="sort"]');
                    if (sortSelect) {
                        sortSelect.value = `${this.defaultSort}:${this.defaultDirection}`;
                    }

                    this.filters = {};
                    this.sort = this.defaultSort;
                    this.direction = this.defaultDirection;
                    this.updateFilterBadge();
                    this.notifyFiltersChange();
                    this.closeDrawer();
                    this.page = 1;
                    this.fetch();
                });
            }
        }

        // 5. Pagination clicks (Delegated)
        if (this.elements.pagination) {
            this.elements.pagination.addEventListener('click', (e) => {
                const target = e.target.closest('[data-page]');
                if (target && !target.classList.contains('disabled')) {
                    const targetPage = parseInt(target.getAttribute('data-page'));
                    if (targetPage && targetPage !== this.page) {
                        this.page = targetPage;
                        this.fetch();
                        // Scroll to top of table gently
                        const container = document.querySelector(this.containerSelector) || this.elements.tableBody.closest('section');
                        if (container) {
                            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                }
            });
        }

        // 6. Escape closes filter drawer
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.elements.drawer?.classList.contains('is-open')) {
                this.closeDrawer();
            }
        });
    }

    openDrawer() {
        if (this.elements.drawer) {
            this.elements.drawer.classList.add('is-open');
            this.elements.drawer.setAttribute('aria-hidden', 'false');
        }
        if (this.elements.toggle) {
            this.elements.toggle.setAttribute('aria-expanded', 'true');
        }
        const backdrop = document.querySelector('#offcanvas-backdrop');
        if (backdrop) {
            backdrop.classList.add('is-visible');
        }
        document.body.style.overflow = 'hidden';
    }

    closeDrawer() {
        if (this.elements.drawer) {
            this.elements.drawer.classList.remove('is-open');
            this.elements.drawer.setAttribute('aria-hidden', 'true');
        }
        if (this.elements.toggle) {
            this.elements.toggle.setAttribute('aria-expanded', 'false');
        }
        const backdrop = document.querySelector('#offcanvas-backdrop');
        if (backdrop) {
            backdrop.classList.remove('is-visible');
        }
        document.body.style.overflow = '';
    }

    applyFiltersFromForm() {
        if (!this.elements.drawerForm) return;

        const formData = new FormData(this.elements.drawerForm);
        const newFilters = {};

        formData.forEach((value, key) => {
            if (key === '_token' || key === 'sort') return;

            const match = key.match(/^filters\[(.*?)\](?:\[\])?$/);
            const filterKey = match ? match[1] : key;

            if (value === '' || value === null) return;

            if (Object.prototype.hasOwnProperty.call(newFilters, filterKey)) {
                if (!Array.isArray(newFilters[filterKey])) {
                    newFilters[filterKey] = [newFilters[filterKey]];
                }
                newFilters[filterKey].push(value);
            } else {
                newFilters[filterKey] = value;
            }
        });

        // Optional combined sort field: "column:direction"
        const sortValue = formData.get('sort');
        if (typeof sortValue === 'string' && sortValue.includes(':')) {
            const [sortField, sortDir = 'desc'] = sortValue.split(':');
            this.sort = sortField || this.defaultSort;
            this.direction = sortDir === 'asc' ? 'asc' : 'desc';
        }

        this.filters = newFilters;
        this.updateFilterBadge();
    }

    notifyFiltersChange() {
        if (typeof this.onFiltersChange === 'function') {
            this.onFiltersChange({
                filters: { ...this.filters },
                sort: this.sort,
                direction: this.direction,
            });
        }
    }

    /**
     * Clear a single applied filter (or reset sort) and refetch.
     * Used by removable filter chips.
     */
    clearFilter(key) {
        if (key === 'sort') {
            this.sort = this.defaultSort;
            this.direction = this.defaultDirection;
            const sortSelect = this.elements.drawerForm?.querySelector('[name="sort"]');
            if (sortSelect) {
                sortSelect.value = `${this.defaultSort}:${this.defaultDirection}`;
            }
        } else {
            delete this.filters[key];
            const field = this.elements.drawerForm?.querySelector(
                `[name="filters[${key}]"], [name="filters[${key}][]"]`
            );
            if (field) {
                const dateRange = field.closest('[data-filter-date-range]');
                if (dateRange) {
                    dateRange.querySelectorAll('[data-range-from], [data-range-to]').forEach((input) => {
                        const match = input.name.match(/^filters\[(.*?)\]$/);
                        if (match) delete this.filters[match[1]];
                    });
                    dateRange._resetFilterDateRange?.();
                } else if (field.tomselect) {
                    field.tomselect.clear(true);
                } else if (field.multiple) {
                    Array.from(field.options).forEach((opt) => {
                        opt.selected = false;
                    });
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    field.value = '';
                }
            }

            // Marks filter uses button chips + hidden inputs
            if (key === 'marks') {
                this.elements.drawerForm?.querySelectorAll('.marks-option-btn.is-selected').forEach((btn) => {
                    btn.classList.remove('is-selected');
                    btn.setAttribute('aria-pressed', 'false');
                });
                const host = this.elements.drawerForm?.querySelector('#drawer-marks-filter-values');
                if (host) host.innerHTML = '';
                const selectAll = document.getElementById('marks-select-all-btn');
                if (selectAll) selectAll.textContent = 'Select All';
            }
        }
        this.updateFilterBadge();
        this.notifyFiltersChange();
        this.page = 1;
        this.fetch();
    }

    updateFilterBadge() {
        const badgeGroups = new Set(
            Object.keys(this.filters)
                .filter((key) => key !== 'trash')
                .map((key) => {
                    if (key === 'date_from' || key === 'date_to') return 'published_date';
                    if (key === 'created_from' || key === 'created_to') return 'created_date';
                    return key;
                })
        );
        const activeCount = badgeGroups.size;
        if (this.elements.toggle) {
            let badge = this.elements.toggle.querySelector('.filter-badge');
            this.elements.toggle.classList.toggle('is-active', activeCount > 0);
            if (activeCount > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'filter-badge';
                    this.elements.toggle.appendChild(badge);
                }
                badge.textContent = activeCount;
                badge.classList.add('is-visible');
            } else if (badge) {
                badge.remove();
            }
        }
    }

    fetch() {
        if (this.loading) return;
        this.loading = true;

        this.showLoading();

        // Build Query URL
        const url = new URL(this.apiUrl, window.location.origin);
        
        // Append paging / search / sorting
        url.searchParams.set('page', this.page);
        url.searchParams.set('per_page', this.per_page);
        url.searchParams.set('sort', this.sort);
        url.searchParams.set('direction', this.direction);
        
        if (this.search) {
            url.searchParams.set('search', this.search);
        }

        // Append filters
        Object.entries(this.filters).forEach(([key, val]) => {
            if (Array.isArray(val)) {
                val.forEach((item) => {
                    if (item !== '' && item !== null && item !== undefined) {
                        url.searchParams.append(`filters[${key}][]`, item);
                    }
                });
            } else if (val !== '' && val !== null && val !== undefined) {
                url.searchParams.set(`filters[${key}]`, val);
            }
        });

        // Append default custom parameters
        Object.entries(this.defaultParams).forEach(([key, val]) => {
            url.searchParams.set(key, val);
        });

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Failed to load table content.');
            return res.json();
        })
        .then(response => {
            this.loading = false;
            this.hideLoading();

            const items = response.data || [];
            const meta = response.meta || { current_page: 1, last_page: 1, total: 0, per_page: this.per_page };

            // Keep local page in sync with API (avoids stale S.No after refresh / pagination)
            if (meta.current_page) {
                this.page = Number(meta.current_page) || this.page;
            }
            if (meta.per_page) {
                this.per_page = Number(meta.per_page) || this.per_page;
            }

            // Render Rows
            if (this.elements.tableBody) {
                if (items.length > 0) {
                    this.elements.tableBody.innerHTML = items
                        .map((item, index) => this.rowTemplate(item, index, meta))
                        .join('');
                    if (this.elements.empty) this.elements.empty.classList.add('hidden');
                    this.elements.tableBody.closest('table').classList.remove('opacity-40');
                } else {
                    this.elements.tableBody.innerHTML = '';
                    if (this.elements.empty) this.elements.empty.classList.remove('hidden');
                }
            }

            // Update Counts
            if (this.elements.totalCount) {
                this.elements.totalCount.textContent = meta.total;
            }

            // Render Pagination Component
            this.renderPagination(meta);

            // Execute custom complete hooks
            if (this.onFetchSuccess) {
                this.onFetchSuccess(response);
            }
        })
        .catch(err => {
            this.loading = false;
            this.hideLoading();
            if (this.elements.tableBody) {
                this.elements.tableBody.innerHTML = `
                    <tr>
                        <td colspan="${this.skeletonColumns}" class="px-6 py-10 text-center text-sm text-rose-500 dark:text-rose-400">
                            Unable to load data. Please refresh and try again.
                        </td>
                    </tr>
                `;
            }
            console.error('AjaxTable Fetch Error:', err);
        });
    }

    showLoading() {
        const container = this.elements.tableBody?.closest(this.containerSelector)
            || document.querySelector(this.containerSelector);
        if (container) {
            container.setAttribute('aria-busy', 'true');
        }

        // Prefer skeleton preview over a blocking spinner overlay
        if (this.preferSkeleton) {
            if (this.elements.loading) {
                this.elements.loading.classList.add('hidden');
            }
        } else if (this.elements.loading) {
            this.elements.loading.classList.remove('hidden');
        }

        if (this.elements.empty) {
            this.elements.empty.classList.add('hidden');
        }
        if (this.elements.tableBody) {
            const table = this.elements.tableBody.closest('table');
            if (table && !this.preferSkeleton) {
                table.classList.add('opacity-40');
            } else if (table) {
                table.classList.remove('opacity-40');
            }
            this.renderSkeleton();
        }
    }

    hideLoading() {
        const container = this.elements.tableBody?.closest(this.containerSelector)
            || document.querySelector(this.containerSelector);
        if (container) {
            container.removeAttribute('aria-busy');
        }
        if (this.elements.loading) {
            this.elements.loading.classList.add('hidden');
        }
        if (this.elements.tableBody) {
            const table = this.elements.tableBody.closest('table');
            if (table) table.classList.remove('opacity-40');
        }
    }

    getSkeletonRowCount() {
        if (this.skeletonRows != null) {
            return this.skeletonRows;
        }
        const perPage = Number(this.per_page) || 10;
        return Math.min(Math.max(perPage, 5), 10);
    }

    renderSkeleton() {
        if (!this.elements.tableBody) return;
        const rows = this.getSkeletonRowCount();

        if (typeof this.skeletonTemplate === 'function') {
            this.elements.tableBody.innerHTML = Array.from({ length: rows }, (_, i) => this.skeletonTemplate(i)).join('');
            return;
        }

        let html = '';
        for (let i = 0; i < rows; i++) {
            html += '<tr class="ajax-table-skeleton-row" aria-hidden="true">';
            for (let c = 0; c < this.skeletonColumns; c++) {
                const width = c === this.skeletonColumns - 1 ? '40%' : `${55 + ((i + c) % 4) * 10}%`;
                const align = c === this.skeletonColumns - 1 ? 'text-right' : '';
                html += `<td class="px-4 py-3 sm:px-6 sm:py-4 ${align}"><div class="ajax-skeleton-bar" style="width:${width}; margin-left:${c === this.skeletonColumns - 1 ? 'auto' : '0'}"></div></td>`;
            }
            html += '</tr>';
        }
        this.elements.tableBody.innerHTML = html;
    }

    renderPagination(meta) {
        if (!this.elements.pagination) return;

        const currentPage = meta.current_page;
        const lastPage = meta.last_page;
        const total = meta.total;
        const perPage = meta.per_page;

        if (total === 0) {
            this.elements.pagination.innerHTML = '';
            return;
        }

        const from = (currentPage - 1) * perPage + 1;
        const to = Math.min(currentPage * perPage, total);

        let html = `
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full text-slate-600 dark:text-slate-400">
                <div class="text-sm font-medium">
                    Showing <span class="font-bold text-slate-800 dark:text-slate-200">${from}</span> to 
                    <span class="font-bold text-slate-800 dark:text-slate-200">${to}</span> of 
                    <span class="font-bold text-slate-800 dark:text-slate-200">${total}</span> results
                </div>
                
                <div class="flex items-center gap-1">
        `;

        // Previous button
        html += `
            <button type="button" data-page="${currentPage - 1}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80 ${currentPage === 1 ? 'disabled pointer-events-none opacity-40' : ''}" aria-label="Previous page">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
        `;

        // Compute page numbers to show
        const pages = this.getPageRange(currentPage, lastPage);

        pages.forEach(p => {
            if (p === '...') {
                html += `
                    <span class="inline-flex h-9 w-9 items-center justify-center text-sm font-semibold text-slate-400">...</span>
                `;
            } else {
                const isActive = p === currentPage;
                html += `
                    <button type="button" data-page="${p}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-sm font-semibold transition ${isActive ? 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-700' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80'}">${p}</button>
                `;
            }
        });

        // Next button
        html += `
            <button type="button" data-page="${currentPage + 1}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80 ${currentPage === lastPage ? 'disabled pointer-events-none opacity-40' : ''}" aria-label="Next page">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        `;

        html += `
                </div>
            </div>
        `;

        this.elements.pagination.innerHTML = html;
    }

    getPageRange(current, last) {
        if (last <= 7) {
            return Array.from({ length: last }, (_, i) => i + 1);
        }

        const range = [];
        const leftLimit = current - 2;
        const rightLimit = current + 2;

        range.push(1);

        if (leftLimit > 2) {
            range.push('...');
        }

        const start = Math.max(2, leftLimit);
        const end = Math.min(last - 1, rightLimit);

        for (let i = start; i <= end; i++) {
            range.push(i);
        }

        if (rightLimit < last - 1) {
            range.push('...');
        }

        range.push(last);
        return range;
    }
}

// Attach globally for dynamic module access
window.AjaxTable = AjaxTable;
