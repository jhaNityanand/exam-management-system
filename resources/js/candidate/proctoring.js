import { api } from './api';

const EVENT_LABELS = {
    tab_switch: 'Tab switch detected',
    window_blur: 'Window focus lost',
    fullscreen_exit: 'Fullscreen exited',
    copy_attempt: 'Copy blocked',
    paste_attempt: 'Paste blocked',
    cut_attempt: 'Cut blocked',
    drag_attempt: 'Drag and drop blocked',
    right_click: 'Right-click blocked',
    devtools_open: 'Developer tools blocked',
    page_refresh: 'Page refresh blocked',
    navigation_back: 'Back navigation blocked',
    media_lost: 'Camera or microphone lost',
    session_warning: 'Session warning',
};

function warningForEvent(event, action, violationCount, limit) {
    if (action === 'auto_submit') {
        return {
            title: 'Exam submitted',
            message: 'Your exam has been automatically submitted because you exceeded the maximum number of allowed rule violations.',
            event,
            count: violationCount,
            limit,
            action,
        };
    }
    if (action === 'flag') {
        return {
            title: 'Event flagged',
            message: 'A proctoring event was flagged on your attempt.',
            event,
            count: violationCount,
            limit,
            action,
        };
    }
    if (action === 'warn' || action === 'deduped') {
        const labels = {
            tab_switch: 'Stay on the exam tab. Leaving or switching tabs is monitored.',
            window_blur: 'Keep this exam window focused until you finish.',
            fullscreen_exit: 'Fullscreen is required. Re-enter fullscreen to continue.',
            copy_attempt: 'Copying content is not allowed during this exam.',
            paste_attempt: 'Pasting content is not allowed during this exam.',
            cut_attempt: 'Cut is not allowed during this exam.',
            drag_attempt: 'Dragging content is not allowed during this exam.',
            right_click: 'Right-click is disabled during this exam.',
            devtools_open: 'Developer tools are not allowed during this exam.',
            page_refresh: 'Refreshing or reloading the page is blocked during this exam.',
            navigation_back: 'Leaving this page with the back button is not allowed.',
            media_lost: 'Camera or microphone connection was lost. Please restore access.',
        };
        const title = EVENT_LABELS[event] || 'Rule warning';
        const base = labels[event] || 'A monitoring event was recorded.';
        const limitText = limit
            ? ` Warning ${Math.max(1, violationCount || 1)} of ${limit}. Repeated violations may auto-submit your exam.`
            : (violationCount ? ` (warning ${violationCount})` : '');
        return {
            title,
            message: base + limitText,
            event,
            count: violationCount || 1,
            limit,
            action: action || 'warn',
        };
    }
    return null;
}

