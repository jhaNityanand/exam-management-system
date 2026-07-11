/**
 * Global bottom-right toast notifications.
 * Usage: EmsToast.success('Saved'), EmsToast.error('Failed'), EmsToast.show({ type, message })
 */
(function (global) {
    const LABELS = {
        success: 'Success',
        error: 'Error',
        warning: 'Warning',
        info: 'Info',
    };

    const ICONS = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    };

    function ensureRoot() {
        let root = document.getElementById('ems-toast-root');
        if (root) return root;
        root = document.createElement('div');
        root.id = 'ems-toast-root';
        root.setAttribute('aria-live', 'polite');
        root.setAttribute('aria-relevant', 'additions');
        document.body.appendChild(root);
        return root;
    }

    function normalizeType(type) {
        const allowed = ['success', 'error', 'warning', 'info'];
        return allowed.includes(type) ? type : 'info';
    }

    function show(options = {}) {
        const type = normalizeType(options.type || options.icon || 'info');
        const message = String(options.message || options.title || '').trim();
        if (!message) return null;

        const duration = Number(options.duration ?? (type === 'error' ? 5000 : 3600));
        const root = ensureRoot();

        const el = document.createElement('div');
        el.className = `ems-toast ems-toast--${type}`;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        el.innerHTML = `
            <span class="ems-toast__icon" aria-hidden="true">${ICONS[type]}</span>
            <div class="ems-toast__body">
                <p class="ems-toast__label">${LABELS[type]}</p>
                <p class="ems-toast__title"></p>
            </div>
            <button type="button" class="ems-toast__close" aria-label="Dismiss">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
            <div class="ems-toast__bar" aria-hidden="true"><span style="animation-duration:${duration}ms"></span></div>
        `;
        el.querySelector('.ems-toast__title').textContent = message;

        const remove = () => {
            if (el.dataset.leaving === '1') return;
            el.dataset.leaving = '1';
            el.classList.add('is-leaving');
            el.classList.remove('is-visible');
            window.setTimeout(() => el.remove(), 240);
        };

        el.querySelector('.ems-toast__close')?.addEventListener('click', remove);

        root.appendChild(el);
        requestAnimationFrame(() => el.classList.add('is-visible'));

        let timer = window.setTimeout(remove, duration);
        el.addEventListener('mouseenter', () => clearTimeout(timer));
        el.addEventListener('mouseleave', () => {
            timer = window.setTimeout(remove, 1400);
        });

        return el;
    }

    function showFlashQueue(items) {
        if (!Array.isArray(items) || !items.length) return;
        items.forEach((item, index) => {
            window.setTimeout(() => show(item), index * 140);
        });
    }

    global.EmsToast = {
        show,
        success: (message, opts = {}) => show({ ...opts, type: 'success', message }),
        error: (message, opts = {}) => show({ ...opts, type: 'error', message }),
        warning: (message, opts = {}) => show({ ...opts, type: 'warning', message }),
        info: (message, opts = {}) => show({ ...opts, type: 'info', message }),
        fromFlash: showFlashQueue,
    };
}(window));
