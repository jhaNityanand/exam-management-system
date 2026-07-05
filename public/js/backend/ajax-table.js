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
        this.debounceTime = config.debounceTime || 350;
        this.defaultParams = config.defaultParams || {};

        // Local state
        this.page = 1;
        this.per_page = 10;
        this.search = '';
        this.filters = {};
        this.sort = config.defaultSort || 'id';
        this.direction = config.defaultDirection || 'desc';
        
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

        // Initial Fetch
        this.fetch();
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
                this.applyFiltersFromForm();
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
                            select.tomselect.setValue('', true); // true silences change event
                        }
                    });

                    this.filters = {};
                    this.updateFilterBadge();
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
    }

    openDrawer() {
        if (this.elements.drawer) {
            this.elements.drawer.classList.add('is-open');
        }
        const backdrop = document.querySelector('#offcanvas-backdrop');
        if (backdrop) {
            backdrop.classList.add('is-visible');
        }
    }

    closeDrawer() {
        if (this.elements.drawer) {
            this.elements.drawer.classList.remove('is-open');
        }
        const backdrop = document.querySelector('#offcanvas-backdrop');
        if (backdrop) {
            backdrop.classList.remove('is-visible');
        }
    }

    applyFiltersFromForm() {
        if (!this.elements.drawerForm) return;

        const formData = new FormData(this.elements.drawerForm);
        const newFilters = {};
        
        formData.forEach((value, key) => {
            // Only pull keys matching naming convention or exclude CSRF token
            if (key === '_token') return;
            
            // Format check: if keys are named filters[name], extract name
            const match = key.match(/^filters\[(.*?)\]$/);
            const filterKey = match ? match[1] : key;

            if (value !== '' && value !== null) {
                newFilters[filterKey] = value;
            }
        });

        this.filters = newFilters;
        this.updateFilterBadge();
    }

    updateFilterBadge() {
        const activeCount = Object.keys(this.filters).length;
        if (this.elements.toggle) {
            let badge = this.elements.toggle.querySelector('.filter-badge');
            if (activeCount > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'filter-badge';
                    this.elements.toggle.appendChild(badge);
                }
                badge.textContent = activeCount;
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
            url.searchParams.set(`filters[${key}]`, val);
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

            // Render Rows
            if (this.elements.tableBody) {
                if (items.length > 0) {
                    this.elements.tableBody.innerHTML = items.map((item, index) => this.rowTemplate(item, index)).join('');
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
            console.error('AjaxTable Fetch Error:', err);
        });
    }

    showLoading() {
        if (this.elements.loading) {
            this.elements.loading.classList.remove('hidden');
        }
        if (this.elements.tableBody) {
            const table = this.elements.tableBody.closest('table');
            if (table) table.classList.add('opacity-40');
        }
    }

    hideLoading() {
        if (this.elements.loading) {
            this.elements.loading.classList.add('hidden');
        }
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
