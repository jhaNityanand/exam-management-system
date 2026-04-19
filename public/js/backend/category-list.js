document.addEventListener('DOMContentLoaded', () => {
    /* ------------------------------------------------------------------ */
    /*  Element refs                                                         */
    /* ------------------------------------------------------------------ */
    const root           = document.getElementById('category-tree-root');
    const searchInput    = document.getElementById('category-search');
    const expandAllButton= document.getElementById('expand-all-btn');
    const expandAllIcon  = document.getElementById('expand-all-icon');
    const emptyState     = document.getElementById('category-empty-state');
    const toast          = document.getElementById('category-toast');
    const toastText      = document.getElementById('category-toast-text');

    /* Bootstrap modal instance for description */
    const descModalEl     = document.getElementById('descModal');
    const descModalTitle  = document.getElementById('descModalLabel');
    const descModalContent= document.getElementById('descModalContent');

    let nodePendingDelete = null;
    let toastTimer        = null;

    if (!root || !searchInput || !expandAllButton) return;

    const topLevelNodes = Array.from(root.children).filter(
        (child) => child.classList.contains('category-tree-node')
    );

    /* ------------------------------------------------------------------ */
    /*  Toast helper                                                         */
    /* ------------------------------------------------------------------ */
    const showToast = (message) => {
        if (!toast || !toastText) return;

        toastText.textContent = message;
        toast.classList.remove('hidden');
        requestAnimationFrame(() => toast.classList.add('is-visible'));

        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.classList.add('hidden'), 200);
        }, 2800);
    };

    /* ------------------------------------------------------------------ */
    /*  Description Modal  (pure JS — no Bootstrap CSS dependency)          */
    /* ------------------------------------------------------------------ */
    let descBackdrop = null;

    const openDescModal = (name, desc) => {
        if (!descModalEl || !descModalTitle || !descModalContent) return;

        descModalTitle.textContent   = name;
        descModalContent.textContent = desc;

        /* Create backdrop */
        descBackdrop = document.createElement('div');
        descBackdrop.className = 'modal-backdrop fade';
        document.body.appendChild(descBackdrop);
        document.body.style.overflow = 'hidden';

        /* Trigger animations on next frame */
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
            /* Remove after transition */
            window.setTimeout(() => {
                descBackdrop?.remove();
                descBackdrop = null;
            }, 150);
        }
        document.body.style.overflow = '';
    };

    /* ------------------------------------------------------------------ */
    /*  Delete Confirmation  (SweetAlert2)                                  */
    /* ------------------------------------------------------------------ */
    const openDeleteConfirm = (node, categoryName) => {
        if (!window.Swal) return;

        nodePendingDelete = node;

        Swal.fire({
            /* ── layout ── */
            showClass:  { popup: 'swal-cat-show'  },
            hideClass:  { popup: 'swal-cat-hide'  },
            buttonsStyling: false,
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,

            /* ── content ── */
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
                    <p class="swal-cat-warning">This action is <strong>irreversible</strong>. All sub-categories linked to this entry will also be affected.</p>
                </div>
            `,

            /* ── buttons ── */
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

            /* ── custom classes ── */
            customClass: {
                popup:          'swal-cat-popup',
                title:          'swal-cat-title',
                htmlContainer:  'swal-cat-html',
                confirmButton:  'swal-cat-confirm-btn',
                cancelButton:   'swal-cat-cancel-btn',
                actions:        'swal-cat-actions',
            },

            /* ── entrance animation hook ── */
            didOpen: (popup) => {
                popup.style.transform = 'scale(1)';
                popup.style.opacity   = '1';
            },

        }).then((result) => {
            if (result.isConfirmed && nodePendingDelete) {
                const parentList = nodePendingDelete.parentElement;
                nodePendingDelete.remove();
                nodePendingDelete = null;

                if (parentList?.id === 'category-tree-root' && !parentList.children.length) {
                    emptyState?.classList.remove('hidden');
                }

                /* ── Success toast (SweetAlert2) ── */
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    iconColor: '#10b981',
                    title: 'Category deleted successfully',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    showCloseButton: true,
                    customClass: {
                        popup:        'swal-cat-toast-popup',
                        title:        'swal-cat-toast-title',
                        timerProgressBar: 'swal-cat-toast-bar',
                    },
                });
            } else {
                nodePendingDelete = null;
            }
        });
    };

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
        const branchToggles = root.querySelectorAll('.toggle-node-btn');
        const allExpanded   = Array.from(branchToggles).every(
            (toggle) => toggle.getAttribute('aria-expanded') === 'true'
        );
        const label = expandAllButton.querySelector('span');

        if (label) {
            label.textContent = allExpanded ? 'Collapse all' : 'Expand all';
        }

        expandAllButton.dataset.expanded = allExpanded ? 'true' : 'false';
        if (expandAllIcon) {
            expandAllIcon.style.transform = allExpanded ? 'rotate(180deg)' : 'rotate(0deg)';
        }
    };

    /* ------------------------------------------------------------------ */
    /*  Search / filter                                                     */
    /* ------------------------------------------------------------------ */
    const filterNode = (node, query) => {
        const haystack    = `${node.dataset.nodeName || ''} ${node.dataset.nodeDescription || ''}`.trim();
        const directMatch = !query || haystack.includes(query);
        const children    = Array.from(
            node.querySelectorAll(':scope > .category-tree-children > .category-tree-node')
        );

        let hasChildMatch = false;
        children.forEach((child) => {
            if (filterNode(child, query)) hasChildMatch = true;
        });

        const visible = directMatch || hasChildMatch;
        node.classList.toggle('is-hidden-by-search', !visible);
        node.classList.toggle('matching-node',   !!query && directMatch);
        node.classList.toggle('has-search-match',!!query && !directMatch && hasChildMatch);

        const childContainer = node.querySelector(':scope > .category-tree-children');
        if (childContainer) {
            const shouldOpen = !!query && hasChildMatch;
            if (shouldOpen)      setBranchState(node, true);
            else if (!query)     setBranchState(node, false);
        }

        return visible;
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
            const isExpanded    = toggle.getAttribute('aria-expanded') === 'true';

            if (node && childContainer) {
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
                deleteButton.closest('.category-tree-node'),
                deleteButton.dataset.categoryName || 'this category'
            );
        }
    });

    /* ------------------------------------------------------------------ */
    /*  Search input                                                        */
    /* ------------------------------------------------------------------ */
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        topLevelNodes.forEach((node) => {
            if (filterNode(node, query)) visibleCount += 1;
        });

        emptyState?.classList.toggle('hidden', visibleCount > 0);
        updateExpandAllLabel();
    });

    /* ------------------------------------------------------------------ */
    /*  Expand / Collapse all                                               */
    /* ------------------------------------------------------------------ */
    expandAllButton.addEventListener('click', () => {
        const shouldExpand = expandAllButton.dataset.expanded !== 'true';

        root.querySelectorAll('.category-tree-node').forEach((node) => {
            if (node.classList.contains('is-hidden-by-search')) return;
            setBranchState(node, shouldExpand);
        });

        updateExpandAllLabel();
    });

    /* ------------------------------------------------------------------ */
    /*  Modal close wiring                                                   */
    /* ------------------------------------------------------------------ */
    /* data-bs-dismiss buttons inside the modal */
    descModalEl?.querySelectorAll('[data-bs-dismiss="modal"]').forEach((btn) => {
        btn.addEventListener('click', closeDescModal);
    });

    /* Click on the dark overlay (outside the card) closes modal */
    descModalEl?.addEventListener('click', (e) => {
        if (e.target === descModalEl) closeDescModal();
    });

    /* Escape key */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && descModalEl?.classList.contains('show')) {
            closeDescModal();
        }
    });

    /* ------------------------------------------------------------------ */
    /*  Initial state                                                       */
    /* ------------------------------------------------------------------ */
    topLevelNodes.forEach((node) => setBranchState(node, false));
    updateExpandAllLabel();
});
