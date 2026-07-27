/**
 * Organization Settings — AJAX save + hero banner CRUD.
 */
(function () {
    'use strict';

    const config = window.orgSettingsConfig || {};
    const form = document.getElementById('organization-settings-form');
    const saveBtn = document.getElementById('org-settings-save-btn');
    const heroModal = document.getElementById('hero-modal');
    const heroForm = document.getElementById('hero-form');
    const heroList = document.getElementById('hero-list');

    if (!config.updateUrl || !form) return;

    const clearErrors = (root = form) => {
        root.querySelectorAll('[data-error-for]').forEach((el) => {
            el.hidden = true;
            el.textContent = '';
            el.classList.remove('is-visible');
        });
        root.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    };

    const resolveFieldName = (name) => {
        if (!name || !name.includes('.')) return name;
        if (name.startsWith('support_hours.')) {
            const parts = name.split('.');
            if (parts.length === 3) {
                return `support_hours[${parts[1]}][${parts[2]}]`;
            }
        }
        if (name.startsWith('social.') && name.endsWith('.url')) {
            const platform = name.split('.')[1];
            return `social[${platform}][url]`;
        }
        return name;
    };

    const showFieldError = (name, message, root = form) => {
        const resolved = resolveFieldName(name);
        const errorEl = root.querySelector(`[data-error-for="${name}"]`)
            || root.querySelector(`[data-error-for="${resolved}"]`);
        const input = root.querySelector(`[name="${resolved}"]`)
            || root.querySelector(`[name="${name}"]`)
            || root.querySelector(`#${CSS.escape(name.replace(/\./g, '_'))}`);
        input?.classList?.add('is-invalid');
        input?._flatpickr?.altInput?.classList?.add('is-invalid');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.hidden = false;
            errorEl.classList.add('is-visible');
        }
    };

    const collectPayload = () => {
        const fd = new FormData(form);
        const payload = {
            site_name: (fd.get('site_name') || '').toString().trim(),
            application_url: (fd.get('application_url') || '').toString().trim(),
            tagline: (fd.get('tagline') || '').toString().trim(),
            description: (fd.get('description') || '').toString().trim(),
            logo_text: (fd.get('logo_text') || '').toString().trim(),
            logo_gallery_id: (fd.get('logo_gallery_id') || '').toString().trim() || null,
            favicon_gallery_id: (fd.get('favicon_gallery_id') || '').toString().trim() || null,
            og_image_gallery_id: (fd.get('og_image_gallery_id') || '').toString().trim() || null,
            email: (fd.get('email') || '').toString().trim(),
            phone: (fd.get('phone') || '').toString().trim(),
            whatsapp: (fd.get('whatsapp') || '').toString().trim(),
            address: (fd.get('address') || '').toString().trim(),
            hours: (fd.get('hours') || '').toString().trim(),
            support_hours: [],
            maps_url: (fd.get('maps_url') || '').toString().trim(),
            footer_about: (fd.get('footer_about') || '').toString().trim(),
            footer_copyright: (fd.get('footer_copyright') || '').toString().trim(),
            cta_title: (fd.get('cta_title') || '').toString().trim(),
            cta_subtitle: (fd.get('cta_subtitle') || '').toString().trim(),
            cta_primary_label: (fd.get('cta_primary_label') || '').toString().trim(),
            cta_primary_url: (fd.get('cta_primary_url') || '').toString().trim(),
            cta_secondary_label: (fd.get('cta_secondary_label') || '').toString().trim(),
            cta_secondary_url: (fd.get('cta_secondary_url') || '').toString().trim(),
            newsletter_title: (fd.get('newsletter_title') || '').toString().trim(),
            newsletter_subtitle: (fd.get('newsletter_subtitle') || '').toString().trim(),
            newsletter_cta: (fd.get('newsletter_cta') || '').toString().trim(),
            seo_default_title: (fd.get('seo_default_title') || '').toString().trim(),
            seo_default_description: (fd.get('seo_default_description') || '').toString().trim(),
            seo_default_keywords: (fd.get('seo_default_keywords') || '').toString().trim(),
            social: {},
        };

        for (let index = 0; index < 7; index += 1) {
            const day = (fd.get(`support_hours[${index}][day]`) || '').toString().trim();
            const from = (fd.get(`support_hours[${index}][from]`) || '').toString().trim();
            const to = (fd.get(`support_hours[${index}][to]`) || '').toString().trim();
            const timezone = (fd.get(`support_hours[${index}][timezone]`) || '').toString().trim();
            if (!day && !from && !to) {
                continue;
            }
            payload.support_hours.push({ day, from, to, timezone: timezone || 'Asia/Kolkata' });
        }

        (config.platforms || []).forEach((platform) => {
            const url = (fd.get(`social[${platform}][url]`) || '').toString().trim();
            const visible = form.querySelector(`[name="social[${platform}][is_visible]"]`)?.checked;
            payload.social[platform] = {
                url,
                is_visible: Boolean(visible),
            };
        });

        return payload;
    };

    const isValidHttpUrl = (value) => {
        try {
            const parsed = new URL(value);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch (error) {
            return false;
        }
    };

    const normalizeApplicationUrl = (value) => {
        const raw = (value || '').toString().trim();
        if (!raw) return '';
        const withScheme = /^https?:\/\//i.test(raw) ? raw : (`https://${raw.replace(/^\/+/, '')}`);
        try {
            const parsed = new URL(withScheme);
            if (!['http:', 'https:'].includes(parsed.protocol) || !parsed.hostname) {
                return '';
            }
            const path = parsed.pathname === '/' ? '' : parsed.pathname.replace(/\/$/, '');
            return `${parsed.protocol}//${parsed.host}${path}`;
        } catch (error) {
            return '';
        }
    };

    const validateApplicationUrl = () => {
        const input = form.querySelector('#application_url');
        const raw = (input?.value || '').toString().trim();
        if (!raw) {
            return null;
        }
        const normalized = normalizeApplicationUrl(raw);
        if (!normalized || !isValidHttpUrl(normalized)) {
            showFieldError('application_url', 'Enter a valid domain or URL (e.g. examtube.in or https://examtube.in).');
            return input;
        }
        if (input) {
            input.value = normalized;
        }
        return null;
    };

    const validateSupportHours = () => {
        const rows = [];
        for (let index = 0; index < 7; index += 1) {
            const day = form.querySelector(`[name="support_hours[${index}][day]"]`)?.value?.trim();
            const fromInput = form.querySelector(`[name="support_hours[${index}][from]"]`);
            const toInput = form.querySelector(`[name="support_hours[${index}][to]"]`);
            const from = (fromInput?.value || '').toString().trim();
            const to = (toInput?.value || '').toString().trim();
            const timezone = form.querySelector(`[name="support_hours[${index}][timezone]"]`)?.value?.trim();
            if (!day && !from && !to) continue;
            rows.push({ index, day, from, to, timezone, fromInput, toInput });
        }

        if (!rows.length) {
            showFieldError('support_hours', 'Add at least one support-hours day.');
            return form.querySelector('#org-support-hours');
        }
        if (rows.length > 7) {
            showFieldError('support_hours', 'You can add a maximum of 7 days.');
            return form.querySelector('#org-support-hours');
        }

        let firstInvalid = null;
        const toMinutes = (value) => {
            const match = String(value || '').trim().match(/^(\d{1,2}):(\d{2})(?:\s*([AaPp][Mm]))?$/);
            if (!match) return null;
            let hour = Number(match[1]);
            const minute = Number(match[2]);
            const meridiem = match[3] ? match[3].toUpperCase() : null;
            if (meridiem) {
                if (hour < 1 || hour > 12 || minute > 59) return null;
                if (meridiem === 'AM') hour = hour === 12 ? 0 : hour;
                else hour = hour === 12 ? 12 : hour + 12;
            } else if (hour > 23 || minute > 59) {
                return null;
            }
            return (hour * 60) + minute;
        };

        rows.forEach((row) => {
            const markTimeInvalid = (input) => {
                input?.classList?.add('is-invalid');
                input?._flatpickr?.altInput?.classList?.add('is-invalid');
            };
            const focusTarget = (input) => input?._flatpickr?.altInput || input;

            const fromMins = toMinutes(row.from);
            const toMins = toMinutes(row.to);
            if (fromMins === null) {
                showFieldError(`support_hours.${row.index}.from`, 'Enter a valid start time.');
                markTimeInvalid(row.fromInput);
                firstInvalid = firstInvalid || focusTarget(row.fromInput);
            }
            if (toMins === null) {
                showFieldError(`support_hours.${row.index}.to`, 'Enter a valid end time.');
                markTimeInvalid(row.toInput);
                firstInvalid = firstInvalid || focusTarget(row.toInput);
            }
            if (fromMins !== null && toMins !== null && fromMins >= toMins) {
                showFieldError(`support_hours.${row.index}.to`, 'End time must be after start time.');
                markTimeInvalid(row.toInput);
                firstInvalid = firstInvalid || focusTarget(row.toInput);
            }
            if (!row.day) {
                showFieldError(`support_hours.${row.index}.day`, 'Select a day.');
                firstInvalid = firstInvalid || form.querySelector(`[name="support_hours[${row.index}][day]"]`);
            }
            if (!row.timezone) {
                showFieldError(`support_hours.${row.index}.timezone`, 'Select a timezone.');
                firstInvalid = firstInvalid || form.querySelector(`[name="support_hours[${row.index}][timezone]"]`);
            }
        });

        return firstInvalid;
    };

    const validateSocialUrls = () => {
        let firstInvalid = null;
        (config.platforms || []).forEach((platform) => {
            const input = form.querySelector(`[name="social[${platform}][url]"]`);
            const url = (input?.value || '').toString().trim();
            if (url === '') {
                return;
            }
            if (!isValidHttpUrl(url)) {
                showFieldError(`social.${platform}.url`, 'Enter a valid URL starting with http:// or https://.');
                if (!firstInvalid) {
                    firstInvalid = input;
                }
            }
        });
        return firstInvalid;
    };

    const bindSocialFieldUx = () => {
        form.querySelectorAll('[data-social-url]').forEach((input) => {
            const platform = input.getAttribute('data-social-url');
            const row = input.closest('[data-social-platform]');
            const toggle = row?.querySelector(`[name="social[${platform}][is_visible]"]`);

            input.addEventListener('input', () => {
                input.classList.remove('is-invalid');
                const errorEl = form.querySelector(`[data-error-for="social.${platform}.url"]`);
                if (errorEl) {
                    errorEl.hidden = true;
                    errorEl.textContent = '';
                    errorEl.classList.remove('is-visible');
                }
                if (toggle && input.value.trim() !== '' && !toggle.checked) {
                    toggle.checked = true;
                }
            });

            input.addEventListener('blur', () => {
                const url = input.value.trim();
                if (url === '') {
                    return;
                }
                if (!isValidHttpUrl(url)) {
                    showFieldError(`social.${platform}.url`, 'Enter a valid URL starting with http:// or https://.');
                }
            });
        });
    };

    bindSocialFieldUx();

    const appUrlInput = form.querySelector('#application_url');
    appUrlInput?.addEventListener('blur', () => {
        const raw = (appUrlInput.value || '').toString().trim();
        if (!raw) {
            return;
        }
        const normalized = normalizeApplicationUrl(raw);
        if (normalized) {
            appUrlInput.value = normalized.replace(/^https?:\/\//i, '');
            appUrlInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();

        if (!form.querySelector('#site_name')?.value?.trim()) {
            showFieldError('site_name', 'Organization name is required.');
            window.Swal?.fire?.({ icon: 'warning', title: 'Check the form', text: 'Organization name is required.' });
            return;
        }

        const invalidAppUrl = validateApplicationUrl();
        if (invalidAppUrl) {
            invalidAppUrl.focus();
            window.Swal?.fire?.({
                icon: 'warning',
                title: 'Check application URL',
                text: 'Enter a valid domain or full URL, for example examtube.in.',
            });
            return;
        }

        const invalidHours = validateSupportHours();
        if (invalidHours) {
            invalidHours.focus?.();
            window.Swal?.fire?.({
                icon: 'warning',
                title: 'Check support hours',
                text: 'Each day needs a valid from/to time, and end time must be after start time. Add 1–7 days.',
            });
            return;
        }

        const invalidSocial = validateSocialUrls();
        if (invalidSocial) {
            invalidSocial.focus();
            window.Swal?.fire?.({
                icon: 'warning',
                title: 'Check social URLs',
                text: 'One or more social profile links are invalid. Use a full http:// or https:// URL.',
            });
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
                body: JSON.stringify(collectPayload()),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                if (data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        showFieldError(field, Array.isArray(messages) ? messages[0] : String(messages));
                    });
                }
                throw new Error(data.message || 'Could not save organization settings.');
            }
            window.EmsToast?.success?.(data.message || 'Saved.');
            window.Swal?.fire?.({ icon: 'success', title: 'Saved', text: data.message || 'Organization settings updated.', timer: 1800, showConfirmButton: false });
        } catch (error) {
            window.Swal?.fire?.({ icon: 'error', title: 'Save failed', text: error.message || 'Could not save settings.' });
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = original || 'Save organization settings';
            }
        }
    });

    const openHeroModal = (hero = null) => {
        if (!heroModal || !heroForm) return;
        heroForm.reset();
        heroForm.querySelector('#hero_id').value = hero?.id || '';
        heroForm.querySelector('#hero_title').value = hero?.title || '';
        heroForm.querySelector('#hero_subtitle').value = hero?.subtitle || '';
        heroForm.querySelector('#hero_description').value = hero?.description || '';
        heroForm.querySelector('#hero_badge_text').value = hero?.badge_text || '';
        heroForm.querySelector('#hero_primary_cta_label').value = hero?.primary_cta_label || '';
        heroForm.querySelector('#hero_primary_cta_url').value = hero?.primary_cta_url || '';
        heroForm.querySelector('#hero_secondary_cta_label').value = hero?.secondary_cta_label || '';
        heroForm.querySelector('#hero_secondary_cta_url').value = hero?.secondary_cta_url || '';
        heroForm.querySelector('#hero_status').value = hero?.status || 'active';
        heroForm.querySelector('#hero_sort_order').value = hero?.sort_order ?? 1;
        heroForm.querySelector('#hero_show_search').checked = hero?.show_search !== false;
        document.getElementById('hero-modal-title').textContent = hero?.id ? 'Edit hero banner' : 'Add hero banner';

        const setDt = (id, value) => {
            if (window.EmsDateTimePicker?.setValue) {
                window.EmsDateTimePicker.setValue(id, value || '');
            } else {
                const input = heroForm.querySelector('#' + id);
                if (input) input.value = value || '';
            }
        };
        setDt('hero_starts_at', hero?.starts_at || '');
        setDt('hero_ends_at', hero?.ends_at || '');

        const setPicker = (name, id, url) => {
            const field = heroForm.querySelector(`[data-gallery-picker][data-name="${name}"]`);
            if (!field) return;
            const hidden = field.querySelector('input[type="hidden"]');
            const preview = field.querySelector('[data-gallery-preview]');
            const empty = field.querySelector('[data-gallery-empty]');
            if (hidden) hidden.value = id || '';
            if (preview) {
                preview.innerHTML = id
                    ? `<div class="gallery-picker-thumb is-selected" data-id="${id}"><img src="${url || ''}" alt="" class="gallery-picker-thumb__img ${url ? '' : 'hidden'}"></div>`
                    : '';
            }
            if (empty) empty.hidden = Boolean(id);
            field.querySelector('.gallery-picker-clear')?.toggleAttribute('hidden', !id);
        };

        setPicker('image_id', hero?.image_id, hero?.image_url);
        setPicker('mobile_image_id', hero?.mobile_image_id, hero?.mobile_image_url);

        heroModal.classList.remove('hidden');
        heroModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ems-dialog-open');
        window.EmsDateTimePicker?.initAll?.(heroModal);
    };

    const closeHeroModal = () => {
        heroModal?.classList.add('hidden');
        heroModal?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ems-dialog-open');
    };

    const renderHeroes = (heroes) => {
        if (!heroList) return;
        if (!heroes?.length) {
            heroList.innerHTML = '<p class="text-sm text-slate-500 dark:text-slate-400" id="hero-empty">No hero banners yet. Add your first slide.</p>';
            return;
        }

        heroList.innerHTML = heroes.map((hero) => `
            <div class="hero-row rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex flex-col sm:flex-row sm:items-center gap-3 justify-between" data-hero-id="${hero.id}">
                <div class="min-w-0 flex items-start gap-3">
                    ${hero.image_url
                        ? `<img src="${hero.image_url}" alt="" class="h-14 w-20 rounded-lg object-cover shrink-0">`
                        : '<div class="h-14 w-20 rounded-lg bg-slate-100 dark:bg-slate-800 shrink-0"></div>'}
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900 dark:text-white truncate">${hero.title || ''}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Order ${hero.sort_order} · ${(hero.status || '').charAt(0).toUpperCase()}${(hero.status || '').slice(1)}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="panel-button-secondary text-sm hero-edit-btn">Edit</button>
                    <button type="button" class="panel-button-secondary text-sm text-red-600 hero-delete-btn" data-id="${hero.id}">Delete</button>
                </div>
            </div>
        `).join('');

        heroList.querySelectorAll('.hero-row').forEach((row, index) => {
            const hero = heroes[index];
            row.querySelector('.hero-edit-btn')?.addEventListener('click', () => openHeroModal(hero));
        });
        bindHeroDeleteButtons();
    };

    const bindHeroDeleteButtons = () => {
        heroList?.querySelectorAll('.hero-delete-btn').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const confirm = await window.Swal?.fire?.({
                    icon: 'warning',
                    title: 'Delete this banner?',
                    text: 'This removes the hero slide from the homepage.',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    confirmButtonColor: '#dc2626',
                });
                if (confirm && !confirm.isConfirmed) return;

                try {
                    const response = await fetch(`${config.heroDeleteUrl}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': config.csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(data.message || 'Delete failed.');
                    renderHeroes(data.heroes || []);
                    window.EmsToast?.success?.(data.message || 'Deleted.');
                } catch (error) {
                    window.Swal?.fire?.({ icon: 'error', title: 'Delete failed', text: error.message || 'Could not delete banner.' });
                }
            });
        });
    };

    document.getElementById('hero-add-btn')?.addEventListener('click', () => openHeroModal(null));
    heroModal?.querySelectorAll('[data-hero-modal-close]').forEach((el) => el.addEventListener('click', closeHeroModal));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && heroModal && !heroModal.classList.contains('hidden')) {
            closeHeroModal();
        }
    });

    document.querySelectorAll('.hero-edit-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            try {
                openHeroModal(JSON.parse(btn.getAttribute('data-hero') || '{}'));
            } catch {
                openHeroModal(null);
            }
        });
    });
    bindHeroDeleteButtons();

    heroForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const title = heroForm.querySelector('#hero_title')?.value?.trim();
        if (!title) {
            window.Swal?.fire?.({ icon: 'warning', title: 'Title required', text: 'Please enter a hero title.' });
            return;
        }

        const id = heroForm.querySelector('#hero_id')?.value;
        const payload = {
            title,
            subtitle: heroForm.querySelector('#hero_subtitle')?.value?.trim() || '',
            description: heroForm.querySelector('#hero_description')?.value?.trim() || '',
            badge_text: heroForm.querySelector('#hero_badge_text')?.value?.trim() || '',
            primary_cta_label: heroForm.querySelector('#hero_primary_cta_label')?.value?.trim() || '',
            primary_cta_url: heroForm.querySelector('#hero_primary_cta_url')?.value?.trim() || '',
            secondary_cta_label: heroForm.querySelector('#hero_secondary_cta_label')?.value?.trim() || '',
            secondary_cta_url: heroForm.querySelector('#hero_secondary_cta_url')?.value?.trim() || '',
            image_id: heroForm.querySelector('[data-gallery-picker][data-name="image_id"] input[type="hidden"]')?.value || null,
            mobile_image_id: heroForm.querySelector('[data-gallery-picker][data-name="mobile_image_id"] input[type="hidden"]')?.value || null,
            theme: 'emerald',
            show_search: Boolean(heroForm.querySelector('#hero_show_search')?.checked),
            sort_order: Number(heroForm.querySelector('#hero_sort_order')?.value || 1),
            status: heroForm.querySelector('#hero_status')?.value || 'active',
            starts_at: heroForm.querySelector('#hero_starts_at')?.value || null,
            ends_at: heroForm.querySelector('#hero_ends_at')?.value || null,
        };

        const saveHeroBtn = document.getElementById('hero-save-btn');
        const original = saveHeroBtn?.textContent;
        if (saveHeroBtn) {
            saveHeroBtn.disabled = true;
            saveHeroBtn.textContent = 'Saving…';
        }

        try {
            const url = id ? `${config.heroUpdateUrl}/${id}` : config.heroStoreUrl;
            const method = id ? 'PUT' : 'POST';
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Could not save banner.');
            renderHeroes(data.heroes || []);
            closeHeroModal();
            window.EmsToast?.success?.(data.message || 'Banner saved.');
        } catch (error) {
            window.Swal?.fire?.({ icon: 'error', title: 'Save failed', text: error.message || 'Could not save banner.' });
        } finally {
            if (saveHeroBtn) {
                saveHeroBtn.disabled = false;
                saveHeroBtn.textContent = original || 'Save banner';
            }
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        if (window.EmsContentForm?.initGalleryPickers) {
            window.EmsContentForm.initGalleryPickers({});
        }
    });
}());
