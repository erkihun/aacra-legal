import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import { cn } from '@/lib/cn';
import { ThemeMode, useTheme } from '@/lib/theme';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const options: Array<{ value: ThemeMode; titleKey: string; descriptionKey: string }> = [
    {
        value: 'light',
        titleKey: 'common.light',
        descriptionKey: 'profile.theme_light_description',
    },
    {
        value: 'dark',
        titleKey: 'common.dark',
        descriptionKey: 'profile.theme_dark_description',
    },
];

export default function AppearancePreferencesForm({
    className = '',
}: {
    className?: string;
}) {
    const { t } = useI18n();
    const { theme, setTheme } = useTheme();
    const { props } = usePage<PageProps>();
    const allowThemeSwitching = props.appMeta.appearance.allow_user_theme_switching;
    const [recentlySaved, setRecentlySaved] = useState(false);

    useEffect(() => {
        if (!recentlySaved) {
            return undefined;
        }

        const timeout = window.setTimeout(() => setRecentlySaved(false), 1800);

        return () => window.clearTimeout(timeout);
    }, [recentlySaved]);

    return (
        <section className={className}>
            <SectionHeader
                title={t('profile.appearance_title')}
                description={t('profile.appearance_help')}
            />

            <div className="mt-6 grid gap-4 md:grid-cols-2">
                {options.map((option) => {
                    const active = theme === option.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => {
                                if (!allowThemeSwitching) {
                                    return;
                                }

                                setTheme(option.value);
                                setRecentlySaved(true);
                            }}
                            disabled={!allowThemeSwitching}
                            className={cn(
                                'focus-ring rounded-[1.5rem] border p-5 text-left transition disabled:cursor-not-allowed disabled:opacity-70',
                                active
                                    ? 'border-[color:var(--primary)] bg-[color:var(--primary-soft)]'
                                    : 'border-[color:var(--border)] bg-[color:var(--surface-muted)] hover:bg-[color:var(--surface-strong)]',
                            )}
                            aria-pressed={active}
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h3 className="text-base font-semibold text-[color:var(--text)]">{t(option.titleKey)}</h3>
                                    <p className="mt-2 text-sm leading-6 text-[color:var(--muted-strong)]">
                                        {t(option.descriptionKey)}
                                    </p>
                                </div>
                                <span
                                    className={cn(
                                        'mt-1 inline-flex h-5 w-5 shrink-0 rounded-full border',
                                        active
                                            ? 'border-[color:var(--primary)] bg-[color:var(--primary)]'
                                            : 'border-[color:var(--border)] bg-transparent',
                                    )}
                                />
                            </div>
                        </button>
                    );
                })}
            </div>

            <SurfaceCard className="mt-4 py-4">
                <p className="text-sm text-[color:var(--muted-strong)]">
                    {!allowThemeSwitching
                        ? t('profile.theme_locked_notice')
                        : recentlySaved
                          ? t('profile.saved')
                          : t('profile.theme_help_secondary')}
                </p>
            </SurfaceCard>
        </section>
    );
}
