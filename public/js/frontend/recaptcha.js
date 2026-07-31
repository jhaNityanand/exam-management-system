/**
 * Attach reCAPTCHA tokens to forms that include [data-et-recaptcha-token].
 */
(function () {
    'use strict';

    const cfg = window.ExamtubeRecaptcha;
    if (!cfg || !cfg.enabled || !cfg.site_key) return;

    const ensureScript = () => new Promise((resolve, reject) => {
        if (window.grecaptcha) {
            resolve(window.grecaptcha);
            return;
        }
        const existing = document.querySelector('script[data-et-recaptcha]');
        if (existing) {
            existing.addEventListener('load', () => resolve(window.grecaptcha));
            existing.addEventListener('error', reject);
            return;
        }
        const script = document.createElement('script');
        script.src = cfg.version === 'v2'
            ? 'https://www.google.com/recaptcha/api.js'
            : 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(cfg.site_key);
        script.async = true;
        script.defer = true;
        script.dataset.etRecaptcha = '1';
        script.onload = () => resolve(window.grecaptcha);
        script.onerror = reject;
        document.head.appendChild(script);
    });

    const attachForm = (form) => {
        const tokenInput = form.querySelector('[data-et-recaptcha-token]');
        if (!tokenInput) return;

        form.addEventListener('submit', async (event) => {
            if (form.dataset.etRecaptchaReady === '1') return;
            if (cfg.version === 'v2') {
                // v2 widget fills g-recaptcha-response itself when present
                const widget = form.querySelector('[name="g-recaptcha-response"]');
                if (widget && widget.value) {
                    tokenInput.value = widget.value;
                }
                return;
            }

            event.preventDefault();
            try {
                const grecaptcha = await ensureScript();
                await new Promise((resolve) => grecaptcha.ready(resolve));
                const token = await grecaptcha.execute(cfg.site_key, { action: cfg.context || 'submit' });
                tokenInput.value = token;
                form.dataset.etRecaptchaReady = '1';
                form.requestSubmit ? form.requestSubmit() : form.submit();
            } catch (error) {
                form.dataset.etRecaptchaReady = '';
                if (window.EmsToast && typeof window.EmsToast.error === 'function') {
                    window.EmsToast.error('reCAPTCHA failed to load. Please refresh and try again.');
                } else if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'reCAPTCHA failed',
                        text: 'Please refresh and try again.',
                    });
                } else {
                    window.alert('reCAPTCHA failed to load. Please refresh and try again.');
                }
            }
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        ensureScript().catch(() => {});
        document.querySelectorAll('form').forEach(attachForm);

        // Newsletter AJAX forms: inject token before fetch if present
        document.querySelectorAll('[data-newsletter-form]').forEach((form) => {
            form.addEventListener('et:before-submit', async (event) => {
                if (cfg.version !== 'v3') return;
                try {
                    const grecaptcha = await ensureScript();
                    await new Promise((resolve) => grecaptcha.ready(resolve));
                    const token = await grecaptcha.execute(cfg.site_key, { action: 'newsletter' });
                    const input = form.querySelector('[data-et-recaptcha-token]');
                    if (input) input.value = token;
                } catch {
                    event.preventDefault();
                }
            });
        });
    });
}());
