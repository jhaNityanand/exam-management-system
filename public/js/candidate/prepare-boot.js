/**
 * Rule-driven prepare boot:
 * - separate camera / mic permission flows
 * - clear device / permission errors
 * - live selfie capture (no upload)
 * - Start stays disabled until checks pass
 */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') || '' : '';
    }

    function boolAttr(el, name) {
        return el && el.getAttribute(name) === '1';
    }

    function deviceMeta(sessionToken) {
        var ua = navigator.userAgent || '';
        return {
            browser: ua.slice(0, 120),
            device_type: /Mobi|Android/i.test(ua) ? 'mobile' : 'desktop',
            os: (navigator.platform || 'unknown').slice(0, 100),
            screen_resolution: String(window.screen.width) + 'x' + String(window.screen.height),
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            local_time: new Date().toISOString(),
            session_token: sessionToken,
        };
    }

    function mediaErrorMessage(err, kind) {
        var name = (err && err.name) || '';
        var label = kind === 'audio' ? 'microphone' : 'camera';

        if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
            return 'Browser blocked ' + label + ' access. Click the lock/camera icon in the address bar, allow ' + label + ', then retry.';
        }
        if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            return 'No ' + label + ' was found on this device. Connect a ' + label + ' and retry.';
        }
        if (name === 'NotReadableError' || name === 'TrackStartError') {
            return 'Your ' + label + ' is already in use by another app. Close that app and retry.';
        }
        if (name === 'OverconstrainedError' || name === 'ConstraintNotSatisfiedError') {
            return 'This ' + label + ' does not meet the required settings. Try another device.';
        }
        if (name === 'SecurityError') {
            return 'Camera/mic can only be used on a secure page (https or localhost).';
        }
        if (name === 'TypeError') {
            return 'This browser could not open the ' + label + '. Use the latest Chrome, Edge, or Firefox.';
        }
        return (err && err.message) ? err.message : ('Unable to access ' + label + '.');
    }

    function setStatus(root, perm, state) {
        var row = root.querySelector('[data-perm="' + perm + '"]');
        if (!row) return;
        var el = row.querySelector('.cx-status');
        if (!el) return;
        var labels = {
            granted: 'Granted',
            denied: 'Denied',
            required: 'Required',
            waiting: 'Waiting',
            optional: 'Optional',
            info: 'Info',
            captured: 'Captured',
            ready: 'Ready',
            missing: 'Missing',
        };
        el.textContent = labels[state] || state;
        el.dataset.state = state;
    }

    function firstError(payload, fallback) {
        if (!payload) return fallback;
        if (typeof payload.message === 'string' && payload.message) return payload.message;
        if (payload.errors) {
            var keys = Object.keys(payload.errors);
            for (var i = 0; i < keys.length; i += 1) {
                var list = payload.errors[keys[i]];
                if (list && list.length) return list[0];
            }
        }
        return fallback;
    }

    function supportsMedia() {
        return !!(navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function');
    }

    onReady(function () {
        var root = document.getElementById('cx-prepare');
        if (!root) return;

        var requireWebcam = boolAttr(root, 'data-require-webcam');
        var requireMic = boolAttr(root, 'data-require-mic');
        var requireFullscreen = boolAttr(root, 'data-require-fullscreen');
        var requireSelfie = boolAttr(root, 'data-require-selfie');
        var startUrl = root.getAttribute('data-start-url') || '';
        var verifyUrl = root.getAttribute('data-verify-url') || '';
        var challengeToken = root.getAttribute('data-challenge-token') || '';
        var sessionToken = (window.crypto && crypto.randomUUID)
            ? crypto.randomUUID().replace(/-/g, '')
            : String(Date.now()) + Math.random().toString(16).slice(2);

        var state = {
            webcam: !requireWebcam && !requireSelfie,
            microphone: !requireMic,
            fullscreen: !requireFullscreen,
            selfie: !requireSelfie,
            videoStream: null,
            audioStream: null,
            photoBlob: null,
            audioCtx: null,
            micTimer: null,
            requesting: false,
        };

        var preview = document.getElementById('cx-preview');
        var canvas = document.getElementById('cx-snapshot-canvas');
        var photoPreview = document.getElementById('cx-photo-preview');
        var captureBtn = document.getElementById('cx-capture-photo');
        var retakeBtn = document.getElementById('cx-retake-photo');
        var permBtn = document.getElementById('cx-request-permissions');
        var fsBtn = document.getElementById('cx-request-fullscreen');
        var startBtn = document.getElementById('cx-start-exam');
        var errorEl = document.getElementById('cx-prepare-error');
        var readyEl = document.getElementById('cx-ready-msg');
        var loading = document.getElementById('cx-loading');
        var progressBar = document.getElementById('cx-progress-bar');
        var loadingStep = document.getElementById('cx-loading-step');
        var micLevel = document.getElementById('cx-mic-level');
        var helpBtn = document.getElementById('cx-help-toggle');
        var helpPanel = document.getElementById('cx-help-panel');
        var abortController = null;

        function showError(msg) {
            if (!errorEl) return;
            if (msg) {
                errorEl.hidden = false;
                errorEl.removeAttribute('hidden');
                errorEl.textContent = msg;
            } else {
                errorEl.hidden = true;
                errorEl.setAttribute('hidden', 'hidden');
                errorEl.textContent = '';
            }
        }

        function readinessMessage() {
            var missing = [];
            if ((requireWebcam || requireSelfie) && !state.webcam) missing.push('camera access');
            if (requireMic && !state.microphone) missing.push('microphone access');
            if (requireFullscreen && !state.fullscreen) missing.push('fullscreen');
            if (requireSelfie && !state.selfie) missing.push('identity selfie');
            if (!missing.length) return 'All required checks are complete. You can start the exam.';
            return 'Start is disabled until you complete: ' + missing.join(', ') + '.';
        }

        function updateStartEnabled() {
            var forceDisabled = startBtn && startBtn.getAttribute('data-force-disabled') === '1';
            var ready = !forceDisabled && state.webcam && state.microphone && state.fullscreen && state.selfie;
            if (startBtn) {
                startBtn.disabled = !ready;
                startBtn.setAttribute('aria-disabled', ready ? 'false' : 'true');
                if (!ready) startBtn.title = forceDisabled ? 'You cannot start this exam right now.' : readinessMessage();
                else startBtn.removeAttribute('title');
            }
            if (readyEl) {
                readyEl.textContent = forceDisabled
                    ? 'Start is unavailable until eligibility issues are resolved.'
                    : readinessMessage();
                readyEl.dataset.state = ready ? 'ready' : 'blocked';
            }
            if (captureBtn) {
                captureBtn.disabled = !state.webcam;
            }
        }

        function stopStream(stream) {
            if (!stream) return;
            stream.getTracks().forEach(function (track) {
                try { track.stop(); } catch (e) {}
            });
        }

        function stopAllMedia() {
            stopStream(state.videoStream);
            stopStream(state.audioStream);
            state.videoStream = null;
            state.audioStream = null;
            if (preview) {
                preview.srcObject = null;
                preview.hidden = true;
                preview.setAttribute('hidden', 'hidden');
            }
            if (state.micTimer) {
                clearInterval(state.micTimer);
                state.micTimer = null;
            }
            if (state.audioCtx) {
                try { state.audioCtx.close(); } catch (e) {}
                state.audioCtx = null;
            }
        }

        function showPreview(stream) {
            if (!preview) return;
            preview.srcObject = stream;
            preview.muted = true;
            preview.playsInline = true;
            preview.hidden = false;
            preview.removeAttribute('hidden');
            var playPromise = preview.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(function () {});
            }
        }

        async function waitForVideoFrame() {
            if (!preview) return false;
            if (preview.readyState >= 2 && preview.videoWidth > 0) return true;
            return new Promise(function (resolve) {
                var tries = 0;
                var timer = setInterval(function () {
                    tries += 1;
                    if ((preview.readyState >= 2 && preview.videoWidth > 0) || tries > 40) {
                        clearInterval(timer);
                        resolve(preview.videoWidth > 0);
                    }
                }, 100);
            });
        }

        async function requestCamera() {
            var stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            });
            stopStream(state.videoStream);
            state.videoStream = stream;
            showPreview(stream);
            await waitForVideoFrame();
            var track = stream.getVideoTracks()[0];
            state.webcam = !!(track && track.readyState === 'live');
            setStatus(root, 'webcam', state.webcam ? 'granted' : 'denied');
            return state.webcam;
        }

        async function requestMicrophone() {
            var stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                },
                video: false,
            });
            stopStream(state.audioStream);
            state.audioStream = stream;
            var track = stream.getAudioTracks()[0];
            if (!track) {
                state.microphone = false;
                setStatus(root, 'microphone', 'denied');
                return false;
            }
            await verifyMicLevel(stream);
            return state.microphone;
        }

        async function verifyMicLevel(stream) {
            try {
                var AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) {
                    state.microphone = true;
                    setStatus(root, 'microphone', 'granted');
                    return;
                }
                state.audioCtx = new AudioCtx();
                if (state.audioCtx.state === 'suspended') {
                    await state.audioCtx.resume();
                }
                var source = state.audioCtx.createMediaStreamSource(stream);
                var analyser = state.audioCtx.createAnalyser();
                analyser.fftSize = 256;
                source.connect(analyser);
                var data = new Uint8Array(analyser.frequencyBinCount);
                var heard = false;
                var tries = 0;
                if (micLevel) {
                    micLevel.hidden = false;
                    micLevel.removeAttribute('hidden');
                    micLevel.textContent = 'Speak briefly to confirm microphone…';
                }
                await new Promise(function (resolve) {
                    state.micTimer = setInterval(function () {
                        analyser.getByteFrequencyData(data);
                        var sum = 0;
                        for (var i = 0; i < data.length; i += 1) sum += data[i];
                        var avg = sum / data.length;
                        tries += 1;
                        if (avg > 4) heard = true;
                        if (heard || tries > 30) {
                            clearInterval(state.micTimer);
                            state.micTimer = null;
                            resolve();
                        }
                    }, 100);
                });
                // Track exists => grant even in a silent room.
                state.microphone = true;
                setStatus(root, 'microphone', 'granted');
                if (micLevel) {
                    micLevel.textContent = heard
                        ? 'Microphone ready (audio detected).'
                        : 'Microphone ready. No speech detected, but the mic is available.';
                }
            } catch (e) {
                state.microphone = !!(stream && stream.getAudioTracks().length);
                setStatus(root, 'microphone', state.microphone ? 'granted' : 'denied');
            }
        }

        async function requestMedia() {
            if (state.requesting) return;
            state.requesting = true;
            showError('');
            if (permBtn) {
                permBtn.disabled = true;
                permBtn.textContent = 'Requesting…';
            }

            try {
                if (!supportsMedia()) {
                    throw Object.assign(new Error('Media devices API is unavailable in this browser.'), { name: 'TypeError' });
                }
                if (!window.isSecureContext) {
                    throw Object.assign(new Error('Camera/mic require a secure context.'), { name: 'SecurityError' });
                }

                var needVideo = requireWebcam || requireSelfie;
                var messages = [];

                if (needVideo) {
                    setStatus(root, 'webcam', 'waiting');
                    try {
                        await requestCamera();
                    } catch (err) {
                        state.webcam = false;
                        setStatus(root, 'webcam', err && err.name === 'NotFoundError' ? 'missing' : 'denied');
                        messages.push(mediaErrorMessage(err, 'video'));
                    }
                }

                if (requireMic) {
                    setStatus(root, 'microphone', 'waiting');
                    try {
                        await requestMicrophone();
                    } catch (err) {
                        state.microphone = false;
                        setStatus(root, 'microphone', err && err.name === 'NotFoundError' ? 'missing' : 'denied');
                        messages.push(mediaErrorMessage(err, 'audio'));
                    }
                }

                if (messages.length) {
                    showError(messages.join(' '));
                } else {
                    showError('');
                }
            } catch (err) {
                showError(mediaErrorMessage(err, requireMic && !(requireWebcam || requireSelfie) ? 'audio' : 'video'));
            } finally {
                state.requesting = false;
                if (permBtn) {
                    permBtn.disabled = false;
                    permBtn.textContent = 'Allow camera / mic';
                }
                updateStartEnabled();
            }
        }

        async function requestFullscreen() {
            showError('');
            try {
                var el = document.documentElement;
                if (document.fullscreenElement || document.webkitFullscreenElement) {
                    state.fullscreen = true;
                    setStatus(root, 'fullscreen', 'granted');
                    updateStartEnabled();
                    return;
                }
                if (el.requestFullscreen) {
                    await el.requestFullscreen();
                } else if (el.webkitRequestFullscreen) {
                    await el.webkitRequestFullscreen();
                } else {
                    throw new Error('Fullscreen is not supported in this browser.');
                }
                state.fullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);
                setStatus(root, 'fullscreen', state.fullscreen ? 'granted' : 'denied');
                if (!state.fullscreen) {
                    showError('Fullscreen did not activate. Try again or use F11, then click Enter fullscreen.');
                }
            } catch (e) {
                state.fullscreen = false;
                setStatus(root, 'fullscreen', 'denied');
                showError((e && e.message) || 'Unable to enter fullscreen. Allow fullscreen when prompted and retry.');
            }
            updateStartEnabled();
        }

        document.addEventListener('fullscreenchange', function () {
            if (!requireFullscreen) return;
            state.fullscreen = !!document.fullscreenElement;
            setStatus(root, 'fullscreen', state.fullscreen ? 'granted' : 'required');
            updateStartEnabled();
        });

        async function captureSelfie() {
            showError('');
            if (!state.webcam || !state.videoStream) {
                showError('Allow camera access first, then capture your selfie.');
                return;
            }
            if (!preview || !canvas) {
                showError('Camera preview is unavailable. Allow camera again and retry.');
                return;
            }
            var ready = await waitForVideoFrame();
            if (!ready) {
                showError('Camera preview is still loading. Wait a moment and try Capture selfie again.');
                return;
            }

            canvas.width = preview.videoWidth || 640;
            canvas.height = preview.videoHeight || 480;
            var ctx = canvas.getContext('2d');
            if (!ctx) {
                showError('Could not capture selfie on this browser.');
                return;
            }
            ctx.drawImage(preview, 0, 0, canvas.width, canvas.height);

            var blob = await new Promise(function (resolve) {
                if (canvas.toBlob) {
                    canvas.toBlob(resolve, 'image/jpeg', 0.92);
                } else {
                    resolve(null);
                }
            });
            if (!blob) {
                showError('Could not capture selfie. Try again.');
                return;
            }

            state.photoBlob = blob;
            state.selfie = true;
            if (photoPreview) {
                if (photoPreview.src && photoPreview.src.indexOf('blob:') === 0) {
                    try { URL.revokeObjectURL(photoPreview.src); } catch (e) {}
                }
                photoPreview.src = URL.createObjectURL(blob);
                photoPreview.hidden = false;
                photoPreview.removeAttribute('hidden');
            }
            setStatus(root, 'selfie', 'captured');
            if (retakeBtn) {
                retakeBtn.hidden = false;
                retakeBtn.removeAttribute('hidden');
            }
            if (captureBtn) captureBtn.textContent = 'Selfie captured';

            if (verifyUrl && challengeToken) {
                var form = new FormData();
                form.append('_token', csrfToken());
                form.append('challenge_token', challengeToken);
                form.append('completed_checks[]', 'selfie');
                form.append('completed_checks[]', 'webcam');
                form.append('selfie', blob, 'verification.jpg');
                try {
                    var res = await fetch(verifyUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: form,
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        var payload = await res.json().catch(function () { return {}; });
                        showError(firstError(payload, 'Selfie captured locally, but server verification failed. You can still retry Start.'));
                    }
                } catch (e) {
                    // Local capture still valid for UI; server re-checks on start.
                }
            }
            updateStartEnabled();
        }

        function validateReady() {
            if ((requireWebcam || requireSelfie) && !state.webcam) return 'Camera access is required.';
            if (requireMic && !state.microphone) return 'Microphone access is required.';
            if (requireFullscreen && !state.fullscreen) return 'Fullscreen is required.';
            if (requireSelfie && (!state.selfie || !state.photoBlob)) return 'Capture a live selfie before starting.';
            return '';
        }

        async function postStart(body, isForm) {
            abortController = new AbortController();
            var timer = setTimeout(function () { abortController.abort(); }, 45000);
            try {
                var headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                };
                if (!isForm) {
                    headers['Content-Type'] = 'application/json';
                    headers['X-CSRF-TOKEN'] = csrfToken();
                }
                var res = await fetch(startUrl, {
                    method: 'POST',
                    headers: headers,
                    body: isForm ? body : JSON.stringify(body),
                    credentials: 'same-origin',
                    signal: abortController.signal,
                });
                var data = await res.json().catch(function () { return {}; });
                if (!res.ok) throw new Error(firstError(data, 'Unable to start exam.'));
                return data;
            } finally {
                clearTimeout(timer);
            }
        }

        function setLoading(on, step, pct) {
            if (!loading) return;
            if (on) {
                loading.hidden = false;
                loading.removeAttribute('hidden');
            } else {
                loading.hidden = true;
                loading.setAttribute('hidden', 'hidden');
            }
            if (loadingStep && step) loadingStep.textContent = step;
            if (progressBar && typeof pct === 'number') progressBar.style.width = pct + '%';
        }

        async function startExam() {
            showError('');
            var err = validateReady();
            if (err) {
                showError(err);
                updateStartEnabled();
                return;
            }

            setLoading(true, 'Validating requirements…', 20);
            var device = deviceMeta(sessionToken);
            var checks = {
                webcam: !!state.webcam,
                microphone: !!state.microphone,
                fullscreen: !!state.fullscreen,
                selfie: !!state.selfie,
            };

            try {
                setLoading(true, 'Creating attempt…', 55);
                var data;
                if (requireSelfie && state.photoBlob) {
                    var form = new FormData();
                    form.append('_token', csrfToken());
                    form.append('challenge_token', challengeToken);
                    Object.keys(device).forEach(function (key) {
                        form.append('device[' + key + ']', device[key]);
                    });
                    Object.keys(checks).forEach(function (key) {
                        form.append('checks[' + key + ']', checks[key] ? '1' : '0');
                    });
                    form.append('selfie', state.photoBlob, 'verification.jpg');
                    data = await postStart(form, true);
                } else {
                    data = await postStart({
                        challenge_token: challengeToken,
                        device: device,
                        checks: checks,
                    }, false);
                }

                setLoading(true, 'Opening exam…', 90);
                await mountRunnerFromStart(data);
            } catch (e) {
                setLoading(false);
                showError(e && e.name === 'AbortError' ? 'Start timed out. Please retry.' : (e.message || 'Unable to start exam.'));
            }
        }

        async function mountRunnerFromStart(data) {
            var host = document.getElementById('cx-runner-host');
            var startedUrl = (data && data.started_url) || root.getAttribute('data-started-url') || '';
            var html = data && data.runner_html;

            if (!host || !html) {
                stopAllMedia();
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                throw new Error('Exam shell could not be loaded. Please refresh and try again.');
            }

            if (typeof window.__cxDestroyExam === 'function') {
                try { window.__cxDestroyExam(); } catch (e) {}
            }

            host.innerHTML = html;
            host.hidden = false;
            host.removeAttribute('hidden');
            host.setAttribute('aria-hidden', 'false');

            var examRoot = host.querySelector('#cx-exam');
            if (!examRoot) {
                throw new Error('Exam interface is incomplete. Please refresh and try again.');
            }

            examRoot.classList.add('cx-exam--overlay', 'is-active');
            document.body.classList.add('is-exam-running');

            if (startedUrl && window.history && window.history.replaceState) {
                try {
                    window.history.replaceState({ examStarted: true }, '', startedUrl);
                } catch (e) {}
            }

            // Hand webcam off to the runner after mount so prepare stream can stop cleanly.
            stopAllMedia();

            if (typeof window.__cxMountExam !== 'function') {
                throw new Error('Exam scripts are still loading. Please wait a moment and retry.');
            }

            window.__cxMountExam(examRoot);
            setLoading(false);

            if (root) {
                root.setAttribute('aria-hidden', 'true');
                root.style.pointerEvents = 'none';
            }
        }

        if (permBtn) permBtn.addEventListener('click', function (e) {
            e.preventDefault();
            requestMedia();
        });
        if (fsBtn) fsBtn.addEventListener('click', function (e) {
            e.preventDefault();
            requestFullscreen();
        });
        if (captureBtn) captureBtn.addEventListener('click', function (e) {
            e.preventDefault();
            captureSelfie();
        });
        if (retakeBtn) retakeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            state.photoBlob = null;
            state.selfie = false;
            if (photoPreview) {
                photoPreview.hidden = true;
                photoPreview.setAttribute('hidden', 'hidden');
            }
            setStatus(root, 'selfie', 'required');
            if (captureBtn) captureBtn.textContent = 'Capture selfie';
            retakeBtn.hidden = true;
            retakeBtn.setAttribute('hidden', 'hidden');
            updateStartEnabled();
        });
        if (startBtn) startBtn.addEventListener('click', function (e) {
            e.preventDefault();
            startExam();
        });
        var cancelBtn = document.getElementById('cx-cancel-start');
        if (cancelBtn) cancelBtn.addEventListener('click', function () {
            if (abortController) abortController.abort();
            setLoading(false);
        });
        if (helpBtn && helpPanel) {
            helpBtn.addEventListener('click', function () {
                var open = helpPanel.hasAttribute('hidden');
                if (open) {
                    helpPanel.removeAttribute('hidden');
                    helpBtn.setAttribute('aria-expanded', 'true');
                } else {
                    helpPanel.setAttribute('hidden', 'hidden');
                    helpBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        if (root.getAttribute('data-block-context') === '1') {
            setStatus(root, 'browser_lock', 'info');
        }

        updateStartEnabled();
    });
})();
