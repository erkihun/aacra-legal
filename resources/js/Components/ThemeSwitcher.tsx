import { useI18n } from '@/lib/i18n';
import { ThemeMode, useTheme } from '@/lib/theme';
import { cn } from '@/lib/cn';

const options: Array<{ value: ThemeMode; icon: JSX.Element; labelKey: string }> = [
    {
        value: 'light',
        labelKey: 'common.light',
        icon: (
            <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8">
                <circle cx="12" cy="12" r="4.25" />
                <path d="M12 2.75v2.5M12 18.75v2.5M5.46 5.46l1.76 1.76M16.78 16.78l1.76 1.76M2.75 12h2.5M18.75 12h2.5M5.46 18.54l1.76-1.76M16.78 7.22l1.76-1.76" />
            </svg>
        ),
    },
    {
        value: 'dark',
        labelKey: 'common.dark',
        icon: (
            <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8">
                <path d="M20.2 15.6A8.5 8.5 0 0 1 8.4 3.8a8.5 8.5 0 1 0 11.8 11.8Z" />
            </svg>
        ),
    },
];

export default function ThemeSwitcher() {
    const { theme, setTheme } = useTheme();
    const { t } = useI18n();

    return (
        <div
            className="surface-muted flex items-center gap-1 p-1"
            role="group"
            aria-label={t('common.theme')}
        >
            {options.map((option) => {
                const active = theme === option.value;

                return (
                    <button
                        key={option.value}
                        type="button"
                        onClick={() => setTheme(option.value)}
                        className={cn(
                            'focus-ring inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold transition',
                            active
                                ? 'bg-[var(--primary)] text-white shadow-sm dark:text-slate-950'
                                : 'text-[color:var(--muted-strong)] hover:bg-[color:var(--surface-strong)]',
                        )}
                        aria-pressed={active}
                    >
                        {option.icon}
                        <span className="hidden sm:inline">{t(option.labelKey)}</span>
                    </button>
                );
            })}
        </div>
    );
}
