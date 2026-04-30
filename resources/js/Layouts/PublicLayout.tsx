import ApplicationLogo from '@/Components/ApplicationLogo';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useMemo, useState } from 'react';

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
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const pageTitle = title ?? appMeta.application_name;
    const footerText = appMeta.footer_text || appMeta.organization.description || t('welcome.footer');
    const publicNavigation = useMemo<Array<{ href: string; label: string }>>(
        () => [
            { href: route('home'), label: t('public.nav.home') },
            { href: `${route('home')}#services`, label: t('public.nav.services') },
            { href: route('posts.index'), label: t('public.nav.updates') },
            { href: `${route('home')}#contact`, label: t('public.nav.contact') },
        ],
        [t],
    );
    const navigation = (
        <>
            <Link href={route('home')} className="flex min-w-0 items-center gap-3 lg:flex-1" onClick={() => setIsMobileMenuOpen(false)}>
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

            <div className="flex w-full items-center justify-end gap-2 sm:gap-3 lg:w-auto lg:flex-nowrap lg:gap-3 lg:shrink-0">
                <nav className="hidden items-center gap-1 lg:flex lg:flex-nowrap">
                    {publicNavigation.map((item) => (
                        <PublicNavLink key={item.href} href={item.href} label={item.label} />
                    ))}
                </nav>

                {availableLocales.length > 1 ? <LanguageSwitcher /> : null}
                {appMeta.appearance.allow_user_theme_switching ? <ThemeSwitcher /> : null}
                <div className="hidden items-center gap-2 lg:flex">
                    {headerAction}
                    <PublicAuthActions
                        isAuthenticated={auth.user !== null}
                        defaultDashboardRoute={appMeta.default_dashboard_route}
                        openPortalLabel={t('public.actions.open_portal')}
                        loginLabel={t('auth.login')}
                        createAccountLabel={t('public.actions.create_account')}
                    />
                </div>

                <button
                    type="button"
                    className="focus-ring inline-flex h-11 w-11 items-center justify-center rounded-full border border-[color:var(--border)] bg-[color:var(--surface-muted)] text-[color:var(--muted-strong)] transition hover:bg-[color:var(--surface-strong)] lg:hidden"
                    aria-expanded={isMobileMenuOpen}
                    aria-controls="public-mobile-menu"
                    aria-label={isMobileMenuOpen ? t('common.close') : t('common.menu')}
                    onClick={() => setIsMobileMenuOpen((current) => !current)}
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5 fill-none stroke-current stroke-[1.8]">
                        {isMobileMenuOpen ? (
                            <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" strokeLinejoin="round" />
                        ) : (
                            <>
                                <path d="M4 7h16" strokeLinecap="round" />
                                <path d="M4 12h16" strokeLinecap="round" />
                                <path d="M4 17h16" strokeLinecap="round" />
                            </>
                        )}
                    </svg>
                </button>
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
                    <header className="surface-card-strong flex flex-wrap items-center justify-between gap-4 border border-[color:var(--border)]/80 bg-[color:color-mix(in srgb,var(--surface-elevated) 88%,transparent)] px-4 py-4 shadow-[0_18px_48px_-28px_rgba(15,23,42,0.45)] backdrop-blur-xl sm:px-5 sm:py-5 lg:flex-nowrap lg:gap-6 lg:px-6">
                        {navigation}

                        {isMobileMenuOpen ? (
                            <div
                                id="public-mobile-menu"
                                className="surface-muted w-full space-y-4 border border-[color:var(--border)]/80 p-4 lg:hidden"
                            >
                                <nav className="grid gap-2">
                                    {publicNavigation.map((item) => (
                                        <PublicNavLink
                                            key={`mobile-${item.href}`}
                                            href={item.href}
                                            label={item.label}
                                            mobile
                                            onClick={() => setIsMobileMenuOpen(false)}
                                        />
                                    ))}
                                </nav>

                                <div className="grid gap-2 sm:grid-cols-2">
                                    <PublicAuthActions
                                        isAuthenticated={auth.user !== null}
                                        defaultDashboardRoute={appMeta.default_dashboard_route}
                                        openPortalLabel={t('public.actions.open_portal')}
                                        loginLabel={t('auth.login')}
                                        createAccountLabel={t('public.actions.create_account')}
                                        onNavigate={() => setIsMobileMenuOpen(false)}
                                        stacked
                                    />
                                </div>

                                {headerAction ? <div className="pt-1">{headerAction}</div> : null}
                            </div>
                        ) : null}
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
                        <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-[1.1fr,0.9fr,0.8fr]">
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
                                <div className="mt-3 space-y-2 break-words">
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

function PublicNavLink({
    href,
    label,
    mobile = false,
    onClick,
}: {
    href: string;
    label: string;
    mobile?: boolean;
    onClick?: () => void;
}) {
    return (
        <Link
            href={href}
            onClick={onClick}
            className={
                mobile
                    ? 'focus-ring rounded-2xl px-4 py-3 text-sm font-medium text-[color:var(--muted-strong)] transition hover:bg-[color:var(--surface-strong)] hover:text-[color:var(--text)]'
                    : 'focus-ring shrink-0 whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium text-[color:var(--muted-strong)] transition hover:bg-[color:var(--surface-muted)] hover:text-[color:var(--text)]'
            }
        >
            {label}
        </Link>
    );
}

function PublicAuthActions({
    isAuthenticated,
    defaultDashboardRoute,
    openPortalLabel,
    loginLabel,
    createAccountLabel,
    onNavigate,
    stacked = false,
}: {
    isAuthenticated: boolean;
    defaultDashboardRoute: string;
    openPortalLabel: string;
    loginLabel: string;
    createAccountLabel: string;
    onNavigate?: () => void;
    stacked?: boolean;
}) {
    const primaryClass = stacked ? 'btn-base btn-primary focus-ring w-full' : 'btn-base btn-primary focus-ring';
    const secondaryClass = stacked ? 'btn-base btn-secondary focus-ring w-full' : 'btn-base btn-secondary focus-ring';

    if (isAuthenticated) {
        return (
            <Link href={route(defaultDashboardRoute)} className={primaryClass} onClick={onNavigate}>
                {openPortalLabel}
            </Link>
        );
    }

    return (
        <>
            <Link href={route('login')} className={secondaryClass} onClick={onNavigate}>
                {loginLabel}
            </Link>
            <Link href={route('register')} className={primaryClass} onClick={onNavigate}>
                {createAccountLabel}
            </Link>
        </>
    );
}
