import { api } from './api';
import { deviceMeta, requestFullscreen, requestMedia } from './permissions';

function setStatus(root, perm, state) {
    const li = root.querySelector(`[data-perm="${perm}"] .cx-status`);
    if (!li) return;
    if (state === 'granted') li.textContent = '✅ Granted';
    else if (state === 'denied') li.textContent = '❌ Denied';
    else if (state === 'skipped') li.textContent = 'ℹ️ Optional';
    else li.textContent = '⚠️ Required';
}

export function initPrepare(root) {
    if (!root) return;

    const requireWebcam = root.dataset.requireWebcam === '1';
    const requireMic = root.dataset.requireMic === '1';
    const requireFullscreen = root.dataset.requireFullscreen === '1';
    const requirePhoto = root.dataset.requirePhoto === '1';
    const startUrl = root.dataset.startUrl;
    const startBtn = root.querySelector('#cx-start-exam');

    let stream = null;
    let photoBlob = null;
    let starting = false;
    const state = {
        webcam: requireWebcam ? 'required' : 'skipped',
        microphone: requireMic ? 'required' : 'skipped',
        fullscreen: requireFullscreen ? 'required' : 'skipped',
    };

    setStatus(root, 'webcam', state.webcam);
    setStatus(root, 'microphone', state.microphone);
    setStatus(root, 'fullscreen', document.fullscreenElement && requireFullscreen ? 'granted' : state.fullscreen);
    if (document.fullscreenElement && requireFullscreen) {
        state.fullscreen = 'granted';
    }
    setStatus(root, 'clipboard', 'skipped');

    const preview = root.querySelector('#cx-preview');
    const errorEl = root.querySelector('#cx-prepare-error');
    const loading = root.querySelector('#cx-loading');
    const progress = root.querySelector('#cx-progress-bar');
    const stepEl = root.querySelector('#cx-loading-step');

    document.addEventListener('fullscreenchange', () => {
        if (!requireFullscreen) return;
        state.fullscreen = document.fullscreenElement ? 'granted' : 'required';
        setStatus(root, 'fullscreen', state.fullscreen === 'granted' ? 'granted' : 'required');
    });

    root.querySelector('#cx-request-permissions')?.addEventListener('click', async () => {
        const result = await requestMedia({ webcam: requireWebcam || requirePhoto, microphone: requireMic });
        stream = result.stream;
        state.webcam = requireWebcam ? result.webcam : 'skipped';
        state.microphone = requireMic ? result.microphone : 'skipped';
        setStatus(root, 'webcam', state.webcam);
        setStatus(root, 'microphone', state.microphone);
        if (stream && preview) {
            preview.hidden = false;
            preview.srcObject = stream;
        }
    });

    root.querySelector('#cx-request-fullscreen')?.addEventListener('click', async () => {
        const ok = await requestFullscreen();
        state.fullscreen = requireFullscreen ? (ok ? 'granted' : 'denied') : 'skipped';
        setStatus(root, 'fullscreen', state.fullscreen);
    });

    root.querySelector('#cx-capture-photo')?.addEventListener('click', async () => {
        if (!stream) {
            const result = await requestMedia({ webcam: true, microphone: false });
            stream = result.stream;
            if (stream && preview) {
                preview.hidden = false;
                preview.srcObject = stream;
            }
        }
        const canvas = root.querySelector('#cx-snapshot-canvas');
        const video = preview;
        if (!canvas || !video || !stream) return;
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        canvas.getContext('2d').drawImage(video, 0, 0);
        canvas.toBlob((blob) => {
            photoBlob = blob;
            const img = root.querySelector('#cx-photo-preview');
            if (img && blob) {
                img.src = URL.createObjectURL(blob);
                img.hidden = false;
            }
        }, 'image/jpeg', 0.9);
    });

    function canStart() {
        if (requireWebcam && state.webcam !== 'granted') return 'Webcam permission is required.';
        if (requireMic && state.microphone !== 'granted') return 'Microphone permission is required.';
        if (requireFullscreen && !document.fullscreenElement && state.fullscreen !== 'granted') {
            return 'Fullscreen mode is required. Click “Enter fullscreen” first.';
        }
        if (requirePhoto && !photoBlob) return 'Photo verification is required.';
        return null;
    }

    function showLoading(message = 'Preparing your exam...') {
        if (!loading) return;
        loading.hidden = false;
        if (stepEl) stepEl.textContent = message;
        if (progress) progress.style.width = '12%';
    }

    function setLoadingProgress(percent, message) {
        if (stepEl && message) stepEl.textContent = message;
        if (progress) progress.style.width = `${Math.max(0, Math.min(100, percent))}%`;
    }

    function hideLoading() {
        if (loading) loading.hidden = true;
        if (progress) progress.style.width = '0%';
    }

    function showError(message) {
        hideLoading();
        if (!errorEl) {
            window.alert(message);
            return;
        }
        errorEl.textContent = message;
        errorEl.hidden = false;
    }

    async function animateSteps(signal) {
        const steps = [
            [18, 'Preparing your exam...'],
            [32, 'Fetching questions...'],
            [48, 'Generating question order...'],
            [62, 'Applying exam rules...'],
            [76, 'Verifying permissions...'],
            [88, 'Loading candidate data...'],
            [96, 'Finalizing session...'],
        ];
        for (const [percent, message] of steps) {
            if (signal.aborted) return;
            setLoadingProgress(percent, message);
            await new Promise((resolve) => window.setTimeout(resolve, 220));
        }
    }

    startBtn?.addEventListener('click', async () => {
        if (starting) return;
        if (errorEl) errorEl.hidden = true;

        // Re-sync fullscreen from the browser (more reliable than button state alone).
        if (requireFullscreen && document.fullscreenElement) {
            state.fullscreen = 'granted';
            setStatus(root, 'fullscreen', 'granted');
        }

        const blocked = canStart();
        if (blocked) {
            // Auto-prompt fullscreen once if that is the only blocker.
            if (requireFullscreen && !document.fullscreenElement) {
                const ok = await requestFullscreen();
                if (ok) {
                    state.fullscreen = 'granted';
                    setStatus(root, 'fullscreen', 'granted');
                } else {
                    showError(blocked);
                    return;
                }
            } else {
                showError(blocked);
                return;
            }
            if (canStart()) {
                showError(canStart());
                return;
            }
        }

        starting = true;
        if (startBtn) startBtn.disabled = true;

        const preferences = {
            theme: root.querySelector('#pref-theme')?.value || 'light',
            font_size: root.querySelector('#pref-font')?.value || 'md',
            language: root.querySelector('#pref-lang')?.value || 'en',
            palette_position: root.querySelector('#pref-palette')?.value || 'right',
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        };
        const device = deviceMeta();

        const abortAnimate = { aborted: false };
        showLoading('Preparing your exam...');
        const animation = animateSteps(abortAnimate);

        try {
            let data;
            if (photoBlob) {
                const form = new FormData();
                Object.entries(preferences).forEach(([key, value]) => form.append(`preferences[${key}]`, value));
                Object.entries(device).forEach(([key, value]) => form.append(`device[${key}]`, value));
                form.append('photo', photoBlob, 'verification.jpg');
                data = await api(startUrl, { method: 'POST', body: form, timeoutMs: 90000 });
            } else {
                data = await api(startUrl, {
                    method: 'POST',
                    body: { preferences, device },
                    timeoutMs: 90000,
                });
            }

            abortAnimate.aborted = true;
            await animation;
            setLoadingProgress(100, 'Opening exam...');
            stream?.getTracks?.().forEach((track) => track.stop());

            if (!data?.redirect) {
                throw new Error('Exam prepared, but no redirect URL was returned.');
            }

            window.location.assign(data.redirect);
        } catch (e) {
            abortAnimate.aborted = true;
            starting = false;
            if (startBtn) startBtn.disabled = false;
            showError(e?.message || 'Unable to start exam.');
        }
    });
}
