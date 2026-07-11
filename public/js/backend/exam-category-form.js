/**
 * exam-category-form.js
 *
 * Create / Edit page logic for Exam Categories (mirrors question-category-form.js).
 */
document.addEventListener('DOMContentLoaded', () => {

    // SEO and AI toggle behaviors are managed by seo-manager.js

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

    const clearError = (field) => {
        field.classList.remove('is-invalid');
        const errorEl = field.parentElement?.querySelector('.qcat-field-error');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.remove('is-visible');
        }
    };

    document.querySelectorAll('.panel-input').forEach((field) => {
        field.addEventListener('input', () => clearError(field));
        field.addEventListener('change', () => clearError(field));
    });

    const validateCanonical = (form, fieldId) => {
        const canonicalField = document.getElementById(fieldId);
        if (canonicalField && canonicalField.value.trim()) {
            try {
                new URL(canonicalField.value.trim());
            } catch {
                showError(canonicalField, 'Please enter a valid URL (e.g. https://example.com).');
                return false;
            }
        }
        return true;
    };

    const form = document.getElementById('category-tree-form');
    if (form) {
        form.addEventListener('submit', (e) => {
            let valid = true;

            const statusField = document.getElementById('qcat-status-select')
                             || document.getElementById('edit-status-select');
            const canonicalId = document.getElementById('edit-canonical')
                ? 'edit-canonical'
                : 'meta-canonical';

            if (statusField && !statusField.value) {
                showError(statusField, 'Please select a status.');
                valid = false;
            }

            if (!validateCanonical(form, canonicalId)) {
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                form.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        form.addEventListener('submit', () => {
            const mapInput = document.getElementById('parent-map-input');
            if (!mapInput) return;

            const map = {};
            document.querySelectorAll('.category-node').forEach((node) => {
                const nodeId = node.dataset.nodeId;
                const parentNode = node.closest('.category-node__children')
                    ?.closest('.category-node');
                const parentNodeId = parentNode?.dataset.nodeId || null;

                if (nodeId && parentNodeId) {
                    map[nodeId] = parentNodeId;
                }
            });

            mapInput.value = JSON.stringify(map);
        }, { capture: true });
    }

    // Validation errors are shown by the global EmsToast flash partial.
});
