export function createPageLockRule({ policy, send, allowUnloadRef }) {
    if (!policy.block_page_refresh) return null;

    const onKeyDown = (e) => {
        const key = e.key?.toLowerCase?.() || '';
        const isRefresh = key === 'f5' || ((e.ctrlKey || e.metaKey) && key === 'r');
        if (!isRefresh) return;
        e.preventDefault();
        send('page_refresh', { key, meta: !!e.metaKey });
    };

    const onBeforeUnload = (e) => {
        if (allowUnloadRef.current) return;
        e.preventDefault();
        e.returnValue = '';
        send('page_refresh', { source: 'beforeunload' });
    };

    let historyTrapActive = false;
    const trapHistory = () => {
        if (historyTrapActive) return;
        historyTrapActive = true;
        try {
            window.history.pushState({ cxExamLock: 1 }, '', window.location.href);
        } catch (err) {}
    };

    const onPopState = () => {
        if (allowUnloadRef.current) return;
        send('navigation_back');
        try {
            window.history.pushState({ cxExamLock: 1 }, '', window.location.href);
        } catch (err) {}
    };

    trapHistory();
    document.addEventListener('keydown', onKeyDown);
    window.addEventListener('beforeunload', onBeforeUnload);
    window.addEventListener('popstate', onPopState);

    return () => {
        document.removeEventListener('keydown', onKeyDown);
        window.removeEventListener('beforeunload', onBeforeUnload);
        window.removeEventListener('popstate', onPopState);
    };
}
