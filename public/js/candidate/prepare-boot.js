/**
 * Standalone prepare-page boot (no Vite dependency).
 * Uses fetch AJAX and always surfaces errors to the user.
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function deviceMeta() {
        var ua = navigator.userAgent || '';
        return {
            browser: ua.slice(0, 120),
            device_type: /Mobi|Android/i.test(ua) ? 'mobile' : 'desktop',
            os: (navigator.platform || 'unknown').slice(0, 100),
            screen_resolution: String(window.screen.width) + 'x' + String(window.screen.height),
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            local_time: new Date().toISOString(),
        };
    }

    function setStatus(root, perm, state) {
        const el = root.querySelector('[data-perm="' + perm + '"] .cx-status');
        if (!el) return;
        if (state === 'granted') el.textContent = 'Granted';
        else if (state === 'denied') el.textContent = 'Denied';
        else if (state === 'skipped' || state === 'optional') el.textContent = 'Optional';
        else if (state === 'info') el.textContent = 'Info';
        else el.textContent = 'Required';
        el.dataset.state = state;
    }

    async function requestMedia(needCam, needMic) {
        const result = { webcam: needCam ? 'denied' : 'skipped', microphone: needMic ? 'denied' : 'skipped', stream: null };
        if (!needCam && !needMic) return result;
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: !!needCam, audio: !!needMic });
            result.stream = stream;
            if (needCam) result.webcam = 'granted';
            if (needMic) result.microphone = 'granted';
        } catch (e) {
            // keep denied
        }
        return result;
    }

    async function requestFullscreen() {
        try {
            if (!document.fullscreenElement) {
                await document.documentElement.requestFullscreen();
            }
            return !!document.fullscreenElement;
        } catch (e) {
            return false;
        }
    }

    function firstError(data, fallback) {
        if (!data) return fallback;
        if (typeof data.message === 'string' && data.message.trim()) return data.message;
        if (data.errors) {
            for (const value of Object.values(data.errors)) {
                if (Array.isArray(value) && value[0]) return String(value[0]);
                if (typeof value === 'string') return value;
            }
        }
        return fallback;
    }

    ready(function () {
        const root = document.getElementById('cx-prepare');
        if (!root) return;

        const startUrl = root.dataset.startUrl;
        const requireWebcam = root.dataset.requireWebcam === '1';
        const requireMic = root.dataset.requireMic === '1';
        const requireFullscreen = root.dataset.requireFullscreen === '1';
        const requirePhoto = root.dataset.requirePhoto === '1';
        const suggestFullscreen = root.dataset.suggestFullscreen === '1';

        const startBtn = document.getElementById('cx-start-exam');
        const cancelBtn = document.getElementById('cx-cancel-start');
        const errorEl = document.getElementById('cx-prepare-error');
        const alertEl = document.getElementById('cx-prepare-alert');
        const loading = document.getElementById('cx-loading');
        const progress = document.getElementById('cx-progress-bar');
        const stepEl = document.getElementById('cx-loading-step');
        const preview = document.getElementById('cx-preview');

        let stream = null;
        let photoBlob = null;
        let starting = false;
        let abortController = null;

        const state = {
            webcam: requireWebcam ? 'required' : 'optional',
            microphone: requireMic ? 'required' : 'optional',
            fullscreen: requireFullscreen ? 'required' : 'optional',
        };

        setStatus(root, 'webcam', state.webcam);
        setStatus(root, 'microphone', state.microphone);
        setStatus(root, 'fullscreen', document.fullscreenElement ? 'granted' : state.fullscreen);
        setStatus(root, 'clipboard', 'info');
        if (document.fullscreenElement) state.fullscreen = 'granted';

        document.addEventListener('fullscreenchange', function () {
            if (document.fullscreenElement) {
                state.fullscreen = 'granted';
                setStatus(root, 'fullscreen', 'granted');
            } else if (requireFullscreen) {
                state.fullscreen = 'required';
                setStatus(root, 'fullscreen', 'required');
            } else {
                state.fullscreen = 'optional';
                setStatus(root, 'fullscreen', 'optional');
            }
        });

        function showAlert(message, type) {
            if (!alertEl) return;
            alertEl.hidden = false;
            alertEl.className = 'cx-alert' + (type ? ' cx-alert--' + type : '');
            alertEl.textContent = message;
        }

        function showError(message) {
            hideLoading();
            if (errorEl) {
                errorEl.hidden = false;
                errorEl.textContent = message;
            }
            showAlert(message, 'danger');
        }

        function hideLoading() {
            if (loading) {
                loading.hidden = true;
                loading.setAttribute('aria-hidden', 'true');
            }
            if (progress) progress.style.width = '0%';
            starting = false;
            if (startBtn) startBtn.disabled = false;
        }

        function showLoading(msg) {
            if (loading) {
                loading.hidden = false;
                loading.setAttribute('aria-hidden', 'false');
            }
            if (stepEl) stepEl.textContent = msg || 'Preparing your exam...';
            if (progress) progress.style.width = '15%';
        }

        // Ensure overlay never sticks on first paint (CSS display can override [hidden]).
        hideLoading();

        function setProgress(pct, msg) {
            if (progress) progress.style.width = Math.max(0, Math.min(100, pct)) + '%';
            if (stepEl && msg) stepEl.textContent = msg;
        }

        document.getElementById('cx-request-permissions')?.addEventListener('click', async function () {
            const result = await requestMedia(requireWebcam || requirePhoto, requireMic);
            stream = result.stream;
            if (requireWebcam) {
                state.webcam = result.webcam;
                setStatus(root, 'webcam', result.webcam);
            }
            if (requireMic) {
                state.microphone = result.microphone;
                setStatus(root, 'microphone', result.microphone);
            }
            if (stream && preview) {
                preview.hidden = false;
                preview.srcObject = stream;
            }
            if ((requireWebcam && result.webcam !== 'granted') || (requireMic && result.microphone !== 'granted')) {
                showError('Permission denied. Please allow access in your browser settings and try again.');
            } else {
                if (alertEl) alertEl.hidden = true;
                if (errorEl) errorEl.hidden = true;
            }
        });

        document.getElementById('cx-request-fullscreen')?.addEventListener('click', async function () {
            const ok = await requestFullscreen();
            state.fullscreen = ok ? 'granted' : (requireFullscreen ? 'denied' : 'optional');
            setStatus(root, 'fullscreen', state.fullscreen === 'granted' ? 'granted' : (requireFullscreen ? 'denied' : 'optional'));
            if (!ok && requireFullscreen) {
                showError('Fullscreen is required. Allow fullscreen and try again.');
            }
        });

        document.getElementById('cx-capture-photo')?.addEventListener('click', async function () {
            if (!stream) {
                const result = await requestMedia(true, false);
                stream = result.stream;
                if (stream && preview) {
                    preview.hidden = false;
                    preview.srcObject = stream;
                }
            }
            const canvas = document.getElementById('cx-snapshot-canvas');
            if (!canvas || !preview || !stream) {
                showError('Unable to capture photo. Allow webcam access first.');
                return;
            }
            canvas.width = preview.videoWidth || 640;
            canvas.height = preview.videoHeight || 480;
            canvas.getContext('2d').drawImage(preview, 0, 0);
            canvas.toBlob(function (blob) {
                photoBlob = blob;
                const img = document.getElementById('cx-photo-preview');
                if (img && blob) {
                    img.src = URL.createObjectURL(blob);
                    img.hidden = false;
                }
            }, 'image/jpeg', 0.9);
        });

        cancelBtn?.addEventListener('click', function () {
            if (abortController) abortController.abort();
            hideLoading();
            showAlert('Start cancelled. You can try again when ready.', 'info');
        });

        function validateReady() {
            if (requireWebcam && state.webcam !== 'granted') return 'Webcam permission is required.';
            if (requireMic && state.microphone !== 'granted') return 'Microphone permission is required.';
            if (requireFullscreen && !document.fullscreenElement) return 'Fullscreen mode is required for this proctored exam.';
            if (requirePhoto && !photoBlob) return 'Photo verification is required.';
            return null;
        }

        async function postStart(payload, isForm) {
            abortController = new AbortController();
            const timeout = window.setTimeout(function () { abortController.abort(); }, 45000);
            const headers = {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            };
            const options = {
                method: 'POST',
                headers: headers,
                credentials: 'same-origin',
                signal: abortController.signal,
                body: null,
            };
            if (isForm) {
                options.body = payload;
            } else {
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(payload);
            }

            try {
                const response = await fetch(startUrl, options);
                const contentType = response.headers.get('content-type') || '';
                let data = null;
                let rawText = '';
                if (contentType.indexOf('application/json') !== -1) {
                    data = await response.json();
                } else {
                    rawText = await response.text();
                }
                if (!response.ok) {
                    throw new Error(firstError(data, rawText
                        ? ('Unable to start exam (' + response.status + ').')
                        : ('Unable to start exam (' + response.status + ').')));
                }
                if (!data || !data.redirect) {
                    throw new Error(firstError(data, 'Exam start succeeded but no redirect was returned.'));
                }
                return data;
            } finally {
                window.clearTimeout(timeout);
            }
        }

        startBtn?.addEventListener('click', async function () {
            if (starting) return;
            if (errorEl) errorEl.hidden = true;
            if (alertEl) alertEl.hidden = true;

            if (suggestFullscreen && !document.fullscreenElement) {
                await requestFullscreen();
            }

            if (requireFullscreen && !document.fullscreenElement) {
                const ok = await requestFullscreen();
                if (!ok) {
                    showError('Fullscreen mode is required for this proctored exam.');
                    return;
                }
                state.fullscreen = 'granted';
                setStatus(root, 'fullscreen', 'granted');
            }

            const blocked = validateReady();
            if (blocked) {
                showError(blocked);
                return;
            }

            starting = true;
            startBtn.disabled = true;
            showLoading('Preparing your exam...');

            const preferences = {
                theme: document.getElementById('pref-theme')?.value || 'light',
                font_size: document.getElementById('pref-font')?.value || 'md',
                language: document.getElementById('pref-lang')?.value || 'en',
                palette_position: document.getElementById('pref-palette')?.value || 'right',
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            };
            const device = deviceMeta();

            const steps = [
                [25, 'Fetching questions...'],
                [45, 'Generating question order...'],
                [65, 'Applying exam rules...'],
                [80, 'Finalizing session...'],
            ];
            let stepIndex = 0;
            const stepTimer = window.setInterval(function () {
                if (stepIndex >= steps.length) return;
                setProgress(steps[stepIndex][0], steps[stepIndex][1]);
                stepIndex += 1;
            }, 400);

            try {
                let data;
                if (photoBlob) {
                    const form = new FormData();
                    form.append('_token', csrfToken());
                    Object.keys(preferences).forEach(function (key) {
                        form.append('preferences[' + key + ']', preferences[key]);
                    });
                    Object.keys(device).forEach(function (key) {
                        form.append('device[' + key + ']', device[key]);
                    });
                    form.append('photo', photoBlob, 'verification.jpg');
                    data = await postStart(form, true);
                } else {
                    data = await postStart({ preferences: preferences, device: device }, false);
                }

                window.clearInterval(stepTimer);
                setProgress(100, 'Opening exam...');
                if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
                window.location.assign(data.redirect);
            } catch (e) {
                window.clearInterval(stepTimer);
                const message = (e && e.name === 'AbortError')
                    ? 'Request timed out or was cancelled. Please try again.'
                    : ((e && e.message) || 'Unable to start exam.');
                showError(message);
            }
        });
    });
})();
