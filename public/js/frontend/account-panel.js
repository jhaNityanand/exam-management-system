/**
 * Candidate account panel — shared chrome + helpers.
 */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    window.CaAccount = {
        csrfToken: csrfToken,
        async fetchJson(url, options) {
            options = options || {};
            var headers = Object.assign({
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            }, options.headers || {});
            var res = await fetch(url, Object.assign({ credentials: 'same-origin' }, options, { headers: headers }));
            var data = await res.json().catch(function () { return {}; });
            if (!res.ok) {
                var err = new Error(data.message || 'Request failed.');
                err.status = res.status;
                err.errors = data.errors || {};
                err.payload = data;
                throw err;
            }
            return data;
        },
        setButtonLoading(btn, loading, label) {
            if (!btn) return;
            if (loading) {
                btn.dataset.caLabel = btn.textContent;
                btn.textContent = label || 'Saving…';
                btn.classList.add('ca-btn-loading');
                btn.disabled = true;
            } else {
                btn.textContent = btn.dataset.caLabel || btn.textContent;
                btn.classList.remove('ca-btn-loading');
                btn.disabled = false;
            }
        },
        showAlert(el, type, message) {
            if (!el) return;
            el.hidden = false;
            el.className = 'ca-alert ca-alert--' + (type === 'error' ? 'error' : 'success');
            el.textContent = message;
        },
        clearFieldErrors(form) {
            form.querySelectorAll('.ca-field').forEach(function (field) {
                field.classList.remove('is-invalid');
                var err = field.querySelector('.ca-field__error');
                if (err) err.remove();
            });
        },
        applyFieldErrors(form, errors) {
            Object.keys(errors || {}).forEach(function (key) {
                var input = form.querySelector('[name="' + key + '"], [name="' + key + '[]"]');
                if (!input) {
                    input = form.querySelector('[name="' + key.replace(/\./g, '][') + '"]');
                }
                // dotted: social_links.website -> social_links[website]
                if (!input && key.indexOf('.') !== -1) {
                    var parts = key.split('.');
                    input = form.querySelector('[name="' + parts[0] + '[' + parts.slice(1).join('][') + ']"]');
                }
                if (!input) return;
                var field = input.closest('.ca-field') || input.parentElement;
                if (!field) return;
                field.classList.add('is-invalid');
                var msg = document.createElement('div');
                msg.className = 'ca-field__error';
                msg.textContent = (errors[key] && errors[key][0]) || 'Invalid value';
                field.appendChild(msg);
            });
        },
    };

    onReady(function () {
        var sidebar = document.getElementById('ca-sidebar');
        var backdrop = document.querySelector('[data-ca-sidebar-close]');
        var openBtn = document.querySelector('[data-ca-sidebar-open]');

        function closeSidebar() {
            if (!sidebar) return;
            sidebar.classList.remove('is-open');
            if (backdrop) backdrop.hidden = true;
            if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        function openSidebar() {
            if (!sidebar) return;
            sidebar.classList.add('is-open');
            if (backdrop) backdrop.hidden = false;
            if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }

        if (openBtn) openBtn.addEventListener('click', openSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 960) closeSidebar();
        });
    });
})();
