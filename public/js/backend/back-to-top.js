/**
 * Backend Back to Top — scrolls #panel-main (admin shell).
 */
(function () {
    'use strict';

    function init() {
        const btn = document.querySelector('[data-admin-back-top]');
        const scroller = document.getElementById('panel-main') || document.scrollingElement || document.documentElement;
        if (!btn || !scroller) return;

        const threshold = 320;

        const sync = () => {
            const top = scroller === window || scroller === document.documentElement || scroller === document.body
                ? (window.scrollY || document.documentElement.scrollTop || 0)
                : (scroller.scrollTop || 0);
            const show = top > threshold;
            btn.classList.toggle('is-visible', show);
            btn.hidden = !show;
            btn.setAttribute('aria-hidden', show ? 'false' : 'true');
        };

        btn.addEventListener('click', () => {
            const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const behavior = reduce ? 'auto' : 'smooth';
            if (typeof scroller.scrollTo === 'function') {
                scroller.scrollTo({ top: 0, behavior });
            } else {
                scroller.scrollTop = 0;
            }
        });

        const target = scroller === document.documentElement || scroller === document.body ? window : scroller;
        target.addEventListener('scroll', sync, { passive: true });
        window.addEventListener('resize', sync, { passive: true });
        sync();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
