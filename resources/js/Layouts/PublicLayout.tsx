import ApplicationLogo from '@/Components/ApplicationLogo';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';

type PublicLayoutProps = PropsWithChildren<{
    title?: string;
    description?: string;
    headerAction?: ReactNode;
}>;

export default function PublicLayout({
    title,
    description,
    headerAction,
    children,
}: PublicLayoutProps) {
    const { props } = usePage<PageProps>();
    const { t } = useI18n();
    const { appMeta, auth, availableLocales } = props;
    const pageTitle = title ?? appMeta.application_name;
    const footerText = appMeta.footer_text || appMeta.organization.description || t('welcome.footer');
    const navigation = (
        <>
            <Link href={route('home')} className="flex min-w-0 items-center gap-3 lg:flex-1">
                <span className="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-[color:var(--primary-soft)] text-[color:var(--primary)]">
                    {appMeta.logo_url ? (
                        <img
                            src={appMeta.logo_url}
                            alt={appMeta.application_short_name}
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <ApplicationLogo className="h-8 w-8 fill-current" />
                    )}
                </span>
                <div className="min-w-0">
                    <p className="truncate text-xs font-semibold uppercase text-[color:var(--primary)]">
                        {appMeta.application_short_name}
                    </p>
                    <p className="truncate text-sm font-semibold text-[color:var(--text)]">
                        {appMeta.application_name}
                    </p>
                    <p className="truncate text-xs text-[color:var(--muted)]">
                        {appMeta.legal_department_name || appMeta.organization_name}
                    </p>
                </div>
            </Link>

            <div className="flex w-full flex-wrap items-center justify-end gap-2 lg:w-auto lg:flex-nowrap lg:gap-3 lg:shrink-0">
                <nav className="hidden items-center gap-1 lg:flex lg:flex-nowrap">
                    <PublicNavLink href={route('home')} label={t('public.nav.home')} />
                    <PublicNavLink href={`${route('home')}#services`} label={t('public.nav.services')} />
                    <PublicNavLink href={route('posts.index')} label={t('public.nav.updates')} />
                    <PublicNavLink href={`${route('home')}#contact`} label={t('public.nav.contact')} />
                </nav>

                {availableLocales.length > 1 ? <LanguageSwitcher /> : null}
                {appMeta.appearance.allow_user_theme_switching ? <ThemeSwitcher /> : null}
                {headerAction}
                {auth.user ? (
                    <Link href={route(appMeta.default_dashboard_route)} className="btn-base btn-primary focus-ring">
                        {t('public.actions.open_portal')}
                    </Link>
                ) : (
                    <>
                        <Link href={route('login')} className="btn-base btn-secondary focus-ring">
                            {t('auth.login')}
                        </Link>
                        <Link href={route('register')} className="btn-base btn-primary focus-ring">
                            {t('public.actions.create_account')}
                        </Link>
                    </>
                )}
            </div>
        </>
    );

    return (
        <>
            <Head title={pageTitle}>
                {description ? <meta name="description" content={description} /> : null}
            </Head>

            <div className="public-shell min-h-screen bg-app text-[color:var(--text)]">
                <div className="absolute inset-x-0 top-0 -z-10 h-[32rem] bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.16),_transparent_25%),radial-gradient(circle_at_top_right,_rgba(14,165,233,0.12),_transparent_26%)] dark:bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.18),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(34,197,94,0.08),_transparent_24%)]" />

                <div className="fixed inset-x-0 top-0 z-50 px-4 py-4 sm:px-6 lg:px-8 xl:px-10 2xl:px-12">
                    <header className="surface-card-strong flex flex-wrap items-center justify-between gap-4 border border-[color:var(--border)]/80 bg-[color:color-mix(in srgb,var(--surface-elevated) 88%,transparent)] px-5 py-4 shadow-[0_18px_48px_-28px_rgba(15,23,42,0.45)] backdrop-blur-xl sm:px-6 lg:flex-nowrap lg:gap-6">
                        {navigation}
                    </header>
                </div>

                <div className="px-4 py-4 sm:px-6 lg:px-8 xl:px-10 2xl:px-12">
                    <header
                        aria-hidden="true"
                        className="surface-card-strong invisible flex flex-wrap items-center justify-between gap-4 border border-transparent px-5 py-4 sm:px-6 lg:flex-nowrap lg:gap-6"
                    >
                        {navigation}
                    </header>
                </div>

                <div className="public-content flex min-h-screen w-full flex-col px-4 pb-4 sm:px-6 sm:pb-6 lg:px-8 lg:pb-8 xl:px-10 2xl:px-12">
                    <div className="flex-1 py-6 sm:py-8">{children}</div>

                    <footer className="mt-auto border-t border-[color:var(--border)] px-1 py-6 text-sm text-[color:var(--muted)]">
                        <div className="grid gap-6 lg:grid-cols-[1.1fr,0.9fr,0.8fr]">
                            <div>
                                <p className="text-sm font-semibold text-[color:var(--text)]">{appMeta.application_name}</p>
                                <p className="mt-2 text-sm font-medium text-[color:var(--muted-strong)]">
                                    {appMeta.legal_department_name || appMeta.organization_name}
                                </p>
                                <p className="mt-2 max-w-xl leading-6">{footerText}</p>
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-[color:var(--text)]">{t('public.footer.quick_links')}</p>
                                <div className="mt-3 flex flex-col gap-2">
                                    <Link href={route('home')} className="transition hover:text-[color:var(--text)]">
                                        {t('public.nav.home')}
                                    </Link>
                                    <Link href={route('posts.index')} className="transition hover:text-[color:var(--text)]">
                                        {t('public.nav.updates')}
                                    </Link>
                                    <Link href={auth.user ? route('advisory.index') : route('login')} className="transition hover:text-[color:var(--text)]">
                                        {t('public.actions.track_requests')}
                                    </Link>
                                </div>
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-[color:var(--text)]">{t('public.contact.title')}</p>
                                <div className="mt-3 space-y-2">
                                    {appMeta.organization.address ? <p>{appMeta.organization.address}</p> : null}
                                    {appMeta.support.email ? <p>{appMeta.support.email}</p> : null}
                                    {appMeta.support.phone ? <p>{appMeta.support.phone}</p> : null}
                                    {appMeta.organization.working_hours_text ? <p>{appMeta.organization.working_hours_text}</p> : null}
                                </div>
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        </>
    );
}

function PublicNavLink({ href, label }: { href: string; label: string }) {
    return (
        <Link
            href={href}
            className="focus-ring shrink-0 whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium text-[color:var(--muted-strong)] transition hover:bg-[color:var(--surface-muted)] hover:text-[color:var(--text)]"
        >
            {label}
        </Link>
    );
}
