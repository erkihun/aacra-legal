import { useEffect, useState } from 'react';

export type ThemeMode = 'light' | 'dark';

const STORAGE_KEY = 'ldms-theme';

export function getPreferredTheme(): ThemeMode {
    if (typeof window === 'undefined') {
        return 'light';
    }

    const themeSwitchingEnabled = document.body.dataset.themeSwitchingEnabled !== 'false';

    if (!themeSwitchingEnabled) {
        const lockedTheme = document.body.dataset.defaultTheme;

        return lockedTheme === 'dark' ? 'dark' : 'light';
    }

    const storedTheme = window.localStorage.getItem(STORAGE_KEY);

    if (storedTheme === 'light' || storedTheme === 'dark') {
        return storedTheme;
    }

    const configuredTheme = document.body.dataset.defaultTheme;

    if (configuredTheme === 'light' || configuredTheme === 'dark') {
        return configuredTheme;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function applyTheme(theme: ThemeMode) {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.dataset.theme = theme;
    window.localStorage.setItem(STORAGE_KEY, theme);
}

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    applyTheme(getPreferredTheme());
}

export function useTheme() {
    const [theme, setTheme] = useState<ThemeMode>(() => getPreferredTheme());

    useEffect(() => {
        applyTheme(theme);
    }, [theme]);

    return {
        theme,
        setTheme,
        toggleTheme: () => setTheme((current) => (current === 'dark' ? 'light' : 'dark')),
    };
}
