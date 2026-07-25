import { createDevtoolsWatcher, isDevtoolsLikelyOpen } from '../devtoolsDetect';

export function createDevtoolsRule({ policy, send, onDevtoolsChange }) {
    if (!policy.detect_devtools) return null;

    let lastSent = 0;
    const reportOpen = () => {
        const now = Date.now();
        if (now - lastSent < 4000) return;
        lastSent = now;
        send('devtools_open', { heuristic: true });
    };

    const onKeyDown = (e) => {
        const key = e.key?.toLowerCase?.() || '';
        const isDevtools =
            key === 'f12' ||
            (e.ctrlKey && e.shiftKey && ['i', 'j', 'c', 'k'].includes(key)) ||
            (e.metaKey && e.altKey && ['i', 'j', 'c'].includes(key)) ||
            (e.ctrlKey && key === 'u') ||
            (e.metaKey && e.altKey && key === 'u');

        if (!isDevtools) return;
        e.preventDefault();
        reportOpen();
    };

    const watcher = createDevtoolsWatcher({
        onChange: (open) => {
            onDevtoolsChange?.(open);
            if (open) reportOpen();
        },
    });

    if (isDevtoolsLikelyOpen()) {
        onDevtoolsChange?.(true);
        reportOpen();
    }

    document.addEventListener('keydown', onKeyDown);
    return () => {
        document.removeEventListener('keydown', onKeyDown);
        watcher.stop();
    };
}
