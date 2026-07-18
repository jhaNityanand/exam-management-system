export async function requestMedia({ webcam = false, microphone = false } = {}) {
    const result = {
        webcam: webcam ? 'denied' : 'skipped',
        microphone: microphone ? 'denied' : 'skipped',
        stream: null,
    };

    if (!webcam && !microphone) {
        return result;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: webcam,
            audio: microphone,
        });
        result.stream = stream;
        if (webcam) result.webcam = 'granted';
        if (microphone) result.microphone = 'granted';
    } catch (e) {
        if (webcam) result.webcam = 'denied';
        if (microphone) result.microphone = 'denied';
    }

    return result;
}

export async function requestFullscreen(el = document.documentElement) {
    try {
        if (!document.fullscreenElement) {
            await el.requestFullscreen?.();
        }
        return !!document.fullscreenElement;
    } catch (e) {
        return false;
    }
}

export function deviceMeta() {
    return {
        browser: navigator.userAgent,
        device_type: /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' : 'desktop',
        os: navigator.platform,
        screen_resolution: `${window.screen.width}x${window.screen.height}`,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        local_time: new Date().toISOString(),
    };
}
