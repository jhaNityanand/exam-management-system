const GRACE_SECONDS = 60;

/**
 * Webcam/mic monitor with a 60s restore grace period before auto-submit.
 */
export function createMediaMonitorRule({
    policy,
    eventsUrl,
    api,
    videoEl,
    statusEl,
    onStatus,
    onWarning,
    onAutoSubmit,
    onGraceTick,
}) {
    const requireWebcam = !!policy.require_webcam;
    const requireMicrophone = !!policy.require_microphone;
    if (!requireWebcam && !requireMicrophone) return null;

    let stream = null;
    let stopped = false;
    let restartTimer = null;
    let graceTimer = null;
    let graceLeft = 0;
    let graceActive = false;
    let reconnecting = false;
    let lastLostReason = 'media_lost';

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

    function clearGrace() {
        graceActive = false;
        graceLeft = 0;
        if (graceTimer) {
            window.clearInterval(graceTimer);
            graceTimer = null;
        }
        onGraceTick?.(null);
    }

    function startGrace(reason) {
        if (stopped || graceActive) return;
        graceActive = true;
        graceLeft = GRACE_SECONDS;
        lastLostReason = reason || 'media_lost';

        onWarning?.({
            title: 'Media connection lost',
            message: 'Please re-enable the required camera'
                + (requireMicrophone ? ' and microphone' : '')
                + `. You have ${GRACE_SECONDS} seconds to restore access or the exam will be submitted automatically.`,
            event: 'media_lost',
            count: null,
            limit: null,
            action: 'warn',
            graceSeconds: GRACE_SECONDS,
            canReconnect: true,
        });

        api(eventsUrl, {
            method: 'POST',
            body: { event: 'media_lost', payload: { reason } },
        }).catch(() => {});

        onGraceTick?.(graceLeft);
        graceTimer = window.setInterval(() => {
            graceLeft -= 1;
            onGraceTick?.(graceLeft);
            if (graceLeft <= 0) {
                clearGrace();
                api(eventsUrl, {
                    method: 'POST',
                    body: { event: 'media_grace_expired', payload: { reason: lastLostReason } },
                }).then((data) => {
                    onAutoSubmit?.({
                        reason: data?.submission_reason || null,
                        message: data?.submission_message
                            || 'Your exam was submitted because camera/microphone access was not restored in time.',
                        violationCount: data?.violation_count,
                    });
                }).catch(() => {
                    onAutoSubmit?.({
                        message: 'Your exam was submitted because camera/microphone access was not restored in time.',
                    });
                });
            }
        }, 1000);
    }

    function reportMediaLost(reason) {
        if (stopped) return;
        const message = requireMicrophone
            ? 'Camera/microphone connection lost. Trying to restore…'
            : 'Camera connection lost. Trying to restore…';
        onStatus?.(message, 'warn');
        setStatus(message, 'warn');
        startGrace(reason);
        scheduleRestart();
    }

    async function start({ manual = false } = {}) {
        if (stopped) return false;
        if (reconnecting) return false;
        reconnecting = true;

        if (!navigator.mediaDevices?.getUserMedia) {
            reconnecting = false;
            setStatus('Camera not supported in this browser', 'error');
            onStatus?.('Camera is required but not supported in this browser.', 'error');
            startGrace('unsupported');
            return false;
        }
        if (!window.isSecureContext) {
            reconnecting = false;
            setStatus('Secure page required for camera', 'error');
            onStatus?.('Camera requires https or localhost.', 'error');
            startGrace('insecure_context');
            return false;
        }

        setStatus(manual ? 'Reconnecting…' : 'Starting camera…', 'info');
        if (manual) {
            onStatus?.('Reconnecting camera' + (requireMicrophone ? '/microphone' : '') + '…', 'info');
        }

        try {
            stopTracks();
            stream = await navigator.mediaDevices.getUserMedia({
                video: requireWebcam ? { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } : false,
                audio: !!requireMicrophone,
            });
            if (videoEl && requireWebcam) {
                videoEl.srcObject = stream;
                videoEl.muted = true;
                videoEl.playsInline = true;
                const playPromise = videoEl.play();
                if (playPromise?.catch) playPromise.catch(() => {});
            }

            stream.getTracks().forEach((track) => {
                track.addEventListener('ended', () => {
                    setStatus(track.kind === 'audio' ? 'Microphone disconnected' : 'Camera disconnected', 'error');
                    reportMediaLost(track.kind + '_ended');
                });
                track.addEventListener('mute', () => {
                    // Soft signal only — some devices fire mute briefly without a real disconnect.
                    setStatus(track.kind === 'audio' ? 'Microphone muted' : 'Camera muted', 'warn');
                });
            });

            clearGrace();
            setStatus(requireMicrophone ? 'Camera & mic active' : 'Camera active', 'ok');
            onStatus?.(
                manual
                    ? (requireMicrophone ? 'Camera & mic reconnected.' : 'Camera reconnected.')
                    : (requireMicrophone ? 'Camera & mic restored.' : 'Camera restored.'),
                'info',
            );
            reconnecting = false;
            return true;
        } catch (err) {
            const name = err?.name || '';
            let message = 'Unable to start camera.';
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                message = 'Media permission denied. Allow camera' + (requireMicrophone ? '/mic' : '') + ' access, then tap Reconnect.';
            } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                message = 'No camera' + (requireMicrophone ? '/microphone' : '') + ' found. Connect a device and tap Reconnect.';
            } else if (name === 'NotReadableError' || name === 'TrackStartError') {
                message = 'Camera is in use by another app. Close it and tap Reconnect.';
            }
            setStatus(message, 'error');
            onStatus?.(message, 'error');
            startGrace(name || 'start_failed');
            if (!manual) scheduleRestart(5000);
            reconnecting = false;
            return false;
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

    return {
        reconnect() {
            if (stopped) return Promise.resolve(false);
            if (restartTimer) {
                window.clearTimeout(restartTimer);
                restartTimer = null;
            }
            return start({ manual: true });
        },
        destroy() {
            stopped = true;
            clearGrace();
            if (restartTimer) window.clearTimeout(restartTimer);
            stopTracks();
        },
    };
}
