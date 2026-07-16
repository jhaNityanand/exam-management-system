export const THEME_KEY = 'ems.theme';

export function readServerThemeDefault() {
    return document.documentElement.dataset.themeDefault || 'system';
}

export function readThemePreference() {
    try {
        const stored = localStorage.getItem(THEME_KEY);
        if (stored) {
            return stored;
        }
    } catch (error) {
        //
    }

    return readServerThemeDefault();
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

export function resolveActualTheme(theme) {
    return theme === 'system' ? (resolveSystemDark() ? 'dark' : 'light') : theme;
}

export function applyTheme(theme = readThemePreference()) {
    const root = document.documentElement;
    const actualTheme = resolveActualTheme(theme);
    const previous = root.classList.contains('dark') ? 'dark' : 'light';

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

    if (previous !== actualTheme) {
        window.dispatchEvent(new CustomEvent('ems:themechange', {
            detail: { theme, actualTheme, previous },
        }));
    }
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
    if (themeToggleBtn && !themeToggleBtn.dataset.themeBound) {
        themeToggleBtn.dataset.themeBound = '1';
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = readThemePreference();
            const actualTheme = resolveActualTheme(currentTheme);
            const newTheme = actualTheme === 'dark' ? 'light' : 'dark';
            writeThemePreference(newTheme);
            applyTheme(newTheme);
        });
    }

    document.querySelectorAll('[data-theme-option]').forEach((option) => {
        if (option.dataset.themeBound) {
            return;
        }

        option.dataset.themeBound = '1';
        option.addEventListener('click', () => {
            const theme = option.dataset.themeOption || 'light';
            writeThemePreference(theme);
            applyTheme(theme);
        });
    });
}
