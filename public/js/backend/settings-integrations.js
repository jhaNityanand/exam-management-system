/**
 * Integrations & Privacy settings — AJAX save.
 */
(function () {
    'use strict';

    const config = window.integrationsSettingsConfig || {};
    const form = document.getElementById('integrations-settings-form');
    const saveBtn = document.getElementById('integrations-save-btn');
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

    const payload = () => {
        const fd = new FormData(form);
        return {
            analytics_enabled: form.querySelector('#analytics_enabled')?.checked ? 1 : 0,
            google_analytics_id: (fd.get('google_analytics_id') || '').toString().trim(),
            gtm_container_id: (fd.get('gtm_container_id') || '').toString().trim(),
            facebook_pixel_id: (fd.get('facebook_pixel_id') || '').toString().trim(),
            custom_head_scripts: (fd.get('custom_head_scripts') || '').toString(),
            custom_body_scripts: (fd.get('custom_body_scripts') || '').toString(),
            cookies_enabled: form.querySelector('#cookies_enabled')?.checked ? 1 : 0,
            cookies_mode: (fd.get('cookies_mode') || 'opt_in').toString(),
            cookies_title: (fd.get('cookies_title') || '').toString().trim(),
            cookies_message: (fd.get('cookies_message') || '').toString().trim(),
            cookies_accept_label: (fd.get('cookies_accept_label') || '').toString().trim(),
            cookies_reject_label: (fd.get('cookies_reject_label') || '').toString().trim(),
            cookies_policy_url: (fd.get('cookies_policy_url') || '').toString().trim(),
            default_timezone: (fd.get('default_timezone') || 'Asia/Kolkata').toString(),
            default_locale: (fd.get('default_locale') || 'en').toString().trim(),
            registration_enabled: form.querySelector('#registration_enabled')?.checked ? 1 : 0,
            newsletter_enabled: form.querySelector('#newsletter_enabled')?.checked ? 1 : 0,
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
