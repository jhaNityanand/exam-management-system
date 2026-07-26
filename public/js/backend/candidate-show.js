/**
 * Candidate details page — modals, delete confirm, document preview.
 */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.hidden = false;
            document.body.classList.add('cand-modal-open');
        }
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        if (!document.querySelector('.cand-modal:not([hidden])')) {
            document.body.classList.remove('cand-modal-open');
        }
    }

    function fillText(id, value, fallback) {
        const el = document.getElementById(id);
        if (!el) return;
        const text = (value || '').trim();
        el.textContent = text || (fallback || '—');
    }

    function openDocPreview(source) {
        const item = source.closest('[data-preview-url]') || source;
        const url = item.getAttribute('data-preview-url');
        if (!url) return;

        const label = item.getAttribute('data-preview-label') || 'Preview';
        const type = item.getAttribute('data-preview-type') || '';
        const status = item.getAttribute('data-preview-status') || '';
        const exam = item.getAttribute('data-preview-exam') || '';
        const attempt = item.getAttribute('data-preview-attempt') || '';
        const captured = item.getAttribute('data-preview-captured') || '';
        const download = item.getAttribute('data-preview-download') || url;

        const img = document.getElementById('doc-preview-image');
        const title = document.getElementById('doc-preview-title');
        const downloadBtn = document.getElementById('doc-preview-download');

        if (img) {
            img.src = url;
            img.alt = label;
        }
        if (title) title.textContent = label;

        fillText('doc-preview-type', type);
        fillText('doc-preview-status', status);
        fillText('doc-preview-exam', exam, 'Not linked');
        fillText('doc-preview-attempt', attempt, '—');
        fillText('doc-preview-captured', captured, '—');

        if (downloadBtn) {
            downloadBtn.href = download;
            downloadBtn.hidden = !download;
        }

        openModal('doc-preview-modal');
    }

    onReady(function () {
        document.querySelectorAll('[data-modal-open]').forEach((btn) => {
            btn.addEventListener('click', () => openModal(btn.getAttribute('data-modal-open')));
        });

        document.querySelectorAll('.cand-modal').forEach((modal) => {
            modal.querySelectorAll('[data-modal-close]').forEach((el) => {
                el.addEventListener('click', () => closeModal(modal));
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('.cand-modal:not([hidden])').forEach((modal) => closeModal(modal));
        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.js-preview-doc');
            if (!btn) return;
            e.preventDefault();
            openDocPreview(btn);
        });

        const toggleBtn = document.getElementById('btn-toggle-status');
        const toggleForm = document.getElementById('toggle-status-form');
        if (toggleBtn && toggleForm) {
            toggleBtn.addEventListener('click', function () {
                const isDeactivate = toggleBtn.getAttribute('data-action') === 'deactivate';
                const name = toggleBtn.getAttribute('data-name') || 'this candidate';
                const title = isDeactivate ? 'Deactivate candidate?' : 'Activate candidate?';
                const text = isDeactivate
                    ? `${name} will no longer be able to sign in or take exams until reactivated.`
                    : `${name} will be able to sign in and take exams again.`;
                const confirmText = isDeactivate ? 'Yes, deactivate' : 'Yes, activate';
                const confirmColor = isDeactivate ? '#e11d48' : '#059669';

                if (window.Swal) {
                    Swal.fire({
                        title,
                        text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: confirmColor,
                        cancelButtonColor: '#64748b',
                        confirmButtonText: confirmText,
                    }).then((result) => {
                        if (result.isConfirmed) toggleForm.submit();
                    });
                } else if (confirm(title)) {
                    toggleForm.submit();
                }
            });
        }

        const deleteBtn = document.getElementById('btn-delete-candidate');
        const deleteForm = document.getElementById('delete-candidate-form');
        if (deleteBtn && deleteForm && window.Swal) {
            deleteBtn.addEventListener('click', function () {
                Swal.fire({
                    title: 'Delete candidate?',
                    text: 'This will move the candidate to the bin. You can restore them later.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, delete',
                }).then((result) => {
                    if (result.isConfirmed) deleteForm.submit();
                });
            });
        } else if (deleteBtn && deleteForm) {
            deleteBtn.addEventListener('click', function () {
                if (confirm('Move this candidate to the bin?')) deleteForm.submit();
            });
        }
    });
})();
