import ApplicationLogo from '@/Components/ApplicationLogo';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    const { t } = useI18n();
    const { props } = usePage<PageProps>();
    const appMeta = props.appMeta;
    const footerText = appMeta.footer_text || appMeta.organization.description || t('welcome.footer');

    return (
        <div className="min-h-screen bg-app">
            <div className="mx-auto flex min-h-screen max-w-6xl flex-col justify-between px-4 py-6 sm:px-6 lg:px-8">
                <header className="flex items-center justify-between gap-4">
                    <Link href={route('home')} className="flex items-center gap-3">
                        <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[color:var(--primary-soft)] text-[color:var(--primary)]">
                            {appMeta.logo_url ? (
                                <img src={appMeta.logo_url} alt={appMeta.application_short_name} className="h-full w-full object-cover" />
                            ) : (
                                <ApplicationLogo className="h-8 w-8 fill-current" />
                            )}
                        </span>
                        <div>
                            <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                                {appMeta.application_short_name}
                            </p>
                            <p className="text-sm text-[color:var(--muted-strong)]">{appMeta.application_name}</p>
                            <p className="mt-1 text-xs text-[color:var(--muted)]">{appMeta.legal_department_name || appMeta.organization_name}</p>
                        </div>
                    </Link>
                    <div className="flex items-center gap-3">
                        {props.availableLocales.length > 1 ? <LanguageSwitcher /> : null}
                        {appMeta.appearance.allow_user_theme_switching ? <ThemeSwitcher /> : null}
                    </div>
                </header>

                <div className="grid gap-10 py-8 lg:grid-cols-[1.1fr,0.9fr] lg:items-center">
                    <section className="hidden lg:block">
                        <div className="max-w-xl space-y-5">
                            <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                                {t('welcome.eyebrow')}
                            </p>
                            <h1 className="text-5xl font-semibold leading-tight text-[color:var(--text)]">
                                {appMeta.legal_department_name || t('welcome.heading')}
                            </h1>
                            <p className="text-lg leading-8 text-[color:var(--muted-strong)]">
                                {appMeta.tagline || t('welcome.description')}
                            </p>
                            {(appMeta.support.email || appMeta.support.phone) ? (
                                <div className="flex flex-wrap gap-4 text-sm text-[color:var(--muted)]">
                                    {appMeta.support.email ? <span>{appMeta.support.email}</span> : null}
                                    {appMeta.support.phone ? <span>{appMeta.support.phone}</span> : null}
                                </div>
                            ) : null}
                        </div>
                    </section>

                    <div className="w-full max-w-xl justify-self-center space-y-4">
                        {appMeta.security.maintenance_banner_enabled ? (
                            <div className="section-shell border-amber-400/30 bg-amber-500/10 py-4 text-sm text-amber-700 dark:text-amber-200">
                                {t('common.maintenance_notice')}
                            </div>
                        ) : null}

                        <div className="surface-card-strong px-6 py-6 sm:px-8">
                            {children}
                        </div>
                    </div>
                </div>

                <footer className="flex flex-wrap items-center justify-between gap-3 border-t pt-5 text-sm text-[color:var(--muted)]" style={{ borderColor: 'var(--border)' }}>
                    <p>{footerText}</p>
                    <p>{appMeta.organization_name}</p>
                </footer>
            </div>
        </div>
    );
}
