/**
 * Theme (light / dark / system) and sidebar collapse for panel layouts.
 */
(function () {
    const root = document.getElementById('panel-root');
    if (!root) return;

    const theme = root.dataset.theme || 'system';
    const html = document.documentElement;

    function applyTheme(t) {
        if (t === 'dark') {
            html.classList.add('dark');
        } else if (t === 'light') {
            html.classList.remove('dark');
        } else {
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
        }
    }

    applyTheme(theme);
    if (theme === 'system') {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => applyTheme('system'));
    }

    const sidebar = document.getElementById('app-sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const labels = document.querySelectorAll('[data-sidebar-label]');

    function setCollapsed(collapsed) {
        if (!sidebar) return;
        sidebar.classList.toggle('w-16', collapsed);
        sidebar.classList.toggle('w-64', !collapsed);
        labels.forEach((el) => {
            el.classList.toggle('hidden', collapsed);
        });
        try {
            localStorage.setItem('ems.sidebarCollapsed', collapsed ? '1' : '0');
        } catch (e) {}
    }

    const stored = localStorage.getItem('ems.sidebarCollapsed');
    const initialCollapsed =
        stored !== null ? stored === '1' : root.dataset.sidebarCollapsed === '1';
    setCollapsed(initialCollapsed);

    toggleBtn?.addEventListener('click', () => {
        const next = !sidebar.classList.contains('w-16');
        setCollapsed(next);
    });
})();
