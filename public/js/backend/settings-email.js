/**
 * Email Configuration settings — AJAX save + test email.
 */
(function () {
    'use strict';

    const config = window.emailSettingsConfig || {};
    const form = document.getElementById('email-settings-form');
    const saveBtn = document.getElementById('email-save-btn');
    const testBtn = document.getElementById('email-test-btn');
    const mailerPill = document.getElementById('email-mailer-pill');

    if (!form || !config.updateUrl) {
        return;
    }

    const mailerLabels = {
        log: 'Log (development)',
        smtp: 'SMTP',
        sendmail: 'Sendmail',
    };

    const clearErrors = () => {
        form.querySelectorAll('[data-error-for]').forEach((el) => {
            el.hidden = true;
            el.textContent = '';
            el.classList.remove('is-visible');
        });
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    };

    const showFieldError = (name, message) => {
        const field = form.querySelector(`[name="${name}"]`) || document.getElementById(name);
        const errorEl = form.querySelector(`[data-error-for="${name}"]`)
            || document.querySelector(`[data-error-for="${name}"]`);
        if (field) field.classList.add('is-invalid');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.hidden = false;
            errorEl.classList.add('is-visible');
        }
    };

    const updateMailerPill = (mailer) => {
        if (!mailerPill) return;
        const label = mailerPill.querySelector('[data-mailer-label]');
        if (label) label.textContent = mailerLabels[mailer] || mailer;
    };

    const clientValidate = () => {
        clearErrors();
        let valid = true;
        const mailer = form.querySelector('#mailer')?.value || 'log';
        const fromAddress = form.querySelector('#from_address');
        const fromName = form.querySelector('#from_name');

        if (!fromAddress?.value?.trim()) {
            showFieldError('from_address', 'From address is required.');
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fromAddress.value.trim())) {
            showFieldError('from_address', 'Enter a valid From email address.');
            valid = false;
        }

        if (!fromName?.value?.trim()) {
            showFieldError('from_name', 'From name is required.');
            valid = false;
        }

        if (mailer === 'smtp') {
            const host = form.querySelector('#host');
            const port = form.querySelector('#port');
            if (!host?.value?.trim()) {
                showFieldError('host', 'SMTP host is required.');
                valid = false;
            }
            if (!port?.value || Number(port.value) < 1) {
                showFieldError('port', 'Enter a valid SMTP port.');
                valid = false;
            }
        }

        if (!valid) {
            form.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return valid;
    };

    const collectPayload = () => {
        const fd = new FormData(form);
        return {
            mailer: (fd.get('mailer') || 'log').toString(),
            host: (fd.get('host') || '').toString().trim(),
            port: Number(fd.get('port') || 587),
            username: (fd.get('username') || '').toString().trim(),
            password: (fd.get('password') || '').toString(),
            encryption: (fd.get('encryption') || 'tls').toString(),
            from_address: (fd.get('from_address') || '').toString().trim(),
            from_name: (fd.get('from_name') || '').toString().trim(),
            google_oauth_enabled: form.querySelector('#google_oauth_enabled')?.checked ? 1 : 0,
            google_client_id: (fd.get('google_client_id') || '').toString().trim(),
            google_client_secret: (fd.get('google_client_secret') || '').toString(),
            google_redirect_uri: (fd.get('google_redirect_uri') || '').toString().trim(),
        };
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!clientValidate()) {
            window.Swal?.fire?.({
                icon: 'warning',
                title: 'Check the form',
                text: 'Please fix the highlighted fields before saving.',
            });
            return;
        }

        const originalLabel = saveBtn?.textContent || 'Save';
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
                body: JSON.stringify(collectPayload()),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        showFieldError(field, Array.isArray(messages) ? messages[0] : String(messages));
                    });
                }
                throw new Error(data.message || 'Could not save email settings.');
            }

            updateMailerPill(data.settings?.mailer || collectPayload().mailer);
            // Clear secret fields after successful save
            const password = form.querySelector('#password');
            const googleSecret = form.querySelector('#google_client_secret');
            if (password) {
                password.value = '';
                password.placeholder = '••••••••  (leave blank to keep current)';
            }
            if (googleSecret) {
                googleSecret.value = '';
                googleSecret.placeholder = '••••••••  (leave blank to keep current)';
            }

            window.EmsToast?.success?.(data.message || 'Settings saved.');
            window.Swal?.fire?.({
                icon: 'success',
                title: 'Saved',
                text: data.message || 'Email settings updated.',
                timer: 2200,
                showConfirmButton: false,
            });
        } catch (error) {
            window.EmsToast?.error?.(error.message || 'Save failed.');
            window.Swal?.fire?.({
                icon: 'error',
                title: 'Save failed',
                text: error.message || 'Could not save email settings.',
            });
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = originalLabel;
            }
        }
    });

    testBtn?.addEventListener('click', async () => {
        const toInput = document.getElementById('test_to');
        const to = (toInput?.value || '').trim();
        clearErrors();

        if (!to || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(to)) {
            showFieldError('test_to', 'Enter a valid recipient email.');
            window.Swal?.fire?.({
                icon: 'warning',
                title: 'Recipient required',
                text: 'Enter a valid email address to receive the test message.',
            });
            return;
        }

        const originalLabel = testBtn.textContent;
        testBtn.disabled = true;
        testBtn.textContent = 'Sending…';

        try {
            const response = await fetch(config.testUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ to }),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Test email failed.');
            }

            window.EmsToast?.success?.(data.message || 'Test email sent.');
            window.Swal?.fire?.({
                icon: 'success',
                title: 'Test sent',
                text: data.message || 'Check the recipient inbox (or the log file if using Log transport).',
                timer: 2800,
                showConfirmButton: false,
            });
        } catch (error) {
            window.EmsToast?.error?.(error.message || 'Test failed.');
            window.Swal?.fire?.({
                icon: 'error',
                title: 'Test failed',
                text: error.message || 'Could not send the test email.',
            });
        } finally {
            testBtn.disabled = false;
            testBtn.textContent = originalLabel;
        }
    });
}());
