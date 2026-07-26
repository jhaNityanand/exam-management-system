/**
 * Security settings — AJAX save.
 */
(function () {
    'use strict';

    const config = window.securitySettingsConfig || {};
    const form = document.getElementById('security-settings-form');
    const saveBtn = document.getElementById('security-save-btn');
    if (!form || !config.updateUrl) return;

    const clearErrors = () => {
        form.querySelectorAll('[data-error-for]').forEach((el) => {
            el.hidden = true;
            el.textContent = '';
            el.classList.remove('is-visible');
        });
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    };

    const showFieldError = (name, message) => {
        const field = form.querySelector(`[name="${name}"]`);
        const errorEl = form.querySelector(`[data-error-for="${name}"]`);
        if (field) field.classList.add('is-invalid');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.hidden = false;
            errorEl.classList.add('is-visible');
        }
    };

    const bool = (id) => (form.querySelector('#' + id)?.checked ? 1 : 0);

    const payload = () => {
        const fd = new FormData(form);
        return {
            recaptcha_enabled: bool('recaptcha_enabled'),
            recaptcha_version: (fd.get('recaptcha_version') || 'v3').toString(),
            recaptcha_site_key: (fd.get('recaptcha_site_key') || '').toString().trim(),
            recaptcha_secret_key: (fd.get('recaptcha_secret_key') || '').toString(),
            recaptcha_score_threshold: (fd.get('recaptcha_score_threshold') || '0.5').toString(),
            recaptcha_on_login: bool('recaptcha_on_login'),
            recaptcha_on_register: bool('recaptcha_on_register'),
            recaptcha_on_contact: bool('recaptcha_on_contact'),
            recaptcha_on_newsletter: bool('recaptcha_on_newsletter'),
            recaptcha_on_password_reset: bool('recaptcha_on_password_reset'),
            login_lockout_enabled: bool('login_lockout_enabled'),
            login_max_attempts: Number(fd.get('login_max_attempts') || 5),
            login_decay_minutes: Number(fd.get('login_decay_minutes') || 1),
        };
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        const original = saveBtn?.textContent || 'Save';
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';
        }
        try {
            const response = await fetch(config.updateUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload()),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        showFieldError(field, Array.isArray(messages) ? messages[0] : String(messages));
                    });
                }
                throw new Error(data.message || 'Save failed.');
            }
            const secret = form.querySelector('#recaptcha_secret_key');
            if (secret) {
                secret.value = '';
                secret.placeholder = '••••••••  (leave blank to keep)';
            }
            window.EmsToast?.success?.(data.message || 'Saved');
            window.Swal?.fire?.({ icon: 'success', title: 'Saved', text: data.message, timer: 2000, showConfirmButton: false });
        } catch (error) {
            window.EmsToast?.error?.(error.message || 'Save failed');
            window.Swal?.fire?.({ icon: 'error', title: 'Save failed', text: error.message });
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = original;
            }
        }
    });
}());
