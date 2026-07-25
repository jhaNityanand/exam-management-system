export function createFullscreenRule({ policy, send, onFullscreenExit }) {
    if (!policy.require_fullscreen) return null;

    const onFullscreen = () => {
        if (!document.fullscreenElement) {
            send('fullscreen_exit');
            onFullscreenExit?.();
        }
    };

    document.addEventListener('fullscreenchange', onFullscreen);
    return () => document.removeEventListener('fullscreenchange', onFullscreen);
}
