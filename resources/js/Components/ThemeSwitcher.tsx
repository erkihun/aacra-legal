import { useTheme } from '@/lib/theme';
import { cn } from '@/lib/cn';

export default function ThemeSwitcher() {
    const { theme, toggleTheme } = useTheme();
    const isDark = theme === 'dark';
    const accessibleLabel = isDark ? 'Switch to light mode' : 'Switch to dark mode';

    return (
        <button
            type="button"
            onClick={toggleTheme}
            className={cn(
                'focus-ring inline-flex h-11 w-11 items-center justify-center rounded-full border text-[color:var(--muted-strong)] transition',
                'bg-[color:var(--surface-muted)] hover:bg-[color:var(--surface-strong)] active:scale-[0.98] active:bg-[color:var(--primary-soft)]',
            )}
            style={{ borderColor: 'var(--border)' }}
            aria-label={accessibleLabel}
            title={accessibleLabel}
            aria-pressed={isDark}
        >
            {isDark ? (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="4.25" />
                    <path d="M12 2.75v2.5M12 18.75v2.5M5.46 5.46l1.76 1.76M16.78 16.78l1.76 1.76M2.75 12h2.5M18.75 12h2.5M5.46 18.54l1.76-1.76M16.78 7.22l1.76-1.76" />
                </svg>
            ) : (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                    <path d="M20.2 15.6A8.5 8.5 0 0 1 8.4 3.8a8.5 8.5 0 1 0 11.8 11.8Z" />
                </svg>
            )}
        </button>
    );
}
