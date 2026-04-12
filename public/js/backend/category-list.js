document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('category-tree-root');
    const searchInput = document.getElementById('category-search');
    const expandAllButton = document.getElementById('expand-all-btn');
    const expandAllIcon = document.getElementById('expand-all-icon');
    const emptyState = document.getElementById('category-empty-state');
    const modal = document.getElementById('desc-modal');
    const modalTitle = document.getElementById('desc-modal-title');
    const modalContent = document.getElementById('desc-modal-content');
    const deleteModal = document.getElementById('delete-confirm-modal');
    const deleteConfirmText = document.getElementById('delete-confirm-text');
    const deleteConfirmButton = document.getElementById('confirm-delete-btn');
    const toast = document.getElementById('category-toast');
    const toastText = document.getElementById('category-toast-text');
    let nodePendingDelete = null;
    let toastTimer = null;

    if (!root || !searchInput || !expandAllButton) {
        return;
    }

    const topLevelNodes = Array.from(root.children).filter((child) => child.classList.contains('category-tree-node'));

    const closeModal = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    };

    const closeDeleteModal = () => {
        deleteModal?.classList.add('hidden');
        deleteModal?.classList.remove('flex');
        nodePendingDelete = null;
    };

    const openModal = (name, desc) => {
        if (!modal || !modalTitle || !modalContent) {
            return;
        }

        modalTitle.textContent = name;
        modalContent.textContent = desc;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const showToast = (message) => {
        if (!toast || !toastText) {
            return;
        }

        toastText.textContent = message;
        toast.classList.remove('hidden');
        requestAnimationFrame(() => toast.classList.add('is-visible'));

        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.classList.add('hidden'), 200);
        }, 2200);
    };

    const openDeleteModal = (node, categoryName) => {
        if (!deleteModal || !deleteConfirmText) {
            return;
        }

        nodePendingDelete = node;
        deleteConfirmText.textContent = `Are you sure you want to delete "${categoryName}"?`;
        deleteModal.classList.remove('hidden');
        deleteModal.classList.add('flex');
    };

    const setBranchState = (node, shouldOpen) => {
        const children = node.querySelector(':scope > .category-tree-children');
        const toggle = node.querySelector('.toggle-node-btn');

        if (!children || !toggle) {
            return;
        }

        children.classList.toggle('hidden', !shouldOpen);
        toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        toggle.title = shouldOpen ? 'Collapse category' : 'Expand category';
    };

    const updateExpandAllLabel = () => {
        const branchToggles = root.querySelectorAll('.toggle-node-btn');
        const allExpanded = Array.from(branchToggles).every((toggle) => toggle.getAttribute('aria-expanded') === 'true');
        const label = expandAllButton.querySelector('span');

        if (label) {
            label.textContent = allExpanded ? 'Collapse all' : 'Expand all';
        }

        expandAllButton.dataset.expanded = allExpanded ? 'true' : 'false';
        if (expandAllIcon) {
            expandAllIcon.style.transform = allExpanded ? 'rotate(180deg)' : 'rotate(0deg)';
        }
    };

    const filterNode = (node, query) => {
        const haystack = `${node.dataset.nodeName || ''} ${node.dataset.nodeDescription || ''}`.trim();
        const directMatch = !query || haystack.includes(query);
        const children = Array.from(node.querySelectorAll(':scope > .category-tree-children > .category-tree-node'));

        let hasChildMatch = false;
        children.forEach((child) => {
            if (filterNode(child, query)) {
                hasChildMatch = true;
            }
        });

        const visible = directMatch || hasChildMatch;
        node.classList.toggle('is-hidden-by-search', !visible);
        node.classList.toggle('matching-node', !!query && directMatch);
        node.classList.toggle('has-search-match', !!query && !directMatch && hasChildMatch);

        const childContainer = node.querySelector(':scope > .category-tree-children');
        if (childContainer) {
            const shouldOpen = !!query && hasChildMatch;
            if (shouldOpen) {
                setBranchState(node, true);
            } else if (!query) {
                setBranchState(node, false);
            }
        }

        return visible;
    };

    root.addEventListener('click', (event) => {
        const toggle = event.target.closest('.toggle-node-btn');
        const viewButton = event.target.closest('.view-desc-btn');
        const deleteButton = event.target.closest('.delete-node-btn');

        if (toggle) {
            const node = toggle.closest('.category-tree-node');
            const childContainer = node?.querySelector(':scope > .category-tree-children');
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

            if (node && childContainer) {
                setBranchState(node, !isExpanded);
                updateExpandAllLabel();
            }
        }

        if (viewButton) {
            openModal(viewButton.dataset.name || 'Category', viewButton.dataset.desc || '');
        }

        if (deleteButton) {
            openDeleteModal(deleteButton.closest('.category-tree-node'), deleteButton.dataset.categoryName || 'this category');
        }
    });

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        topLevelNodes.forEach((node) => {
            if (filterNode(node, query)) {
                visibleCount += 1;
            }
        });

        emptyState?.classList.toggle('hidden', visibleCount > 0);
        updateExpandAllLabel();
    });

    expandAllButton.addEventListener('click', () => {
        const shouldExpand = expandAllButton.dataset.expanded !== 'true';

        root.querySelectorAll('.category-tree-node').forEach((node) => {
            if (node.classList.contains('is-hidden-by-search')) {
                return;
            }

            setBranchState(node, shouldExpand);
        });

        updateExpandAllLabel();
    });

    document.getElementById('close-desc-modal')?.addEventListener('click', closeModal);
    document.getElementById('close-desc-modal-btn')?.addEventListener('click', closeModal);
    document.getElementById('cancel-delete-btn')?.addEventListener('click', closeDeleteModal);
    deleteConfirmButton?.addEventListener('click', () => {
        if (!nodePendingDelete) {
            return;
        }

        const parentList = nodePendingDelete.parentElement;
        nodePendingDelete.remove();
        closeDeleteModal();
        showToast('Category deleted successfully.');

        if (parentList?.id === 'category-tree-root' && !parentList.children.length) {
            emptyState?.classList.remove('hidden');
        }
    });

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    deleteModal?.addEventListener('click', (event) => {
        if (event.target === deleteModal) {
            closeDeleteModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
            closeDeleteModal();
        }
    });

    topLevelNodes.forEach((node) => setBranchState(node, false));
    updateExpandAllLabel();
});
