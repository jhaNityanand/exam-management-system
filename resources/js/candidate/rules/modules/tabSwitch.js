export function createTabSwitchRule({ policy, send }) {
    if (!policy.detect_tab_switch) return null;

    let lastFocusSent = 0;
    const sendFocus = (event) => {
        const now = Date.now();
        if (now - lastFocusSent < 1500) return;
        lastFocusSent = now;
        send(event);
    };

    const onVisibility = () => {
        if (document.hidden) sendFocus('tab_switch');
    };
    const onBlur = () => {
        if (!document.hidden) sendFocus('window_blur');
    };

    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('blur', onBlur);
    return () => {
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('blur', onBlur);
    };
}
