export const THEME_KEY = 'ems.theme';

export function readThemePreference() {
    try {
        return localStorage.getItem(THEME_KEY) || 'light';
    } catch (error) {
        return 'light';
    }
}

export function writeThemePreference(theme) {
    try {
        localStorage.setItem(THEME_KEY, theme);
    } catch (error) {
        //
    }
}

export function resolveSystemDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function applyTheme(theme = readThemePreference()) {
    const root = document.documentElement;
    const actualTheme = theme === 'system' ? (resolveSystemDark() ? 'dark' : 'light') : theme;

    root.classList.toggle('dark', actualTheme === 'dark');
    root.dataset.theme = theme;
    root.style.colorScheme = actualTheme;

    document.querySelectorAll('[data-theme-trigger]').forEach((trigger) => {
        trigger.dataset.activeTheme = theme;
        const label = trigger.querySelector('[data-theme-label]');
        if (label) {
            label.textContent = theme.charAt(0).toUpperCase() + theme.slice(1);
        }
    });

    document.querySelectorAll('[data-theme-option]').forEach((option) => {
        const isActive = option.dataset.themeOption === theme;
        option.classList.toggle('bg-slate-100', isActive);
        option.classList.toggle('text-slate-950', isActive);
        option.classList.toggle('dark:bg-slate-700', isActive);
        option.classList.toggle('dark:text-white', isActive);
    });
}

export function initThemeControls() {
    applyTheme();

    const media = window.matchMedia('(prefers-color-scheme: dark)');
    const mediaHandler = () => {
        if (readThemePreference() === 'system') {
            applyTheme('system');
        }
    };

    if (typeof media.addEventListener === 'function') {
        media.addEventListener('change', mediaHandler);
    } else if (typeof media.addListener === 'function') {
        media.addListener(mediaHandler);
    }

    const themeToggleBtn = document.getElementById('theme-toggle-btn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = readThemePreference();
            const actualTheme = currentTheme === 'system' ? (resolveSystemDark() ? 'dark' : 'light') : currentTheme;
            const newTheme = actualTheme === 'dark' ? 'light' : 'dark';
            writeThemePreference(newTheme);
            applyTheme(newTheme);
        });
    }

    document.querySelectorAll('[data-theme-option]').forEach((option) => {
        option.addEventListener('click', () => {
            const theme = option.dataset.themeOption || 'light';
            writeThemePreference(theme);
            applyTheme(theme);
        });
    });
}
