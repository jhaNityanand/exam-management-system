export function createTimer({ expiresAt, serverNow, onTick, onExpire }) {
    const expiresMs = new Date(expiresAt).getTime();
    const offset = new Date(serverNow).getTime() - Date.now();
    let timerId = null;

    function now() {
        return Date.now() + offset;
    }

    function remaining() {
        return Math.max(0, Math.floor((expiresMs - now()) / 1000));
    }

    function stage(totalSeconds, left) {
        if (!totalSeconds) return 'green';
        const ratio = left / totalSeconds;
        if (ratio > 0.5) return 'green';
        if (ratio > 0.25) return 'yellow';
        if (ratio > 0.1) return 'orange';
        return 'red';
    }

    function format(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        const pad = (n) => String(n).padStart(2, '0');
        return h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
    }

    function tick(totalSeconds) {
        const left = remaining();
        onTick?.({
            left,
            label: format(left),
            stage: stage(totalSeconds, left),
        });
        if (left <= 0) {
            stop();
            onExpire?.();
        }
    }

    function start(totalSeconds) {
        stop();
        tick(totalSeconds);
        timerId = window.setInterval(() => tick(totalSeconds), 1000);
    }

    function stop() {
        if (timerId) window.clearInterval(timerId);
        timerId = null;
    }

    return { start, stop, remaining, format };
}
