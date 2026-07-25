export function createRightClickRule({ policy, send }) {
    if (!policy.block_context_menu) return null;

    const onContext = (e) => {
        e.preventDefault();
        // Soft event: blocks menu and warns, but does not burn the shared violation budget.
        send('right_click');
    };

    document.addEventListener('contextmenu', onContext);
    return () => document.removeEventListener('contextmenu', onContext);
}
