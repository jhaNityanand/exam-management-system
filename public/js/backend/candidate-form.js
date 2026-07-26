/**
 * Candidate create / edit form — client validation + avatar preview.
 */
(function () {
    'use strict';

    const config = window.candidateFormConfig || { isEdit: false, initials: 'C', avatarColor: '#4f46e5' };

    function onReady(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    function initialsFrom(name) {
        if (window.EmsUserAvatar && typeof window.EmsUserAvatar.initials === 'function') {
            return window.EmsUserAvatar.initials(name, config.initials || 'C');
        }
        return String(name || config.initials || 'C').replace(/\s+/g, ' ').trim().slice(0, 2).toUpperCase() || 'C';
    }

    function showError(fieldId, message) {
        const input = document.getElementById(fieldId);
        const err = document.getElementById('err-' + fieldId);
        if (input) input.classList.add('is-invalid');
        if (err) {
            err.textContent = message || '';
            err.classList.toggle('is-visible', Boolean(message));
        }
    }

    function clearError(fieldId) {
        const input = document.getElementById(fieldId);
        const err = document.getElementById('err-' + fieldId);
        if (input) input.classList.remove('is-invalid');
        if (err) {
            err.textContent = '';
            err.classList.remove('is-visible');
        }
    }

    function clearAllErrors(form) {
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('.qcat-field-error').forEach((el) => {
            if (el.id && el.id.startsWith('err-')) {
                el.textContent = '';
                el.classList.remove('is-visible');
            }
        });
    }

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function fileToDataUrl(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    function setAvatarPreview(url, initials) {
        const box = document.getElementById('candidate-avatar-preview');
        if (!box) return;
        if (url) {
            box.style.background = '';
            box.innerHTML = '<img src="' + url + '" alt="Profile photo">';
        } else {
            box.style.background = config.avatarColor || '#4f46e5';
            box.style.color = '#fff';
            box.innerHTML = '<span id="candidate-avatar-initials">' + (initials || config.initials || 'C') + '</span>';
        }
    }

    function validate(form) {
        clearAllErrors(form);
        let valid = true;

        const name = (form.querySelector('#name')?.value || '').trim();
        const email = (form.querySelector('#email')?.value || '').trim();
        const username = (form.querySelector('#username')?.value || '').trim();
        const password = form.querySelector('#password')?.value || '';
        const passwordConfirmation = form.querySelector('#password_confirmation')?.value || '';
        const status = form.querySelector('#status')?.value || '';
        const dob = form.querySelector('#date_of_birth')?.value || '';

        if (!name) {
            showError('name', 'Full name is required.');
            valid = false;
        }

        if (!email) {
            showError('email', 'Email address is required.');
            valid = false;
        } else if (!isValidEmail(email)) {
            showError('email', 'Enter a valid email address.');
            valid = false;
        }

        if (username && !/^[A-Za-z0-9_-]+$/.test(username)) {
            showError('username', 'Username may only contain letters, numbers, dashes, and underscores.');
            valid = false;
        }

        if (!status) {
            showError('status', 'Status is required.');
            valid = false;
        }

        if (!config.isEdit) {
            if (!password) {
                showError('password', 'Password is required.');
                valid = false;
            } else if (password.length < 8) {
                showError('password', 'Password must be at least 8 characters.');
                valid = false;
            }
            if (password !== passwordConfirmation) {
                showError('password_confirmation', 'Password confirmation does not match.');
                valid = false;
            }
        } else if (password || passwordConfirmation) {
            // Only validate password on edit when the user intentionally filled either field.
            if (password.length < 8) {
                showError('password', 'Password must be at least 8 characters.');
                valid = false;
            }
            if (password !== passwordConfirmation) {
                showError('password_confirmation', 'Password confirmation does not match.');
                valid = false;
            }
        }

        if (dob && window.DobDatePicker) {
            const dobMessage = window.DobDatePicker.validate(dob);
            if (dobMessage) {
                showError('date_of_birth', dobMessage);
                valid = false;
            }
        }

        return valid;
    }

    onReady(function () {
        const form = document.getElementById('candidate-form');
        if (!form) return;

        if (window.DobDatePicker) {
            window.DobDatePicker.initAll(form);
        }

        const fileInput = document.getElementById('candidate-avatar-input');
        const removeBtn = document.getElementById('candidate-avatar-remove');
        const cropped = document.getElementById('cropped_avatar');
        const removeFlag = document.getElementById('remove_avatar');
        const nameInput = document.getElementById('name');
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('password_confirmation');

        // Prevent browser autofill from blocking edit submits / avatar saves.
        if (config.isEdit) {
            if (passwordInput) passwordInput.value = '';
            if (passwordConfirmInput) passwordConfirmInput.value = '';
        }

        // Keep initials preview in sync when typing a name (no photo case).
        nameInput?.addEventListener('input', function () {
            if (document.querySelector('#candidate-avatar-preview img')) {
                return;
            }
            setAvatarPreview(null, initialsFrom(nameInput.value));
        });

        // Restore preview if validation failed with a pending crop payload.
        if (cropped?.value && String(cropped.value).startsWith('data:image/')) {
            setAvatarPreview(cropped.value);
        }
        if (fileInput) {
            fileInput.addEventListener('change', async function () {
                const file = fileInput.files && fileInput.files[0];
                if (!file) return;
                clearError('cropped_avatar');

                const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (file.type && !allowed.includes(file.type)) {
                    showError('cropped_avatar', 'Choose a JPG, PNG, GIF, or WebP image.');
                    fileInput.value = '';
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    showError('cropped_avatar', 'Image must be smaller than 2MB.');
                    fileInput.value = '';
                    return;
                }
                try {
                    const dataUrl = await fileToDataUrl(file);
                    if (!cropped) {
                        showError('cropped_avatar', 'Avatar field is missing.');
                        return;
                    }
                    cropped.value = dataUrl;
                    if (removeFlag) removeFlag.value = '0';
                    setAvatarPreview(dataUrl);
                } catch (e) {
                    showError('cropped_avatar', 'Unable to read image.');
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (cropped) cropped.value = '';
                if (removeFlag) removeFlag.value = '1';
                if (fileInput) fileInput.value = '';
                setAvatarPreview(null, initialsFrom(nameInput?.value || config.initials));
            });
        }

        form.querySelectorAll('input, select, textarea').forEach((el) => {
            el.addEventListener('input', () => {
                if (el.id) clearError(el.id);
            });
            el.addEventListener('change', () => {
                if (el.id) clearError(el.id);
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Clear accidental autofilled passwords on edit when confirmation is empty.
            if (config.isEdit && passwordInput && passwordConfirmInput) {
                if (passwordInput.value && !passwordConfirmInput.value) {
                    passwordInput.value = '';
                }
            }

            if (!validate(form)) {
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) firstInvalid.focus();
                return;
            }

            const btn = document.getElementById('btn-submit');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-70');
            }
            HTMLFormElement.prototype.submit.call(form);
        });
    });
})();
