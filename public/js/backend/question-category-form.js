/**
 * question-category-form.js
 *
 * Handles the Create and Edit page logic for the Question Category module:
 *   - Metadata accordion expand / collapse
 *   - AI toggle visibility logic:
 *       "Create via AI" ON  → hide builder canvas + metadata section + "Improve via AI"
 *       "Create via AI" OFF → show all of the above
 *   - Character counter for meta fields
 *   - Frontend validation (no HTML `required` used)
 *   - Parent-map JSON serialization for the tree builder form
 *   - Server-side validation error display (from __serverErrors global)
 */
document.addEventListener('DOMContentLoaded', () => {

    // SEO and AI toggle behaviors are managed by seo-manager.js

    /* ------------------------------------------------------------------ */
    /*  Status Toggle Switch                                                */
    /* ------------------------------------------------------------------ */
    const statusCheckbox = document.getElementById('qcat-status-toggle')
                        || document.getElementById('edit-status-toggle');
    const statusLabel    = document.getElementById('qcat-status-indicator-label')
                        || document.getElementById('edit-status-indicator-label');

    const updateStatusLabel = () => {
        if (!statusCheckbox || !statusLabel) return;
        if (statusCheckbox.checked) {
            statusLabel.textContent = 'Active';
            statusLabel.className = 'qcat-status-text-indicator status-active';
        } else {
            statusLabel.textContent = 'Inactive';
            statusLabel.className = 'qcat-status-text-indicator status-inactive';
        }
    };

    if (statusCheckbox && statusLabel) {
        statusCheckbox.addEventListener('change', updateStatusLabel);
        updateStatusLabel(); // Run on load
    }

    // Character counters are managed by seo-manager.js

    /* ------------------------------------------------------------------ */
    /*  Frontend validation (no HTML `required`)                            */
    /* ------------------------------------------------------------------ */

    /**
     * Show an error under a field.
     * @param {HTMLElement} field
     * @param {string}      message
     */
    const showError = (field, message) => {
        field.classList.add('is-invalid');
        let errorEl = field.parentElement?.querySelector('.qcat-field-error');
        if (!errorEl) {
            errorEl = document.createElement('p');
            errorEl.className = 'qcat-field-error';
            field.after(errorEl);
        }
        errorEl.textContent = message;
        errorEl.classList.add('is-visible');
    };

    /**
     * Clear an error on a field.
     * @param {HTMLElement} field
     */
    const clearError = (field) => {
        field.classList.remove('is-invalid');
        const errorEl = field.parentElement?.querySelector('.qcat-field-error');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.remove('is-visible');
        }
    };

    // Clear errors on input
    document.querySelectorAll('.panel-input').forEach((field) => {
        field.addEventListener('input', () => clearError(field));
        field.addEventListener('change', () => clearError(field));
    });

    // Validate the edit form (single-category)
    const editForm = document.getElementById('qcat-edit-form');
    if (editForm) {
        editForm.addEventListener('submit', (e) => {
            let valid = true;

            const nameField   = document.getElementById('edit-name');
            const statusField = document.getElementById('edit-status');

            if (nameField && !nameField.value.trim()) {
                showError(nameField, 'Please enter a category name.');
                valid = false;
            }
            if (statusField && !statusField.value) {
                showError(statusField, 'Please select a status.');
                valid = false;
            }

            // Basic URL validation for canonical_url
            const canonicalField = document.getElementById('edit-canonical');
            if (canonicalField && canonicalField.value.trim()) {
                try {
                    new URL(canonicalField.value.trim());
                } catch {
                    showError(canonicalField, 'Please enter a valid URL (e.g. https://example.com).');
                    valid = false;
                }
            }

            if (!valid) {
                e.preventDefault();
                // Scroll to first error
                const firstInvalid = editForm.querySelector('.is-invalid');
                firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    // Validate the create form (tree builder)
    const createForm = document.getElementById('category-tree-form');
    if (createForm) {
        createForm.addEventListener('submit', (e) => {
            // Tree builder validation is handled by category-manager.js
            // Here we handle the status field and URL field
            let valid = true;

            const statusField  = document.getElementById('qcat-status');
            const canonicalFld = document.getElementById('meta-canonical');

            if (statusField && !statusField.value) {
                showError(statusField, 'Please select a status.');
                valid = false;
            }
            if (canonicalFld && canonicalFld.value.trim()) {
                try {
                    new URL(canonicalFld.value.trim());
                } catch {
                    showError(canonicalFld, 'Please enter a valid URL (e.g. https://example.com).');
                    valid = false;
                }
            }

            if (!valid) e.preventDefault();
        });

        // ── Parent map serialization ──────────────────────────────────────────
        // category-manager.js builds a tree in the DOM. Before submitting, we
        // traverse the tree to compute the parent relationship map and write it
        // to the hidden _parent_map input so the backend can reconstruct hierarchy.
        createForm.addEventListener('submit', () => {
            const mapInput = document.getElementById('parent-map-input');
            if (!mapInput) return;

            const map = {};
            const allNodes = document.querySelectorAll('.category-node');

            allNodes.forEach((node) => {
                const nodeId       = node.dataset.nodeId;
                const parentNode   = node.closest('.category-node__children')
                                       ?.closest('.category-node');
                const parentNodeId = parentNode?.dataset.nodeId || null;

                if (nodeId && parentNodeId) {
                    map[nodeId] = parentNodeId;
                }
            });

            mapInput.value = JSON.stringify(map);
        }, { capture: true }); // capture: true — runs before validation listener
    }

    /* ------------------------------------------------------------------ */
    /*  Server-side validation errors                                       */
    /* ------------------------------------------------------------------ */
    if (window.__serverErrors && window.__serverErrors.length && window.Swal) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            iconColor: '#f43f5e',
            title: window.__serverErrors[0],
            showConfirmButton: false,
            timer: 4500,
            timerProgressBar: true,
            customClass: {
                popup: 'swal-cat-toast-popup',
                title: 'swal-cat-toast-title',
                timerProgressBar: 'swal-cat-toast-bar',
            },
        });
    }

});
