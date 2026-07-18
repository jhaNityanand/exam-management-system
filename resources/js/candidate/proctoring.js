import { api } from './api';

export function bindProctoring({ eventsUrl, policy, onAutoSubmit }) {
    const send = (event, payload = {}) => {
        api(eventsUrl, {
            method: 'POST',
            body: { event, payload },
        }).then((data) => {
            if (data?.auto_submitted) onAutoSubmit?.();
        }).catch(() => {});
    };

    const onVisibility = () => {
        if (document.hidden && policy.detect_tab_switch) {
            send('tab_switch');
        }
    };
    const onBlur = () => {
        if (policy.detect_tab_switch) send('window_blur');
    };
    const onFullscreen = () => {
        if (policy.require_fullscreen && !document.fullscreenElement) {
            send('fullscreen_exit');
        }
    };
    const onCopy = (e) => {
        if (policy.block_copy_paste) {
            e.preventDefault();
            send('copy_attempt');
        }
    };
    const onPaste = (e) => {
        if (policy.block_copy_paste) {
            e.preventDefault();
            send('paste_attempt');
        }
    };
    const onContext = (e) => {
        e.preventDefault();
        send('right_click');
    };

    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('blur', onBlur);
    document.addEventListener('fullscreenchange', onFullscreen);
    document.addEventListener('copy', onCopy);
    document.addEventListener('paste', onPaste);
    document.addEventListener('contextmenu', onContext);

    window.addEventListener('beforeunload', (e) => {
        e.preventDefault();
        e.returnValue = '';
    });

    return () => {
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('blur', onBlur);
        document.removeEventListener('fullscreenchange', onFullscreen);
        document.removeEventListener('copy', onCopy);
        document.removeEventListener('paste', onPaste);
        document.removeEventListener('contextmenu', onContext);
    };
}
