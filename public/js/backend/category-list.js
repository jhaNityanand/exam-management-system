/**
 * category-list.js
 *
 * Handles the interactive behaviour of the Question Category index (tree list) page:
 *   - Tree expand / collapse
 *   - Client-side search highlight (live JS filter on the already-rendered tree)
 *   - SweetAlert2 delete confirmation → real HTTP DELETE (via hidden form)
 *   - Description modal
 *   - Expand-All / Collapse-All toggle
 */
document.addEventListener('DOMContentLoaded', () => {

    /* ------------------------------------------------------------------ */
    /*  Element refs                                                        */
    /* ------------------------------------------------------------------ */
    const root            = document.getElementById('category-tree-root');
    const searchInput     = document.getElementById('category-search');
    const expandAllButton = document.getElementById('expand-all-btn');
    const expandAllIcon   = document.getElementById('expand-all-icon');
    const emptyState      = document.getElementById('category-empty-state');
    const descModalEl     = document.getElementById('descModal');
    const descModalTitle  = document.getElementById('descModalLabel');
    const descModalContent= document.getElementById('descModalContent');

    if (!root) return;

    const topLevelNodes = Array.from(root.children).filter(
        (c) => c.classList.contains('category-tree-node')
    );

    /* ------------------------------------------------------------------ */
    /*  Tree expand / collapse                                              */
    /* ------------------------------------------------------------------ */
    const setBranchState = (node, shouldOpen) => {
        const children = node.querySelector(':scope > .category-tree-children');
        const toggle   = node.querySelector('.toggle-node-btn');
        if (!children || !toggle) return;

        children.classList.toggle('hidden', !shouldOpen);
        toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        toggle.title = shouldOpen ? 'Collapse category' : 'Expand category';
    };

    const updateExpandAllLabel = () => {
        const toggles     = root.querySelectorAll('.toggle-node-btn');
        const allExpanded = Array.from(toggles).every(
            (t) => t.getAttribute('aria-expanded') === 'true'
        );
        const label = expandAllButton?.querySelector('span');
        if (label) label.textContent = allExpanded ? 'Collapse All' : 'Expand All';
        if (expandAllButton) expandAllButton.dataset.expanded = allExpanded ? 'true' : 'false';
        if (expandAllIcon) expandAllIcon.style.transform = allExpanded ? 'rotate(180deg)' : '';
    };

    /* ------------------------------------------------------------------ */
    /*  Client-side search filter                                           */
    /* ------------------------------------------------------------------ */
    const filterNode = (node, query) => {
        const haystack   = `${node.dataset.nodeName || ''} ${node.dataset.nodeDescription || ''}`.trim();
        const directMatch = !query || haystack.includes(query);
        const children   = Array.from(
            node.querySelectorAll(':scope > .category-tree-children > .category-tree-node')
        );

        let hasChildMatch = false;
        children.forEach((child) => {
            if (filterNode(child, query)) hasChildMatch = true;
        });

        const visible = directMatch || hasChildMatch;
        node.classList.toggle('is-hidden-by-search', !visible);
        node.classList.toggle('matching-node',    !!query && directMatch);
        node.classList.toggle('has-search-match', !!query && !directMatch && hasChildMatch);

        const childContainer = node.querySelector(':scope > .category-tree-children');
        if (childContainer) {
            if (!!query && hasChildMatch) setBranchState(node, true);
            else if (!query)             setBranchState(node, false);
        }

        return visible;
    };

    /* ------------------------------------------------------------------ */
    /*  Description modal (pure JS — no Bootstrap CSS dependency)          */
    /* ------------------------------------------------------------------ */
    let descBackdrop = null;

    const openDescModal = (name, desc) => {
        if (!descModalEl || !descModalTitle || !descModalContent) return;
        descModalTitle.textContent   = name;
        descModalContent.textContent = desc;

        descBackdrop = document.createElement('div');
        descBackdrop.className = 'modal-backdrop fade';
        document.body.appendChild(descBackdrop);
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(() => {
            descBackdrop.classList.add('show');
            descModalEl.classList.add('show');
            descModalEl.removeAttribute('aria-hidden');
            descModalEl.setAttribute('aria-modal', 'true');
        });
    };

    const closeDescModal = () => {
        if (!descModalEl) return;
        descModalEl.classList.remove('show');
        descModalEl.setAttribute('aria-hidden', 'true');
        descModalEl.removeAttribute('aria-modal');
        if (descBackdrop) {
            descBackdrop.classList.remove('show');
            window.setTimeout(() => { descBackdrop?.remove(); descBackdrop = null; }, 150);
        }
        document.body.style.overflow = '';
    };

    /* ------------------------------------------------------------------ */
    /*  Delete confirmation → real HTTP DELETE                              */
    /* ------------------------------------------------------------------ */
    const openDeleteConfirm = (categoryId, categoryName) => {
        if (!window.Swal) return;

        const form = document.getElementById(`delete-form-${categoryId}`);
        if (!form) return;

        Swal.fire({
            showClass:  { popup: 'swal-cat-show' },
            hideClass:  { popup: 'swal-cat-hide' },
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
                    <p class="swal-cat-message">You are about to permanently delete</p>
                    <div class="swal-cat-name-chip">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <span>${categoryName}</span>
                    </div>
                    <p class="swal-cat-warning">This action is <strong>irreversible</strong>. All sub-categories and questions linked to this entry will also be affected.</p>
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
                popup:         'swal-cat-popup',
                title:         'swal-cat-title',
                htmlContainer: 'swal-cat-html',
                confirmButton: 'swal-cat-confirm-btn',
                cancelButton:  'swal-cat-cancel-btn',
                actions:       'swal-cat-actions',
            },

        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the real hidden DELETE form
                form.submit();
            }
        });
    };

    /* ------------------------------------------------------------------ */
    /*  Event delegation on tree root                                       */
    /* ------------------------------------------------------------------ */
    root.addEventListener('click', (event) => {
        const toggle       = event.target.closest('.toggle-node-btn');
        const viewButton   = event.target.closest('.view-desc-btn');
        const deleteButton = event.target.closest('.delete-node-btn');

        if (toggle) {
            const node          = toggle.closest('.category-tree-node');
            const childContainer= node?.querySelector(':scope > .category-tree-children');
            if (node && childContainer) {
                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                setBranchState(node, !isExpanded);
                updateExpandAllLabel();
            }
        }

        if (viewButton) {
            openDescModal(
                viewButton.dataset.name || 'Category',
                viewButton.dataset.desc || ''
            );
        }

        if (deleteButton) {
            openDeleteConfirm(
                deleteButton.dataset.categoryId,
                deleteButton.dataset.categoryName || 'this category'
            );
        }
    });

    /* ------------------------------------------------------------------ */
    /*  Live search                                                         */
    /* ------------------------------------------------------------------ */
    searchInput?.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        let visible = 0;
        topLevelNodes.forEach((node) => { if (filterNode(node, query)) visible++; });
        emptyState?.classList.toggle('hidden', visible > 0);
        updateExpandAllLabel();
    });

    /* ------------------------------------------------------------------ */
    /*  Expand / Collapse All                                               */
    /* ------------------------------------------------------------------ */
    expandAllButton?.addEventListener('click', () => {
        const shouldExpand = expandAllButton.dataset.expanded !== 'true';
        root.querySelectorAll('.category-tree-node').forEach((node) => {
            if (!node.classList.contains('is-hidden-by-search')) {
                setBranchState(node, shouldExpand);
            }
        });
        updateExpandAllLabel();
    });

    /* ------------------------------------------------------------------ */
    /*  Modal close wiring                                                  */
    /* ------------------------------------------------------------------ */
    descModalEl?.querySelectorAll('[data-bs-dismiss="modal"]').forEach((btn) => {
        btn.addEventListener('click', closeDescModal);
    });
    descModalEl?.addEventListener('click', (e) => {
        if (e.target === descModalEl) closeDescModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && descModalEl?.classList.contains('show')) closeDescModal();
    });

    /* ------------------------------------------------------------------ */
    /*  Initial state — all branches collapsed                              */
    /* ------------------------------------------------------------------ */
    topLevelNodes.forEach((node) => setBranchState(node, false));
    updateExpandAllLabel();
});
