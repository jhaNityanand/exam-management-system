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
        /**
         * Acknowledgement messages → toast (same as backend EmsToast).
         * Dialogs/confirms should use confirm() / SweetAlert2 instead.
         */
        showAlert(el, type, message) {
            var text = (message || '').toString().trim();
            if (!text) return;

            var tone = (type || 'info').toString().toLowerCase();
            if (tone === 'failed' || tone === 'danger') tone = 'error';
            if (tone !== 'success' && tone !== 'error' && tone !== 'warning' && tone !== 'info') {
                tone = 'info';
            }

            if (el) {
                el.hidden = true;
                el.textContent = '';
                el.className = 'ca-alert';
            }

            if (window.EmsToast && typeof window.EmsToast[tone] === 'function') {
                window.EmsToast[tone](text);
                return;
            }

            // Fallback if toast assets failed to load
            if (el) {
                el.hidden = false;
                el.className = 'ca-alert ca-alert--' + (tone === 'error' ? 'error' : 'success');
                el.textContent = text;
            }
        },
        /**
         * Confirm / alert dialogs → SweetAlert2 (same idea as backend).
         */
        confirm(options) {
            options = options || {};
            var title = options.title || 'Are you sure?';
            var text = options.text || '';
            var confirmText = options.confirmText || 'Yes';
            var cancelText = options.cancelText || 'Cancel';
            var icon = options.icon || 'warning';

            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: cancelText,
                    reverseButtons: true,
                    focusCancel: true,
                }).then(function (result) {
                    return !!(result && result.isConfirmed);
                });
            }

            return Promise.resolve(window.confirm(text ? (title + '\n\n' + text) : title));
        },
        alert(options) {
            options = options || {};
            var title = options.title || 'Notice';
            var text = options.text || '';
            var icon = options.icon || 'info';

            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmButtonText: options.confirmText || 'OK',
                });
            }

            window.alert(text ? (title + '\n\n' + text) : title);
            return Promise.resolve();
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
