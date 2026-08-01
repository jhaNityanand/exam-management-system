/**
 * Maintenance Mode settings — AJAX save + client validation.
 */
(function () {
    'use strict';

    const config = window.maintenanceSettingsConfig || {};
    const form = document.getElementById('maintenance-form');
    const saveBtn = document.getElementById('maintenance-save-btn');
    const statusPill = document.getElementById('maintenance-status-pill');

    if (!form || !config.updateUrl) {
        return;
    }

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

    const plainFromHtml = (html) => String(html || '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const updateStatusPill = (enabled) => {
        if (!statusPill) return;
        const label = statusPill.querySelector('[data-status-label]');
        statusPill.className = 'maint-status ' + (enabled ? 'maint-status--on' : 'maint-status--off');
        if (label) label.textContent = enabled ? 'Enabled' : 'Disabled';
    };

    const syncEditors = () => {
        window.EmsRichTextEditor?.syncAll?.();
    };

    const clientValidate = () => {
        clearErrors();
        syncEditors();
        let valid = true;
        const title = form.querySelector('#title');
        const message = form.querySelector('#message');

        if (!title?.value?.trim()) {
            showFieldError('title', 'Title is required.');
            valid = false;
        }
        if (!plainFromHtml(message?.value || '')) {
            showFieldError('message', 'Message is required.');
            valid = false;
        }

        ['social_facebook', 'social_instagram', 'social_linkedin', 'social_twitter', 'social_youtube', 'social_telegram']
            .forEach((name) => {
                const input = form.querySelector(`[name="${name}"]`);
                const value = input?.value?.trim();
                if (!value) return;
                try {
                    const url = new URL(value);
                    if (!['http:', 'https:'].includes(url.protocol)) {
                        throw new Error('invalid');
                    }
                } catch {
                    showFieldError(name, 'Enter a valid URL starting with https://');
                    valid = false;
                }
            });

        if (!valid) {
            form.querySelector('.is-invalid, [data-error-for].is-visible')?.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }

        return valid;
    };

    const collectPayload = () => {
        syncEditors();
        const fd = new FormData(form);
        return {
            enabled: form.querySelector('#enabled')?.checked ? 1 : 0,
            title: (fd.get('title') || '').toString().trim(),
            message: (fd.get('message') || '').toString().trim(),
            estimated_at: (fd.get('estimated_at') || '').toString().trim(),
            social_facebook: (fd.get('social_facebook') || '').toString().trim(),
            social_instagram: (fd.get('social_instagram') || '').toString().trim(),
            social_linkedin: (fd.get('social_linkedin') || '').toString().trim(),
            social_twitter: (fd.get('social_twitter') || '').toString().trim(),
            social_youtube: (fd.get('social_youtube') || '').toString().trim(),
            social_telegram: (fd.get('social_telegram') || '').toString().trim(),
            logo_gallery_id: (fd.get('logo_gallery_id') || '').toString().trim() || null,
            background_gallery_id: (fd.get('background_gallery_id') || '').toString().trim() || null,
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

        const enabled = form.querySelector('#enabled')?.checked;
        if (enabled) {
            const confirm = await window.Swal?.fire?.({
                icon: 'warning',
                title: 'Enable maintenance mode?',
                html: 'Frontend visitors (including candidates) will only see the maintenance page.<br><strong>Admin panel access will remain available.</strong>',
                showCancelButton: true,
                confirmButtonText: 'Yes, enable it',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d97706',
            });
            if (confirm && !confirm.isConfirmed) {
                return;
            }
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
                throw new Error(data.message || 'Could not save maintenance settings.');
            }

            updateStatusPill(Boolean(data.settings?.enabled));
            window.EmsToast?.success?.(data.message || 'Settings saved.');
            window.Swal?.fire?.({
                icon: 'success',
                title: 'Saved',
                text: data.message || 'Maintenance settings updated.',
                timer: 2200,
                showConfirmButton: false,
            });
        } catch (error) {
            window.EmsToast?.error?.(error.message || 'Save failed.');
            window.Swal?.fire?.({
                icon: 'error',
                title: 'Save failed',
                text: error.message || 'Could not save maintenance settings.',
            });
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = originalLabel;
            }
        }
    });

    document.addEventListener('DOMContentLoaded', async () => {
        if (window.EmsRichTextEditor?.initAll) {
            await window.EmsRichTextEditor.initAll(document);
        }
        if (window.EmsContentForm?.initGalleryPickers) {
            window.EmsContentForm.initGalleryPickers({});
        }
    });
}());
