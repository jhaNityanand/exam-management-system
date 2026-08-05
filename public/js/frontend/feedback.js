/**
 * Shared feedback form + result modal helpers.
 */
(function () {
    'use strict';

    var LABELS = {
        1: 'Poor',
        2: 'Fair',
        3: 'Good',
        4: 'Very good',
        5: 'Excellent',
    };

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function paintStars(root, value, hoverValue) {
        var active = hoverValue || value || 0;
        root.querySelectorAll('[data-fb-star]').forEach(function (btn) {
            var n = Number(btn.getAttribute('data-fb-star') || 0);
            btn.classList.toggle('is-on', n <= (value || 0));
            btn.classList.toggle('is-hover', !!hoverValue && n <= hoverValue);
            btn.setAttribute('aria-checked', n === value ? 'true' : 'false');
        });
        var label = root.querySelector('[data-fb-rating-label]');
        if (label) {
            label.textContent = value ? (LABELS[value] || (value + ' stars')) : 'Select a rating';
        }
    }

    function bindStars(form) {
        var wrap = form.querySelector('[data-fb-stars]');
        var input = form.querySelector('[data-fb-rating]');
        if (!wrap || !input) return;

        wrap.querySelectorAll('[data-fb-star]').forEach(function (btn) {
            var n = Number(btn.getAttribute('data-fb-star') || 0);
            btn.addEventListener('mouseenter', function () {
                paintStars(form, Number(input.value || 0), n);
            });
            btn.addEventListener('mouseleave', function () {
                paintStars(form, Number(input.value || 0), 0);
            });
            btn.addEventListener('click', function () {
                input.value = String(n);
                paintStars(form, n, 0);
                clearError(form);
            });
        });
    }

    function clearError(form) {
        var err = form.querySelector('[data-fb-error]');
        if (!err) return;
        err.hidden = true;
        err.textContent = '';
    }

    function showError(form, message) {
        var err = form.querySelector('[data-fb-error]');
        if (!err) return;
        err.hidden = false;
        err.removeAttribute('hidden');
        err.textContent = message || 'Unable to submit feedback.';
    }

    function bindCounter(form) {
        var ta = form.querySelector('[data-fb-message]');
        var count = form.querySelector('[data-fb-count]');
        if (!ta || !count) return;
        var sync = function () {
            count.textContent = String((ta.value || '').length);
        };
        ta.addEventListener('input', sync);
        sync();
    }

    async function submitForm(form) {
        clearError(form);
        var rating = Number(form.querySelector('[data-fb-rating]')?.value || 0);
        var message = (form.querySelector('[data-fb-message]')?.value || '').trim();
        var title = (form.querySelector('[data-fb-title]')?.value || '').trim();

        if (!rating || rating < 1 || rating > 5) {
            showError(form, 'Please select a star rating.');
            return false;
        }
        if (message.length < 10) {
            showError(form, 'Please share at least 10 characters.');
            return false;
        }

        var url = form.getAttribute('data-store-url');
        if (!url) return false;

        form.classList.add('is-loading');
        var submitBtn = form.querySelector('[data-fb-submit]');
        var label = form.querySelector('[data-fb-submit-label]');
        if (submitBtn) submitBtn.disabled = true;
        if (label) label.textContent = 'Submitting…';

        try {
            var res = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    rating: rating,
                    title: title || null,
                    message: message,
                    exam_id: Number(form.getAttribute('data-exam-id') || 0) || null,
                    exam_attempt_id: Number(form.getAttribute('data-attempt-id') || 0) || null,
                    source: form.getAttribute('data-source') || 'web',
                }),
            });
            var data = await res.json().catch(function () { return {}; });
            if (!res.ok) {
                var first = data.message
                    || (data.errors && Object.values(data.errors).flat()[0])
                    || 'Unable to submit feedback.';
                throw new Error(first);
            }

            var success = form.querySelector('[data-fb-success]');
            if (success) {
                success.hidden = false;
                success.removeAttribute('hidden');
            }
            form.dispatchEvent(new CustomEvent('feedback:submitted', { bubbles: true, detail: data }));
            return true;
        } catch (e) {
            showError(form, (e && e.message) || 'Unable to submit feedback.');
            return false;
        } finally {
            form.classList.remove('is-loading');
            if (submitBtn) submitBtn.disabled = false;
            if (label) label.textContent = 'Submit feedback';
        }
    }

    function enhanceForm(form) {
        if (!form || form.__fbBound) return;
        form.__fbBound = true;
        bindStars(form);
        bindCounter(form);
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            submitForm(form);
        });
    }

    function initOpenPanel() {
        document.querySelectorAll('[data-fb-open-panel]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var panel = document.getElementById('fb-exam-panel');
                if (!panel) return;
                panel.hidden = false;
                panel.removeAttribute('hidden');
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                panel.querySelector('[data-fb-star="5"]')?.focus();
            });
        });
    }

    function initExamShowModal() {
        var modal = document.getElementById('fb-exam-modal');
        if (!modal) return;

        var form = modal.querySelector('[data-fb-form]');
        var openers = document.querySelectorAll('[data-fb-open-exam-modal]');
        if (!openers.length) return;

        function openModal() {
            modal.hidden = false;
            modal.removeAttribute('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('fb-modal-open');
            modal.querySelector('[data-fb-star="5"]')?.focus();
        }

        function closeModal() {
            modal.hidden = true;
            modal.setAttribute('hidden', 'hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('fb-modal-open');
        }

        openers.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openModal();
            });
        });

        modal.querySelectorAll('[data-fb-close-exam-modal]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                closeModal();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });

        form?.addEventListener('feedback:submitted', function () {
            window.setTimeout(closeModal, 500);
        });
    }

    function initResultModal() {
        var page = document.getElementById('rs-page');
        var modal = document.getElementById('fb-result-modal');
        if (!page || !modal) return;
        if (page.getAttribute('data-needs-feedback') !== '1') return;

        var form = modal.querySelector('[data-fb-form]');
        var closed = false;

        function closeModal(skipped) {
            if (closed) return;
            closed = true;
            modal.hidden = true;
            modal.setAttribute('hidden', 'hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('fb-modal-open');
            page.dispatchEvent(new CustomEvent('feedback:closed', {
                bubbles: true,
                detail: { skipped: !!skipped },
            }));
        }

        async function skipFeedback() {
            var skipUrl = page.getAttribute('data-feedback-skip-url');
            var attemptId = page.getAttribute('data-attempt-id');
            if (skipUrl && attemptId) {
                try {
                    await fetch(skipUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ exam_attempt_id: Number(attemptId) }),
                    });
                } catch (e) {}
            }
            closeModal(true);
        }

        enhanceForm(form);
        modal.hidden = false;
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('fb-modal-open');

        modal.querySelectorAll('[data-fb-skip]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                skipFeedback();
            });
        });

        form?.addEventListener('feedback:submitted', function () {
            window.setTimeout(function () { closeModal(false); }, 700);
        });
    }

    function initExamShowReload() {
        document.querySelectorAll('[data-fb-form][data-source="exam_show"]').forEach(function (form) {
            form.addEventListener('feedback:submitted', function () {
                window.setTimeout(function () {
                    window.location.reload();
                }, 650);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-fb-form]').forEach(enhanceForm);
        initOpenPanel();
        initExamShowModal();
        initResultModal();
        initExamShowReload();
    });

    window.EmsFeedback = {
        enhanceForm: enhanceForm,
        submitForm: submitForm,
    };
})();
