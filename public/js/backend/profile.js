(function () {
    'use strict';

    const form = document.querySelector('[data-profile-form]');
    if (!form) return;

    const byId = (id) => document.getElementById(id);
    const errorFor = (field) => document.querySelector(`[data-error-for="${field.id}"]`);
    const hostFor = (field) => field.closest('[data-field-host]');

    const setError = (field, message) => {
        const host = hostFor(field);
        const error = errorFor(field);
        host?.classList.toggle('is-invalid', Boolean(message));
        field.setAttribute('aria-invalid', message ? 'true' : 'false');
        if (error) {
            error.textContent = message || '';
        }
    };

    const validators = {
        name(value) {
            const cleaned = value.trim();
            if (!cleaned) return 'Please enter your full name.';
            if (cleaned.length < 2) return 'Your name must contain at least 2 characters.';
            if (cleaned.length > 255) return 'Your name cannot exceed 255 characters.';
            return '';
        },
        email(value) {
            const cleaned = value.trim();
            if (!cleaned) return 'Please enter your email address.';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(cleaned)) {
                return 'Enter a valid email address, such as name@example.com.';
            }
            return '';
        },
        phone(value) {
            const cleaned = value.trim();
            if (!cleaned) return '';
            if (!/^\+?[\d\s().-]{7,30}$/.test(cleaned)) {
                return 'Enter a valid phone number with 7–30 digits or separators.';
            }
            return '';
        },
        url(value) {
            const cleaned = value.trim();
            if (!cleaned) return '';
            try {
                const parsed = new URL(cleaned);
                if (!['http:', 'https:'].includes(parsed.protocol)) throw new Error('Invalid protocol');
                return '';
            } catch (_) {
                return 'Enter a complete URL beginning with http:// or https://.';
            }
        },
    };

    const validateField = (field) => {
        const validator = validators[field.dataset.validate];
        if (!validator) return true;
        const message = validator(field.value);
        setError(field, message);
        return !message;
    };

    form.querySelectorAll('[data-validate]').forEach((field) => {
        field.addEventListener('blur', () => validateField(field));
        field.addEventListener('input', () => {
            if (hostFor(field)?.classList.contains('is-invalid')) {
                validateField(field);
            }
        });
    });

    // Mark server-side errors and switch to their section.
    const initialError = [...form.querySelectorAll('.profile-field-error')]
        .find((element) => element.textContent.trim() !== '');
    if (initialError) {
        initialError.closest('[data-field-host]')?.classList.add('is-invalid');
        const fieldId = initialError.dataset.errorFor;
        const field = fieldId ? byId(fieldId) : null;
        if (field?.dataset.section) {
            window.dispatchEvent(new CustomEvent('profile-tab', { detail: field.dataset.section }));
        }
    }

    const bio = byId('bio');
    const bioCount = document.querySelector('[data-bio-count]');
    const syncBioCount = () => {
        if (bioCount) bioCount.textContent = `${bio?.value.length || 0} / 2000`;
    };
    bio?.addEventListener('input', syncBioCount);
    syncBioCount();

    form.addEventListener('submit', (event) => {
        const fields = [...form.querySelectorAll('[data-validate]')];
        const firstInvalid = fields.find((field) => !validateField(field));

        if (firstInvalid) {
            event.preventDefault();
            window.dispatchEvent(new CustomEvent('profile-tab', {
                detail: firstInvalid.dataset.section || 'general',
            }));
            window.setTimeout(() => {
                firstInvalid.focus({ preventScroll: true });
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 80);
            return;
        }

        const submit = form.querySelector('[data-profile-submit]');
        if (submit) {
            submit.disabled = true;
            submit.innerHTML = `
                <svg class="profile-submit-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M12 3a9 9 0 1 1-8.46 5.92"/>
                </svg>
                Saving…
            `;
        }
    });

    const passwordForm = document.querySelector('[data-password-form]');
    if (passwordForm) {
        const passwordFields = {
            current: passwordForm.querySelector('[data-password-field="current"]'),
            new: passwordForm.querySelector('[data-password-field="new"]'),
            confirmation: passwordForm.querySelector('[data-password-field="confirmation"]'),
        };

        const setPasswordError = (key, message) => {
            const field = passwordFields[key];
            const host = field?.closest('[data-password-host]');
            const error = passwordForm.querySelector(`[data-password-error="${key}"]`);
            host?.classList.toggle('is-invalid', Boolean(message));
            field?.setAttribute('aria-invalid', message ? 'true' : 'false');
            if (error) error.textContent = message || '';
        };

        const validatePasswordField = (key) => {
            const value = passwordFields[key]?.value || '';
            let message = '';
            if (key === 'current' && !value) {
                message = 'Enter your current password.';
            }
            if (key === 'new') {
                if (!value) {
                    message = 'Enter a new password.';
                } else if (value.length < 8) {
                    message = 'Your new password must contain at least 8 characters.';
                } else if (!/[a-z]/.test(value) || !/[A-Z]/.test(value) || !/\d/.test(value)) {
                    message = 'Include uppercase, lowercase, and at least one number.';
                }
            }
            if (key === 'confirmation') {
                if (!value) {
                    message = 'Confirm your new password.';
                } else if (value !== passwordFields.new?.value) {
                    message = 'The password confirmation does not match.';
                }
            }
            setPasswordError(key, message);
            return !message;
        };

        Object.entries(passwordFields).forEach(([key, field]) => {
            field?.addEventListener('blur', () => validatePasswordField(key));
            field?.addEventListener('input', () => {
                if (field.closest('[data-password-host]')?.classList.contains('is-invalid')) {
                    validatePasswordField(key);
                }
                if (key === 'new' && passwordFields.confirmation?.value) {
                    validatePasswordField('confirmation');
                }
            });
        });

        passwordForm.addEventListener('submit', (event) => {
            const firstInvalidKey = Object.keys(passwordFields)
                .find((key) => !validatePasswordField(key));
            if (!firstInvalidKey) return;

            event.preventDefault();
            passwordFields[firstInvalidKey]?.focus();
        });
    }

    // Avatar crop / edit workflow.
    const input = byId('avatar_input');
    const modal = byId('avatar-crop-modal');
    const cropImage = byId('avatar-crop-image');
    const croppedInput = byId('cropped_avatar');
    const removeInput = byId('remove_avatar');
    const previewImage = document.querySelector('[data-avatar-image]');
    const previewInitials = document.querySelector('[data-avatar-initials]');
    const removeButton = document.querySelector('[data-avatar-remove]');
    const avatarError = document.querySelector('[data-error-for="avatar_input"]');
    let cropper = null;
    let objectUrl = null;

    const showAvatarError = (message) => {
        if (avatarError) avatarError.textContent = message || '';
    };

    const closeCropModal = () => {
        cropper?.destroy();
        cropper = null;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        if (input) input.value = '';
    };

    const openCropModal = (file) => {
        objectUrl = URL.createObjectURL(file);
        cropImage.src = objectUrl;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        const initializeCropper = () => {
            if (!window.Cropper) {
                // Keep upload functional if the optional CropperJS CDN is unavailable.
                cropImage.classList.add('profile-crop-fallback-image');
                return;
            }
            cropper = new window.Cropper(cropImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.92,
                background: false,
                responsive: true,
                restore: false,
                guides: true,
                center: true,
                movable: true,
                zoomable: true,
                rotatable: false,
                scalable: false,
            });
        };

        if (cropImage.complete) {
            initializeCropper();
        } else {
            cropImage.addEventListener('load', initializeCropper, { once: true });
        }
    };

    input?.addEventListener('change', () => {
        showAvatarError('');
        const file = input.files?.[0];
        if (!file) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            showAvatarError('Choose a JPG, PNG, or WebP image.');
            input.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            showAvatarError('Profile photos must be 2 MB or smaller.');
            input.value = '';
            return;
        }

        openCropModal(file);
    });

    modal?.querySelectorAll('[data-crop-close]').forEach((button) => {
        button.addEventListener('click', closeCropModal);
    });

    modal?.querySelector('[data-crop-apply]')?.addEventListener('click', () => {
        let canvas;
        if (cropper) {
            canvas = cropper.getCroppedCanvas({
                width: 512,
                height: 512,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
                fillColor: '#ffffff',
            });
        } else {
            const sourceSize = Math.min(cropImage.naturalWidth, cropImage.naturalHeight);
            if (!sourceSize) {
                showAvatarError('The selected image could not be processed.');
                closeCropModal();
                return;
            }
            const sourceX = (cropImage.naturalWidth - sourceSize) / 2;
            const sourceY = (cropImage.naturalHeight - sourceSize) / 2;
            canvas = document.createElement('canvas');
            canvas.width = 512;
            canvas.height = 512;
            const context = canvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, 512, 512);
            context.drawImage(
                cropImage,
                sourceX,
                sourceY,
                sourceSize,
                sourceSize,
                0,
                0,
                512,
                512
            );
        }
        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
        croppedInput.value = dataUrl;
        removeInput.value = '0';
        previewImage.src = dataUrl;
        previewImage.hidden = false;
        previewInitials.hidden = true;
        removeButton.hidden = false;
        showAvatarError('');
        closeCropModal();
    });

    removeButton?.addEventListener('click', () => {
        croppedInput.value = '';
        removeInput.value = '1';
        previewImage.src = '';
        previewImage.hidden = true;
        previewInitials.hidden = false;
        removeButton.hidden = true;
        showAvatarError('');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            closeCropModal();
        }
    });
}());