export function bindProctoring({ eventsUrl, policy, examRoot, onAutoSubmit, onFullscreenExit, onWarning }) {
    const limit = Number(policy.focus_violation_limit || 0) || 3;
    const root = examRoot || document.getElementById('cx-exam');
    let allowUnload = false;

    if (policy.block_copy_paste && root) {
        root.classList.add('is-no-select');
    }

    const send = (event, payload = {}) => {
        if (allowUnload) return;
        api(eventsUrl, {
            method: 'POST',
            body: { event, payload },
        }).then((data) => {
            if (data?.auto_submitted) {
                onAutoSubmit?.({
                    reason: data.submission_reason || null,
                    message: data.submission_message
                        || 'Your exam has been automatically submitted because you exceeded the maximum number of allowed rule violations.',
                    violationCount: data.violation_count,
                    limit,
                });
                return;
            }
            const warning = warningForEvent(event, data?.action, data?.violation_count, limit);
            if (warning) onWarning?.(warning, data);
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
        if (!policy.block_copy_paste) return;
        e.preventDefault();
        send('copy_attempt');
    };
    const onCut = (e) => {
        if (!policy.block_copy_paste) return;
        e.preventDefault();
        send('cut_attempt');
    };
    const onPaste = (e) => {
        if (!policy.block_copy_paste) return;
        e.preventDefault();
        send('paste_attempt');
    };
    const onSelectStart = (e) => {
        if (!policy.block_copy_paste) return;
        const target = e.target;
        if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement) return;
        e.preventDefault();
    };
    const onDragStart = (e) => {
        if (!policy.block_copy_paste) return;
        e.preventDefault();
        send('drag_attempt');
    };
    const onDrop = (e) => {
        if (!policy.block_copy_paste) return;
        e.preventDefault();
        send('drag_attempt');
    };
    const onContext = (e) => {
        if (!policy.block_context_menu) return;
        e.preventDefault();
        send('right_click');
    };
    const onKeyDown = (e) => {
        const key = e.key?.toLowerCase?.() || '';
        const isDevtools =
            key === 'f12' ||
            (e.ctrlKey && e.shiftKey && ['i', 'j', 'c', 'k'].includes(key)) ||
            (e.metaKey && e.altKey && ['i', 'j', 'c'].includes(key)) ||
            (e.ctrlKey && key === 'u') ||
            (e.metaKey && e.altKey && key === 'u');

        if (policy.detect_devtools && isDevtools) {
            e.preventDefault();
            send('devtools_open', { key });
        }

        const isRefresh =
            key === 'f5' ||
            ((e.ctrlKey || e.metaKey) && key === 'r');
        if (policy.block_page_refresh && isRefresh) {
            e.preventDefault();
            send('page_refresh', { key, meta: !!e.metaKey });
        }
    };
    const onBeforeUnload = (e) => {
        if (allowUnload || !policy.block_page_refresh) return;
        e.preventDefault();
        e.returnValue = '';
        send('page_refresh', { source: 'beforeunload' });
    };

    let historyTrapActive = false;
    const trapHistory = () => {
        if (!policy.block_page_refresh || historyTrapActive) return;
        historyTrapActive = true;
        try {
            window.history.pushState({ cxExamLock: 1 }, '', window.location.href);
        } catch (e) {}
    };
    const onPopState = () => {
        if (allowUnload || !policy.block_page_refresh) return;
        send('navigation_back');
        try {
            window.history.pushState({ cxExamLock: 1 }, '', window.location.href);
        } catch (e) {}
    };

    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('blur', onBlur);
    document.addEventListener('fullscreenchange', onFullscreen);
    document.addEventListener('copy', onCopy);
    document.addEventListener('cut', onCut);
    document.addEventListener('paste', onPaste);
    document.addEventListener('selectstart', onSelectStart);
    document.addEventListener('dragstart', onDragStart);
    document.addEventListener('drop', onDrop);
    document.addEventListener('contextmenu', onContext);
    document.addEventListener('keydown', onKeyDown);
    window.addEventListener('beforeunload', onBeforeUnload);
    if (policy.block_page_refresh) {
        trapHistory();
        window.addEventListener('popstate', onPopState);
    }

    function destroy() {
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('blur', onBlur);
        document.removeEventListener('fullscreenchange', onFullscreen);
        document.removeEventListener('copy', onCopy);
        document.removeEventListener('cut', onCut);
        document.removeEventListener('paste', onPaste);
        document.removeEventListener('selectstart', onSelectStart);
        document.removeEventListener('dragstart', onDragStart);
        document.removeEventListener('drop', onDrop);
        document.removeEventListener('contextmenu', onContext);
        document.removeEventListener('keydown', onKeyDown);
        window.removeEventListener('beforeunload', onBeforeUnload);
        window.removeEventListener('popstate', onPopState);
        if (root) root.classList.remove('is-no-select');
    }

    return {
        destroy,
        allowNavigation() {
            allowUnload = true;
        },
    };
}

export function startWebcamMonitor({
    videoEl,
    statusEl,
    eventsUrl,
    requireMicrophone = false,
    onStatus,
    onAutoSubmit,
    onWarning,
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
        const message = requireMicrophone
            ? 'Camera/microphone connection lost. Trying to restore…'
            : 'Camera connection lost. Trying to restore…';
        onStatus?.(message, 'warn');
        onWarning?.({
            title: 'Media connection lost',
            message: 'Please restore camera' + (requireMicrophone ? ' and microphone' : '') + ' access to continue.',
            event: 'media_lost',
            count: null,
            limit: null,
            action: 'warn',
        });
        api(eventsUrl, {
            method: 'POST',
            body: { event: 'media_lost', payload: { reason } },
        }).then((data) => {
            if (data?.auto_submitted) {
                onAutoSubmit?.({
                    reason: data.submission_reason || null,
                    message: data.submission_message
                        || 'Your exam has been automatically submitted because you exceeded the maximum number of allowed rule violations.',
                    violationCount: data.violation_count,
                });
            }
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
                audio: !!requireMicrophone,
            });
            if (videoEl) {
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
                    scheduleRestart();
                });
                track.addEventListener('mute', () => {
                    setStatus(track.kind === 'audio' ? 'Microphone muted' : 'Camera muted', 'warn');
                    reportMediaLost(track.kind + '_mute');
                });
            });

            lostReported = false;
            setStatus(requireMicrophone ? 'Camera & mic active' : 'Camera active', 'ok');
        } catch (err) {
            const name = err?.name || '';
            let message = 'Unable to start camera.';
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                message = 'Media permission denied. Allow camera' + (requireMicrophone ? '/mic' : '') + ' access to continue.';
            } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                message = 'No camera' + (requireMicrophone ? '/microphone' : '') + ' found. Connect a device and retry.';
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
