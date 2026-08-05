function pad(n) {
    return String(n).padStart(2, '0');
}

export function formatDuration(seconds) {
    const total = Math.max(0, Math.floor(seconds));
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    return h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
}

export function createTimer({ expiresAt, serverNow, onTick, onExpire }) {
    let expiresMs = new Date(expiresAt).getTime();
    let offset = new Date(serverNow).getTime() - Date.now();
    let timerId = null;
    let totalSecondsCached = null;

    function now() {
        return Date.now() + offset;
    }

    function remaining() {
        return Math.max(0, Math.floor((expiresMs - now()) / 1000));
    }

    /**
     * Split total duration into 4 equal quarters (by remaining time).
     * Q1 green → Q2 blue → Q3 yellow → Q4 first half orange → Q4 second half red.
     */
    function stage(totalSeconds, left) {
        if (!totalSeconds) return 'green';
        const ratio = left / totalSeconds;
        if (ratio > 0.75) return 'green';
        if (ratio > 0.5) return 'blue';
        if (ratio > 0.25) return 'yellow';
        if (ratio > 0.125) return 'orange';
        return 'red';
    }

    function tick(totalSeconds) {
        const left = remaining();
        onTick?.({
            left,
            elapsed: null,
            label: formatDuration(left),
            stage: stage(totalSeconds, left),
            mode: 'remaining',
        });
        if (left <= 0) {
            stop();
            onExpire?.();
        }
    }

    function sync({ expiresAt: nextExpiresAt, serverNow: nextServerNow }) {
        expiresMs = new Date(nextExpiresAt).getTime();
        offset = new Date(nextServerNow).getTime() - Date.now();
        tick(totalSecondsCached);
    }

    function start(totalSeconds) {
        stop();
        totalSecondsCached = totalSeconds;
        tick(totalSeconds);
        timerId = window.setInterval(() => tick(totalSeconds), 1000);
    }

    function stop() {
        if (timerId) window.clearInterval(timerId);
        timerId = null;
    }

    return { start, stop, remaining, format: formatDuration, sync };
}

export function createElapsedTimer({ startedAt, serverNow, onTick }) {
    const startedMs = new Date(startedAt).getTime();
    const offset = new Date(serverNow).getTime() - Date.now();
    let timerId = null;

    function now() {
        return Date.now() + offset;
    }

    function elapsed() {
        return Math.max(0, Math.floor((now() - startedMs) / 1000));
    }

    function tick() {
        const value = elapsed();
        onTick?.({
            left: null,
            elapsed: value,
            label: formatDuration(value),
            stage: 'green',
            mode: 'elapsed',
        });
    }

    function start() {
        stop();
        tick();
        timerId = window.setInterval(tick, 1000);
    }

    function stop() {
        if (timerId) window.clearInterval(timerId);
        timerId = null;
    }

    return { start, stop, elapsed, format: formatDuration };
}
