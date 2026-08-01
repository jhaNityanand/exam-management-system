/**
 * Candidate profile page — AJAX load + save.
 */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    function setVal(id, value) {
        var el = document.getElementById(id);
        if (el) el.value = value == null ? '' : value;
    }

    function setAvatarPreview(url, initials) {
        var box = document.getElementById('ca-avatar-preview');
        if (!box) return;
        if (url) {
            box.innerHTML = '<img src="' + url + '" alt="Profile photo">';
        } else {
            box.innerHTML = '<span id="ca-avatar-initials">' + (initials || 'U') + '</span>';
        }
    }

    function nameInitials(name) {
        if (window.EmsUserAvatar && typeof window.EmsUserAvatar.initials === 'function') {
            return window.EmsUserAvatar.initials(name, 'U');
        }
        var cleaned = String(name || 'U').replace(/\s+/g, ' ').trim();
        var parts = cleaned.split(' ').filter(Boolean);
        if (parts.length >= 2) {
            return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
        }
        return cleaned.slice(0, 2).toUpperCase() || 'U';
    }

    function renderStats(stats) {
        var host = document.getElementById('ca-profile-stats');
        if (!host) return;
        host.innerHTML = [
            ['Attempts', stats.attempts || 0],
            ['Completed', stats.completed || 0],
            ['Passed', stats.passed || 0],
            ['Member since', stats.member_since || '—'],
        ].map(function (row) {
            return '<div class="ca-stat"><span class="ca-stat__label">' + row[0] + '</span><span class="ca-stat__value">' + row[1] + '</span></div>';
        }).join('');
    }

    function applyCompletion(completion) {
        var pct = (completion && completion.percent) || 0;
        var fill = document.getElementById('ca-completion-fill');
        var label = document.getElementById('ca-completion-pct');
        var text = document.getElementById('ca-completion-text');
        if (fill) fill.style.width = pct + '%';
        if (label) label.textContent = pct + '%';
        if (text) text.textContent = ((completion && completion.filled) || 0) + ' of ' + ((completion && completion.total) || 0) + ' fields complete';
    }

    function setDob(value) {
        var el = document.getElementById('ca-dob');
        if (!el) return;
        var next = value == null ? '' : String(value);
        if (el._dobPicker) {
            if (next) el._dobPicker.setDate(next, true);
            else el._dobPicker.clear();
            return;
        }
        el.value = next;
    }

    function fillForm(data) {
        var user = data.user || {};
        var profile = data.profile || {};
        var social = profile.social_links || {};
        setVal('ca-name', user.name);
        setVal('ca-username', user.username);
        setVal('ca-email', user.email);
        setVal('ca-phone', profile.phone);
        setDob(profile.date_of_birth);
        setVal('ca-gender', profile.gender);
        setVal('ca-bio', profile.bio);
        setVal('ca-address1', profile.address_line1);
        setVal('ca-address2', profile.address_line2);
        setVal('ca-city', profile.city);
        setVal('ca-state', profile.state_region);
        setVal('ca-postal', profile.postal_code);
        setVal('ca-country', profile.country);
        setVal('ca-website', social.website);
        setVal('ca-linkedin', social.linkedin);
        setVal('ca-twitter', social.twitter);
        setVal('ca-github', social.github);
        setVal('ca-facebook', social.facebook);
        setAvatarPreview(data.avatar_url, nameInitials(user.name || 'U'));
        applyCompletion(data.completion || {});
        renderStats(data.stats || {});
    }

    function showDobError(message) {
        var input = document.getElementById('ca-dob');
        if (!input) return;
        var field = input.closest('.ca-field');
        if (!field) return;
        field.classList.toggle('is-invalid', Boolean(message));
        var existing = field.querySelector('.ca-field__error');
        if (existing) existing.remove();
        if (!message) return;
        var err = document.createElement('div');
        err.className = 'ca-field__error';
        err.textContent = message;
        field.appendChild(err);
    }

    onReady(function () {
        var root = document.getElementById('ca-profile');
        if (!root || !window.CaAccount) return;

        var form = document.getElementById('ca-profile-form');
        var alertEl = document.getElementById('ca-profile-alert');
        var skeleton = document.getElementById('ca-profile-skeleton');
        var content = document.getElementById('ca-profile-content');
        var saveBtn = document.getElementById('ca-profile-save');

        var pickerReady = window.DobDatePicker
            ? window.DobDatePicker.initAll(root)
            : Promise.resolve([]);

        pickerReady
            .then(function () {
                return window.CaAccount.fetchJson(root.getAttribute('data-url'));
            })
            .then(function (data) {
                if (skeleton) skeleton.remove();
                if (content) {
                    content.hidden = false;
                    content.removeAttribute('hidden');
                }
                fillForm(data);
            })
            .catch(function (err) {
                if (skeleton) skeleton.remove();
                window.CaAccount.showAlert(alertEl, 'error', err.message || 'Unable to load profile.');
            });

        var fileInput = document.getElementById('ca-avatar-input');
        var cropModal = document.getElementById('ca-avatar-crop-modal');
        var cropImage = document.getElementById('ca-avatar-crop-image');
        var cropper = null;
        var objectUrl = null;

        function closeCropModal() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            if (cropModal) {
                cropModal.hidden = true;
                cropModal.setAttribute('aria-hidden', 'true');
            }
            document.body.style.overflow = '';
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }
            if (fileInput) fileInput.value = '';
            if (cropImage) {
                cropImage.removeAttribute('src');
                cropImage.classList.remove('ca-crop-fallback-image');
            }
        }

        function openCropModal(file) {
            if (!cropModal || !cropImage) return;
            objectUrl = URL.createObjectURL(file);
            cropImage.src = objectUrl;
            cropModal.hidden = false;
            cropModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            var applyFocus = cropModal.querySelector('[data-ca-crop-apply]');
            window.setTimeout(function () {
                (applyFocus || cropModal.querySelector('button'))?.focus?.();
            }, 40);

            var initializeCropper = function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                if (!window.Cropper) {
                    cropImage.classList.add('ca-crop-fallback-image');
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
                    rotatable: true,
                    scalable: false,
                });
            };

            if (cropImage.complete) initializeCropper();
            else cropImage.addEventListener('load', initializeCropper, { once: true });
        }

        function applyCroppedAvatar() {
            var canvas;
            if (cropper) {
                canvas = cropper.getCroppedCanvas({
                    width: 512,
                    height: 512,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });
            } else if (cropImage && cropImage.naturalWidth) {
                canvas = document.createElement('canvas');
                canvas.width = 512;
                canvas.height = 512;
                var ctx = canvas.getContext('2d');
                var size = Math.min(cropImage.naturalWidth, cropImage.naturalHeight);
                var sx = (cropImage.naturalWidth - size) / 2;
                var sy = (cropImage.naturalHeight - size) / 2;
                ctx.drawImage(cropImage, sx, sy, size, size, 0, 0, 512, 512);
            }

            if (!canvas) {
                window.CaAccount.showAlert(alertEl, 'error', 'Unable to crop image. Please try another photo.');
                return;
            }

            var dataUrl = canvas.toDataURL('image/jpeg', 0.92);
            document.getElementById('ca-cropped-avatar').value = dataUrl;
            document.getElementById('ca-remove-avatar').value = '0';
            setAvatarPreview(dataUrl);
            closeCropModal();
        }

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                var file = fileInput.files && fileInput.files[0];
                if (!file) return;
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                    window.CaAccount.showAlert(alertEl, 'error', 'Choose a JPG, PNG, or WebP image.');
                    fileInput.value = '';
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    window.CaAccount.showAlert(alertEl, 'error', 'Image must be smaller than 2MB.');
                    fileInput.value = '';
                    return;
                }
                openCropModal(file);
            });
        }

        if (cropModal) {
            cropModal.querySelectorAll('[data-ca-crop-close]').forEach(function (btn) {
                btn.addEventListener('click', closeCropModal);
            });
            var applyBtn = cropModal.querySelector('[data-ca-crop-apply]');
            if (applyBtn) applyBtn.addEventListener('click', applyCroppedAvatar);

            cropModal.querySelectorAll('[data-ca-crop-zoom]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!cropper) return;
                    cropper.zoom(parseFloat(btn.getAttribute('data-ca-crop-zoom') || '0'));
                });
            });
            cropModal.querySelectorAll('[data-ca-crop-rotate]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!cropper) return;
                    cropper.rotate(parseFloat(btn.getAttribute('data-ca-crop-rotate') || '0'));
                });
            });
            var resetBtn = cropModal.querySelector('[data-ca-crop-reset]');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    if (cropper) cropper.reset();
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && cropModal && !cropModal.hidden) {
                    closeCropModal();
                }
            });
        }

        var removeBtn = document.getElementById('ca-avatar-remove');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                document.getElementById('ca-cropped-avatar').value = '';
                document.getElementById('ca-remove-avatar').value = '1';
                setAvatarPreview(null, nameInitials((document.getElementById('ca-name') || {}).value || 'U'));
            });
        }

        if (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                window.CaAccount.clearFieldErrors(form);
                showDobError('');

                var dobValue = (document.getElementById('ca-dob') || {}).value || '';
                var dobMessage = window.DobDatePicker
                    ? window.DobDatePicker.validate(dobValue)
                    : '';
                if (dobMessage) {
                    showDobError(dobMessage);
                    window.CaAccount.showAlert(alertEl, 'error', dobMessage);
                    return;
                }

                window.CaAccount.setButtonLoading(saveBtn, true);
                try {
                    var fd = new FormData(form);
                    var payload = {};
                    fd.forEach(function (value, key) {
                        if (key.indexOf('social_links[') === 0) {
                            payload.social_links = payload.social_links || {};
                            var k = key.slice('social_links['.length, -1);
                            payload.social_links[k] = value;
                        } else if (key === 'remove_avatar') {
                            payload.remove_avatar = value === '1' || value === 'true';
                        } else {
                            payload[key] = value;
                        }
                    });
                    var data = await window.CaAccount.fetchJson(root.getAttribute('data-save-url'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                    window.CaAccount.showAlert(alertEl, 'success', data.message || 'Profile saved.');
                    applyCompletion(data.completion || {});
                    if (data.avatar_url) setAvatarPreview(data.avatar_url);
                    document.getElementById('ca-cropped-avatar').value = '';
                    document.getElementById('ca-remove-avatar').value = '0';
                } catch (err) {
                    window.CaAccount.showAlert(alertEl, 'error', err.message || 'Unable to save profile.');
                    window.CaAccount.applyFieldErrors(form, err.errors || {});
                } finally {
                    window.CaAccount.setButtonLoading(saveBtn, false);
                }
            });
        }
    });
})();
