export const SIDEBAR_KEY = 'ems.sidebarCollapsed';

function debounce(fn, delay = 250) {
    let timer;
    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => fn(...args), delay);
    };
}

export function initSidebar() {
    const root = document.getElementById('panel-root');
    const sidebar = document.getElementById('app-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggleButtons = document.querySelectorAll('[data-sidebar-toggle]');
    const closeButtons = document.querySelectorAll('[data-sidebar-close]');

    if (!root || !sidebar) {
        return;
    }

    const isDesktop = () => window.innerWidth >= 1024;

    const setCollapsed = (collapsed) => {
        root.dataset.sidebarCollapsed = collapsed ? '1' : '0';
        
        if (isDesktop()) {
            sidebar.style.width = collapsed ? '80px' : '288px';
        }

        document.querySelectorAll('[data-sidebar-toggle-icon]').forEach((icon) => {
            icon.style.transform = collapsed ? 'rotate(180deg)' : 'rotate(0deg)';
        });

        try {
            localStorage.setItem(SIDEBAR_KEY, collapsed ? '1' : '0');
        } catch (error) {
            //
        }
    };

    const setMobileOpen = (open) => {
        root.dataset.sidebarOpen = open ? '1' : '0';
        sidebar.classList.toggle('-translate-x-full', !open);
        backdrop?.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open && !isDesktop());
    };

    const storedCollapsed = (() => {
        try {
            return localStorage.getItem(SIDEBAR_KEY);
        } catch (error) {
            return null;
        }
    })();

    setCollapsed(storedCollapsed ? storedCollapsed === '1' : root.dataset.sidebarCollapsed === '1');
    setMobileOpen(false);

    toggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (isDesktop()) {
                setCollapsed(root.dataset.sidebarCollapsed !== '1');
                return;
            }

            setMobileOpen(root.dataset.sidebarOpen !== '1');
        });
    });

    [...closeButtons, backdrop].filter(Boolean).forEach((button) => {
        button.addEventListener('click', () => setMobileOpen(false));
    });

    window.addEventListener(
        'resize',
        debounce(() => {
            if (isDesktop()) {
                setMobileOpen(false);
            }
        }, 120)
    );
}
