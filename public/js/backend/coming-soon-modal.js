/**
 * Reusable Coming Soon modal open/close helpers.
 */
(function (global) {
    'use strict';

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        if (!document.querySelector('.ems-coming-soon-modal:not([hidden]), .cand-modal:not([hidden])')) {
            document.body.style.overflow = '';
        }
    }

    function bindComingSoonModals(root) {
        const scope = root || document;

        scope.querySelectorAll('[data-coming-soon-modal]').forEach((btn) => {
            if (btn.dataset.boundComingSoon === '1') return;
            btn.dataset.boundComingSoon = '1';
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                openModal(btn.getAttribute('data-coming-soon-modal'));
            });
        });

        scope.querySelectorAll('.ems-coming-soon-modal').forEach((modal) => {
            if (modal.dataset.boundComingSoon === '1') return;
            modal.dataset.boundComingSoon = '1';
            modal.querySelectorAll('[data-modal-close]').forEach((el) => {
                el.addEventListener('click', () => closeModal(modal));
            });
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.ems-coming-soon-modal:not([hidden])').forEach((modal) => closeModal(modal));
    });

    document.addEventListener('DOMContentLoaded', () => bindComingSoonModals(document));

    global.EmsComingSoonModal = {
        open: openModal,
        close: closeModal,
        bind: bindComingSoonModals,
    };
})(window);
