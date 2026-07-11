/**
 * category-tree.js
 *
 * Shared AJAX tree explorer for Question and Exam category index pages.
 * Configure via window.categoryTreeConfig before this script loads:
 *   - indexUrl            (named route for the list / AJAX HTML partial)
 *   - detailsBaseUrl      (base URL for JSON show: {base}/{id})
 *   - linkedResourceLabel (e.g. "questions" or "exams" — used in delete copy)
 */
document.addEventListener('DOMContentLoaded', () => {
    const config = window.categoryTreeConfig || {};
    const indexUrl = config.indexUrl || window.location.pathname;
    const detailsBaseUrl = (config.detailsBaseUrl || indexUrl).replace(/\/$/, '');
    const linkedResourceLabel = config.linkedResourceLabel || 'items';

    const persistentContainer = document.getElementById('category-tree-container');
    const searchInput = document.getElementById('category-search');
    const statusFilter = document.getElementById('status-filter');
    const expandAllButton = document.getElementById('expand-all-btn');
    const expandAllIcon = document.getElementById('expand-all-icon');
    const detailsModalEl = document.getElementById('categoryDetailsModal');

    if (!persistentContainer) return;

    const skeletonHTML = `
        <div class="animate-pulse space-y-4" aria-hidden="true">
            <div class="rounded-2xl border border-slate-200/60 bg-white dark:border-slate-800/80 dark:bg-slate-900/60 px-4 py-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800 shrink-0"></div>
                            <div class="h-5 w-40 rounded bg-slate-200 dark:bg-slate-800"></div>
                            <div class="h-5 w-16 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                        </div>
                        <div class="pl-12 space-y-2">
                            <div class="h-4 w-5/6 rounded bg-slate-100 dark:bg-slate-800/50"></div>
                            <div class="h-4 w-3/4 rounded bg-slate-100 dark:bg-slate-800/50"></div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                        <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                        <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                    </div>
                </div>
            </div>
            <div class="ml-8 rounded-2xl border border-slate-200/60 bg-white dark:border-slate-800/80 dark:bg-slate-900/60 px-4 py-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800 shrink-0"></div>
                            <div class="h-5 w-32 rounded bg-slate-200 dark:bg-slate-800"></div>
                        </div>
                        <div class="pl-12 space-y-2">
                            <div class="h-4 w-4/5 rounded bg-slate-100 dark:bg-slate-800/50"></div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                        <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                        <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200/60 bg-white dark:border-slate-800/80 dark:bg-slate-900/60 px-4 py-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800 shrink-0"></div>
                            <div class="h-5 w-48 rounded bg-slate-200 dark:bg-slate-800"></div>
                        </div>
                        <div class="pl-12 space-y-2">
                            <div class="h-4 w-2/3 rounded bg-slate-100 dark:bg-slate-800/50"></div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                        <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                        <div class="h-9 w-9 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                    </div>
                </div>
            </div>
        </div>
    `;

    const debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    };

    const setBranchState = (node, shouldOpen) => {
        const children = node.querySelector(':scope > .category-tree-children');
        const toggle = node.querySelector('.toggle-node-btn');
        if (!children || !toggle) return;

        children.classList.toggle('hidden', !shouldOpen);
        toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        toggle.title = shouldOpen ? 'Collapse category' : 'Expand category';
    };

    const updateExpandAllLabel = () => {
        const toggles = persistentContainer.querySelectorAll('.toggle-node-btn');
        if (toggles.length === 0) return;
        const allExpanded = Array.from(toggles).every(
            (t) => t.getAttribute('aria-expanded') === 'true'
        );
        const label = expandAllButton?.querySelector('span');
        if (label) label.textContent = allExpanded ? 'Collapse All' : 'Expand All';
        if (expandAllButton) expandAllButton.dataset.expanded = allExpanded ? 'true' : 'false';
        if (expandAllIcon) expandAllIcon.style.transform = allExpanded ? 'rotate(180deg)' : '';
    };

    const loadCategories = () => {
        const query = searchInput ? searchInput.value.trim() : '';
        const status = statusFilter ? statusFilter.value : '';

        persistentContainer.innerHTML = skeletonHTML;

        const url = new URL(indexUrl, window.location.origin);
        if (query) {
            url.searchParams.set('search', query);
        } else {
            url.searchParams.delete('search');
        }
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }

        window.history.replaceState(null, '', url.toString());

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
        })
            .then((response) => {
                if (!response.ok) throw new Error('Failed to load categories hierarchy.');
                return response.text();
            })
            .then((html) => {
                persistentContainer.innerHTML = html;

                const rootEl = document.getElementById('category-tree-root');
                if (rootEl) {
                    const nodes = Array.from(rootEl.children).filter((c) =>
                        c.classList.contains('category-tree-node')
                    );

                    if (query) {
                        rootEl.querySelectorAll('.category-tree-node').forEach((node) => {
                            setBranchState(node, true);
                        });
                    } else {
                        nodes.forEach((node) => {
                            setBranchState(node, false);
                        });
                    }
                }
                updateExpandAllLabel();
            })
            .catch((err) => {
                console.error('AJAX Load error:', err);
                persistentContainer.innerHTML = `
                <div class="rounded-3xl border border-dashed border-rose-300 bg-rose-50 px-6 py-12 text-center dark:border-rose-900/40 dark:bg-rose-900/10">
                    <h3 class="text-base font-semibold text-rose-900 dark:text-rose-400">Failed to retrieve categories</h3>
                    <p class="mt-1 text-sm text-rose-500 dark:text-rose-400">Please refresh or verify backend services.</p>
                </div>
            `;
            });
    };

    let detailsBackdrop = null;

    const openDetailsModal = (categoryId) => {
        if (!detailsModalEl) return;

        detailsBackdrop = document.createElement('div');
        detailsBackdrop.className = 'modal-backdrop fade';
        document.body.appendChild(detailsBackdrop);
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(() => {
            detailsBackdrop.classList.add('show');
            detailsModalEl.classList.add('show');
            detailsModalEl.removeAttribute('aria-hidden');
            detailsModalEl.setAttribute('aria-modal', 'true');
        });

        const skeleton = document.getElementById('modal-skeleton');
        const content = document.getElementById('modal-content');
        if (skeleton) skeleton.classList.remove('hidden');
        if (content) content.classList.add('hidden');

        const seoContent = document.getElementById('modal-seo-content');
        const seoToggleIcon = document.getElementById('modal-seo-toggle-icon');
        if (seoContent) seoContent.classList.add('hidden');
        if (seoToggleIcon) seoToggleIcon.classList.remove('rotate-180');

        fetch(`${detailsBaseUrl}/${categoryId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })
            .then((response) => {
                if (!response.ok) throw new Error('Could not fetch category profile.');
                return response.json();
            })
            .then((data) => {
                document.getElementById('categoryDetailsModalLabel').textContent = data.name;
                document.getElementById('modal-slug').textContent = data.slug || 'N/A';

                const statusEl = document.getElementById('modal-status-badge');
                if (statusEl) {
                    statusEl.innerHTML = `
                    <span class="qcat-status-badge qcat-status-badge--${data.status}">
                        ${data.status.charAt(0).toUpperCase() + data.status.slice(1)}
                    </span>
                `;
                }

                document.getElementById('modal-parent').textContent = data.parent
                    ? data.parent.name
                    : 'None';

                const aiFlagsEl = document.getElementById('modal-ai-flags');
                if (aiFlagsEl) {
                    aiFlagsEl.innerHTML = '';
                    if (data.ai_generated) {
                        aiFlagsEl.innerHTML += `<span class="qcat-ai-badge" title="Created via AI">AI Generated</span>`;
                    }
                    if (data.ai_improve) {
                        aiFlagsEl.innerHTML += `
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                            AI Improve Queue
                        </span>
                    `;
                    }
                    if (!data.ai_generated && !data.ai_improve) {
                        aiFlagsEl.innerHTML = `<span class="text-xs text-slate-400 font-medium">None</span>`;
                    }
                }

                document.getElementById('modal-description').textContent =
                    data.description || 'No description added yet.';

                const childrenEl = document.getElementById('modal-children');
                if (childrenEl) {
                    childrenEl.innerHTML = '';
                    if (data.children && data.children.length > 0) {
                        data.children.forEach((child) => {
                            const badge = document.createElement('span');
                            badge.className = 'child-category-badge';
                            badge.textContent = child.name;
                            childrenEl.appendChild(badge);
                        });
                    } else {
                        childrenEl.innerHTML =
                            '<span class="text-xs text-slate-400 font-medium">No sub-categories.</span>';
                    }
                }

                document.getElementById('modal-meta-title').textContent = data.meta_title || 'N/A';
                document.getElementById('modal-canonical-url').textContent =
                    data.canonical_url || 'N/A';
                document.getElementById('modal-meta-keywords').textContent =
                    data.meta_keywords || 'N/A';
                document.getElementById('modal-meta-description').textContent =
                    data.meta_description || 'N/A';
                document.getElementById('modal-og-title').textContent = data.og_title || 'N/A';
                document.getElementById('modal-og-description').textContent =
                    data.og_description || 'N/A';

                document.getElementById('modal-created-at').textContent =
                    data.formatted_created_at || 'N/A';
                document.getElementById('modal-updated-at').textContent =
                    data.formatted_updated_at || 'N/A';

                if (skeleton) skeleton.classList.add('hidden');
                if (content) content.classList.remove('hidden');
            })
            .catch((err) => {
                console.error(err);
                document.getElementById('categoryDetailsModalLabel').textContent =
                    'Error Loading Profile';
                if (skeleton) skeleton.classList.add('hidden');
                if (content) {
                    content.innerHTML = `
                    <div class="text-center py-8 text-rose-600 dark:text-rose-400 font-medium">
                        Unable to fetch category details. Please close and try again.
                    </div>
                `;
                    content.classList.remove('hidden');
                }
            });
    };

    const closeDetailsModal = () => {
        if (!detailsModalEl) return;
        detailsModalEl.classList.remove('show');
        detailsModalEl.setAttribute('aria-hidden', 'true');
        detailsModalEl.removeAttribute('aria-modal');
        if (detailsBackdrop) {
            detailsBackdrop.classList.remove('show');
            window.setTimeout(() => {
                detailsBackdrop?.remove();
                detailsBackdrop = null;
            }, 150);
        }
        document.body.style.overflow = '';
    };

    const openDeleteConfirm = (categoryId, categoryName) => {
        if (!window.Swal) return;

        const form = document.getElementById(`delete-form-${categoryId}`);
        if (!form) return;

        Swal.fire({
            showClass: { popup: 'swal-cat-show' },
            hideClass: { popup: 'swal-cat-hide' },
            buttonsStyling: false,
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,

            title: 'Delete Category',
            html: `
                <div class="swal-cat-body">
                    <div class="swal-cat-icon-wrap">
                        <span class="swal-cat-icon-ring">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </span>
                    </div>
                    <p class="swal-cat-message">You are about to delete</p>
                    <div class="swal-cat-name-chip">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <span>${categoryName}</span>
                    </div>
                    <p class="swal-cat-warning">This will soft-delete the category. Sub-categories and ${linkedResourceLabel} linked to this entry will also be affected.</p>
                </div>
            `,

            confirmButtonText: `
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:6px">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Yes, Delete
            `,
            cancelButtonText: `
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancel
            `,

            customClass: {
                popup: 'swal-cat-popup',
                title: 'swal-cat-title',
                htmlContainer: 'swal-cat-html',
                confirmButton: 'swal-cat-confirm-btn',
                cancelButton: 'swal-cat-cancel-btn',
                actions: 'swal-cat-actions',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    };

    persistentContainer.addEventListener('click', (event) => {
        const toggle = event.target.closest('.toggle-node-btn');
        const viewButton = event.target.closest('.view-node-btn');
        const deleteButton = event.target.closest('.delete-node-btn');

        if (toggle) {
            const node = toggle.closest('.category-tree-node');
            const childContainer = node?.querySelector(':scope > .category-tree-children');
            if (node && childContainer) {
                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                setBranchState(node, !isExpanded);
                updateExpandAllLabel();
            }
        }

        if (viewButton) {
            openDetailsModal(viewButton.dataset.categoryId);
        }

        if (deleteButton) {
            openDeleteConfirm(
                deleteButton.dataset.categoryId,
                deleteButton.dataset.categoryName || 'this category'
            );
        }
    });

    if (searchInput) {
        searchInput.addEventListener(
            'input',
            debounce(() => {
                loadCategories();
            }, 300)
        );
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            loadCategories();
        });
    }

    expandAllButton?.addEventListener('click', () => {
        const shouldExpand = expandAllButton.dataset.expanded !== 'true';
        persistentContainer.querySelectorAll('.category-tree-node').forEach((node) => {
            setBranchState(node, shouldExpand);
        });
        updateExpandAllLabel();
    });

    if (detailsModalEl) {
        detailsModalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach((btn) => {
            btn.addEventListener('click', closeDetailsModal);
        });
        detailsModalEl.addEventListener('click', (e) => {
            if (e.target === detailsModalEl) closeDetailsModal();
        });

        const seoToggle = document.getElementById('modal-seo-toggle');
        const seoContent = document.getElementById('modal-seo-content');
        const seoToggleIcon = document.getElementById('modal-seo-toggle-icon');

        if (seoToggle && seoContent && seoToggleIcon) {
            seoToggle.addEventListener('click', () => {
                const isHidden = seoContent.classList.contains('hidden');
                if (isHidden) {
                    seoContent.classList.remove('hidden');
                    seoToggleIcon.classList.add('rotate-180');
                } else {
                    seoContent.classList.add('hidden');
                    seoToggleIcon.classList.remove('rotate-180');
                }
            });
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && detailsModalEl?.classList.contains('show')) {
            closeDetailsModal();
        }
    });

    loadCategories();
});
