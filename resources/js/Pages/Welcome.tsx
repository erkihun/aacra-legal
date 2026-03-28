import ApplicationLogo from '@/Components/ApplicationLogo';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth, appMeta, availableLocales }: PageProps) {
    const { t } = useI18n();
    const footerText = appMeta.footer_text || appMeta.organization_description || t('welcome.footer');

    return (
        <>
            <Head title={t('welcome.title')} />

            <div className="min-h-screen bg-app text-[color:var(--text)]">
                <div className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,0.14),_transparent_22%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.10),_transparent_22%)] dark:bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(56,189,248,0.14),_transparent_24%)]" />

                <div className="mx-auto flex min-h-screen max-w-6xl flex-col justify-between px-4 py-8 sm:px-6 lg:px-8">
                    <header className="flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <span className="rounded-2xl bg-cyan-400/15 p-3 text-cyan-300">
                                {appMeta.logo_url ? (
                                    <img src={appMeta.logo_url} alt={appMeta.application_short_name} className="h-10 w-10 rounded-xl object-cover" />
                                ) : (
                                    <ApplicationLogo className="h-10 w-10 fill-current" />
                                )}
                            </span>
                            <div>
                                <p className="text-xs font-semibold uppercase text-cyan-300">
                                    {appMeta.application_short_name}
                                </p>
                                <p className="text-sm text-[color:var(--muted-strong)]">{appMeta.application_name}</p>
                                <p className="mt-1 text-xs text-[color:var(--muted)]">{appMeta.organization_name}</p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center justify-end gap-3">
                            {availableLocales.length > 1 ? (
                                <div className="hidden sm:block">
                                    <LanguageSwitcher />
                                </div>
                            ) : null}
                            {appMeta.appearance.allow_user_theme_switching ? <ThemeSwitcher /> : null}
                            {auth.user ? (
                                <Link
                                    href={route(appMeta.default_dashboard_route)}
                                    className="btn-base btn-primary focus-ring"
                                >
                                    {t('navigation.dashboard')}
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="btn-base btn-secondary focus-ring"
                                    >
                                        {t('auth.login')}
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="btn-base btn-primary focus-ring"
                                    >
                                        {t('auth.register')}
                                    </Link>
                                </>
                            )}
                        </div>
                    </header>

                    <main className="grid gap-8 py-16 lg:grid-cols-[1.2fr,0.8fr] lg:items-center">
                        <section className="space-y-6">
                            {appMeta.security.maintenance_banner_enabled ? (
                                <div className="section-shell border-amber-400/30 bg-amber-500/10 py-4 text-sm text-amber-700 dark:text-amber-200">
                                    {t('common.maintenance_notice')}
                                </div>
                            ) : null}

                            <div className="inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-xs font-semibold uppercase text-cyan-200">
                                {t('welcome.eyebrow')}
                            </div>
                            <div className="space-y-4">
                                <h1 className="max-w-4xl text-4xl font-semibold tracking-tight text-[color:var(--text)] sm:text-5xl">
                                    {t('welcome.heading')}
                                </h1>
                                <p className="max-w-3xl text-base leading-7 text-[color:var(--muted-strong)] sm:text-lg">
                                    {t('welcome.description')}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-3">
                                <Metric label={t('welcome.metric.workflows')} value="2" />
                                <Metric label={t('welcome.metric.locales')} value={`${t('settings.locale_options.en')} / ${t('settings.locale_options.am')}`} />
                                <Metric label={t('welcome.metric.audit')} value={t('welcome.metric.audit_value')} />
                            </div>
                        </section>

                        <section className="surface-card-strong p-8 shadow-[0_30px_80px_-40px_rgba(14,165,233,0.35)]">
                            <p className="text-xs font-semibold uppercase text-cyan-300">
                                {t('welcome.features_title')}
                            </p>
                            <div className="mt-6 space-y-4">
                                <Feature
                                    title={t('welcome.feature.advisory_title')}
                                    description={t('welcome.feature.advisory_description')}
                                />
                                <Feature
                                    title={t('welcome.feature.cases_title')}
                                    description={t('welcome.feature.cases_description')}
                                />
                                <Feature
                                    title={t('welcome.feature.reporting_title')}
                                    description={t('welcome.feature.reporting_description')}
                                />
                            </div>
                        </section>
                    </main>

                    <footer className="border-t pt-6 text-sm text-[color:var(--muted)]" style={{ borderColor: 'var(--border)' }}>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <span>{footerText}</span>
                            <span>{appMeta.support.email ?? appMeta.organization_name}</span>
                        </div>
                    </footer>
                </div>
            </div>
        </>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return (
        <div className="surface-muted px-5 py-4">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-xl font-semibold text-[color:var(--text)]">{value}</p>
        </div>
    );
}

function Feature({ title, description }: { title: string; description: string }) {
    return (
        <article className="surface-muted px-5 py-4">
            <h2 className="text-lg font-semibold text-[color:var(--text)]">{title}</h2>
            <p className="mt-2 text-sm leading-6 text-[color:var(--muted-strong)]">{description}</p>
        </article>
    );
}
