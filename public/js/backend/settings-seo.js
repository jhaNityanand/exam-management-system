/**
 * SEO settings — save + regenerate via AJAX.
 */
(function () {
    'use strict';

    const config = window.seoSettingsConfig || {};
    const form = document.getElementById('seo-settings-form');
    const saveBtn = document.getElementById('seo-save-btn');
    const regenerateBtn = document.getElementById('seo-regenerate-btn');

    if (!config.updateUrl) return;

    const clearErrors = () => {
        form?.querySelectorAll('[data-error-for]').forEach((el) => {
            el.hidden = true;
            el.textContent = '';
            el.classList.remove('is-visible');
        });
        form?.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    };

    const showFieldError = (name, message) => {
        const field = form?.querySelector(`[name="${name}"]`);
        const errorEl = form?.querySelector(`[data-error-for="${name}"]`);
        if (field) field.classList.add('is-invalid');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.hidden = false;
            errorEl.classList.add('is-visible');
        }
    };

    const payloadFromForm = () => ({
        chunk_size: Number(form.querySelector('#chunk_size')?.value || 750),
        robots_extra: (form.querySelector('#robots_extra')?.value || '').trim(),
        humans_text: form.querySelector('#humans_text')?.value || '',
        security_contact_email: (form.querySelector('#security_contact_email')?.value || '').trim(),
        security_policy_url: (form.querySelector('#security_policy_url')?.value || '').trim(),
        manifest_name: (form.querySelector('#manifest_name')?.value || '').trim(),
        manifest_short_name: (form.querySelector('#manifest_short_name')?.value || '').trim(),
        manifest_theme_color: (form.querySelector('#manifest_theme_color')?.value || '').trim(),
        manifest_background_color: (form.querySelector('#manifest_background_color')?.value || '').trim(),
    });

    const renderStatus = (status) => {
        const last = document.getElementById('seo-last-generated');
        if (last && status?.last_generated_at) {
            last.textContent = 'Last generated: ' + new Date(status.last_generated_at).toLocaleString();
        }

        const counts = document.getElementById('seo-url-counts');
        if (counts && status?.url_counts) {
            counts.innerHTML = 'URL counts: ' + Object.entries(status.url_counts)
                .map(([section, count]) => `<span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 mr-1 mb-1">${section}: ${count}</span>`)
                .join('');
        }

        const host = document.getElementById('seo-file-status');
        if (host && status?.public_urls) {
            host.innerHTML = Object.entries(status.public_urls).map(([key, url]) => {
                const exists = Boolean(status.files_exist?.[key]);
                const cls = exists
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
                    : 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300';
                return `<a href="${url}" target="_blank" rel="noopener" class="rounded-xl border px-3 py-3 text-sm transition ${cls}">
                    <span class="block font-semibold uppercase tracking-wide text-[11px]">${key}</span>
                    <span class="block mt-1 truncate text-xs opacity-80">${exists ? 'Ready' : 'Missing'}</span>
                </a>`;
            }).join('');
        }
    };

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();

        const payload = payloadFromForm();
        if (!payload.manifest_name || !payload.manifest_short_name) {
            window.Swal?.fire?.({ icon: 'warning', title: 'Check the form', text: 'Manifest name fields are required.' });
            return;
        }

        const original = saveBtn?.textContent;
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
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                if (data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        showFieldError(field, Array.isArray(messages) ? messages[0] : String(messages));
                    });
                }
                throw new Error(data.message || 'Could not save SEO settings.');
            }
            renderStatus(data.status);
            window.EmsToast?.success?.(data.message || 'Saved.');
            window.Swal?.fire?.({ icon: 'success', title: 'Saved', text: data.message || 'SEO settings updated.', timer: 1800, showConfirmButton: false });
        } catch (error) {
            window.Swal?.fire?.({ icon: 'error', title: 'Save failed', text: error.message || 'Could not save SEO settings.' });
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = original || 'Save SEO settings';
            }
        }
    });

    regenerateBtn?.addEventListener('click', async () => {
        const confirm = await window.Swal?.fire?.({
            icon: 'question',
            title: 'Regenerate SEO files?',
            text: 'This will rebuild sitemap.xml, robots.txt, feeds, humans.txt, security.txt, and manifest.json in the public directory.',
            showCancelButton: true,
            confirmButtonText: 'Regenerate',
        });
        if (confirm && !confirm.isConfirmed) return;

        const original = regenerateBtn.textContent;
        regenerateBtn.disabled = true;
        regenerateBtn.textContent = 'Generating…';

        try {
            const response = await fetch(config.regenerateUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Generation failed.');
            }
            renderStatus(data.status);
            window.EmsToast?.success?.(data.message || 'Generated.');
            window.Swal?.fire?.({
                icon: 'success',
                title: 'SEO files ready',
                text: data.message || 'Files were written to the public directory.',
            });
        } catch (error) {
            window.Swal?.fire?.({ icon: 'error', title: 'Generation failed', text: error.message || 'Could not regenerate SEO files.' });
        } finally {
            regenerateBtn.disabled = false;
            regenerateBtn.textContent = original;
        }
    });
}());
