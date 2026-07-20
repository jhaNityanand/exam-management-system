import { api } from './api';

function warningForEvent(event, action, violationCount) {
    if (action === 'auto_submit') {
        return 'A rule limit was reached. Your exam is being submitted.';
    }
    if (action === 'flag') {
        return 'A proctoring event was flagged on your attempt.';
    }
    if (action === 'warn' || action === 'deduped') {
        const labels = {
            tab_switch: 'Stay on the exam tab. Tab switches are monitored.',
            window_blur: 'Keep this window focused during the exam.',
            fullscreen_exit: 'Fullscreen is required for this exam.',
            copy_attempt: 'Copying is blocked during this exam.',
            paste_attempt: 'Pasting is blocked during this exam.',
            cut_attempt: 'Cut is blocked during this exam.',
            right_click: 'Right-click is blocked during this exam.',
            devtools_open: 'Developer tools are not allowed during this exam.',
            page_refresh: 'Refreshing the page is blocked during this exam.',
            media_lost: 'Camera connection was lost. Please restore camera access.',
        };
        const base = labels[event] || 'A monitoring event was recorded.';
        return violationCount ? `${base} (warning ${violationCount})` : base;
    }
    return null;
}

export function bindProctoring({ eventsUrl, policy, onAutoSubmit, onFullscreenExit, onWarning }) {
    const send = (event, payload = {}) => {
        api(eventsUrl, {
            method: 'POST',
            body: { event, payload },
        }).then((data) => {
            if (data?.auto_submitted) {
                onAutoSubmit?.();
                return;
            }
            const message = warningForEvent(event, data?.action, data?.violation_count);
            if (message) onWarning?.(message, data);
        }).catch(() => {});
    };

    let lastFocusSent = 0;
    const sendFocus = (event) => {
        const now = Date.now();
        if (now - lastFocusSent < 1500) return;
        lastFocusSent = now;
        send(event);
    };

    const onVisibility = () => {
        if (document.hidden && policy.detect_tab_switch) {
            sendFocus('tab_switch');
        }
    };
    const onBlur = () => {
        if (policy.detect_tab_switch && !document.hidden) {
            sendFocus('window_blur');
        }
    };
    const onFullscreen = () => {
        if (policy.require_fullscreen && !document.fullscreenElement) {
            send('fullscreen_exit');
            onFullscreenExit?.();
        }
    };
    const onCopy = (e) => {
        if (policy.block_copy_paste) {
            e.preventDefault();
            send('copy_attempt');
        }
    };
    const onCut = (e) => {
        if (policy.block_copy_paste) {
            e.preventDefault();
            send('cut_attempt');
        }
    };
    const onPaste = (e) => {
        if (policy.block_copy_paste) {
            e.preventDefault();
            send('paste_attempt');
        }
    };
    const onContext = (e) => {
        if (policy.block_context_menu) {
            e.preventDefault();
            send('right_click');
        }
    };
    const onKeyDown = (e) => {
        const key = e.key?.toLowerCase?.() || '';
        const isDevtools =
            key === 'f12' ||
            (e.ctrlKey && e.shiftKey && ['i', 'j', 'c'].includes(key)) ||
            (e.metaKey && e.altKey && ['i', 'j', 'c'].includes(key)) ||
            (e.ctrlKey && key === 'u');

        if (policy.detect_devtools && isDevtools) {
            e.preventDefault();
            send('devtools_open', { key });
        }

        if (policy.block_page_refresh && ((e.ctrlKey && key === 'r') || key === 'f5')) {
            e.preventDefault();
            send('page_refresh');
        }
    };
    const onBeforeUnload = (e) => {
        if (policy.block_page_refresh) {
            e.preventDefault();
            e.returnValue = '';
            send('page_refresh');
        }
    };

    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('blur', onBlur);
    document.addEventListener('fullscreenchange', onFullscreen);
    document.addEventListener('copy', onCopy);
    document.addEventListener('cut', onCut);
    document.addEventListener('paste', onPaste);
    document.addEventListener('contextmenu', onContext);
    document.addEventListener('keydown', onKeyDown);
    window.addEventListener('beforeunload', onBeforeUnload);

    return () => {
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('blur', onBlur);
        document.removeEventListener('fullscreenchange', onFullscreen);
        document.removeEventListener('copy', onCopy);
        document.removeEventListener('cut', onCut);
        document.removeEventListener('paste', onPaste);
        document.removeEventListener('contextmenu', onContext);
        document.removeEventListener('keydown', onKeyDown);
        window.removeEventListener('beforeunload', onBeforeUnload);
    };
}

export function startWebcamMonitor({
    videoEl,
    statusEl,
    eventsUrl,
    onStatus,
    onAutoSubmit,
}) {
    let stream = null;
    let stopped = false;
    let restartTimer = null;
    let lostReported = false;

    function setStatus(message, tone = 'info') {
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.dataset.tone = tone;
        }
    }

    function stopTracks() {
        if (!stream) return;
        stream.getTracks().forEach((track) => {
            try { track.stop(); } catch (e) {}
        });
        stream = null;
        if (videoEl) videoEl.srcObject = null;
    }

    function reportMediaLost(reason) {
        if (lostReported || stopped) return;
        lostReported = true;
        onStatus?.('Camera connection lost. Trying to restore…', 'warn');
        api(eventsUrl, {
            method: 'POST',
            body: { event: 'media_lost', payload: { reason } },
        }).then((data) => {
            if (data?.auto_submitted) onAutoSubmit?.();
        }).catch(() => {});
    }

    async function start() {
        if (stopped) return;
        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Camera not supported in this browser', 'error');
            onStatus?.('Camera is required but not supported in this browser.', 'error');
            return;
        }
        if (!window.isSecureContext) {
            setStatus('Secure page required for camera', 'error');
            onStatus?.('Camera requires https or localhost.', 'error');
            return;
        }

        setStatus('Starting camera…', 'info');
        try {
            stopTracks();
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false,
            });
            if (videoEl) {
                videoEl.srcObject = stream;
                videoEl.muted = true;
                videoEl.playsInline = true;
                const playPromise = videoEl.play();
                if (playPromise?.catch) playPromise.catch(() => {});
            }

            const track = stream.getVideoTracks()[0];
            if (track) {
                track.addEventListener('ended', () => {
                    setStatus('Camera disconnected', 'error');
                    reportMediaLost('track_ended');
                    scheduleRestart();
                });
                track.addEventListener('mute', () => {
                    setStatus('Camera muted', 'warn');
                    reportMediaLost('track_mute');
                });
            }

            lostReported = false;
            setStatus('Camera active', 'ok');
        } catch (err) {
            const name = err?.name || '';
            let message = 'Unable to start camera.';
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                message = 'Camera permission denied. Allow camera access to continue.';
            } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                message = 'No camera found. Connect a webcam and retry.';
            } else if (name === 'NotReadableError' || name === 'TrackStartError') {
                message = 'Camera is in use by another app. Close it and retry.';
            }
            setStatus(message, 'error');
            onStatus?.(message, 'error');
            reportMediaLost(name || 'start_failed');
            scheduleRestart(5000);
        }
    }

    function scheduleRestart(delay = 2500) {
        if (stopped) return;
        if (restartTimer) window.clearTimeout(restartTimer);
        restartTimer = window.setTimeout(() => {
            start().catch(() => {});
        }, delay);
    }

    start().catch(() => {});

    return () => {
        stopped = true;
        if (restartTimer) window.clearTimeout(restartTimer);
        stopTracks();
    };
}
