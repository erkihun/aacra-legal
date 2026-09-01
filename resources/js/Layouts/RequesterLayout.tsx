import ApplicationLogo from '@/Components/ApplicationLogo';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { type PropsWithChildren, useState } from 'react';

type RequesterUser = {
    id: string;
    full_name: string;
    email: string;
    department?: { name_en: string; name_am?: string | null };
};

type RequesterPageProps = PageProps & {
    requesterAuth?: { user: RequesterUser };
};

type NavItem = {
    label: string;
    routeName: string;
    pattern: string;
};

export default function RequesterLayout({
    children,
    breadcrumbs,
}: PropsWithChildren<{ breadcrumbs?: Array<{ label: string; href?: string }> }>) {
    const { t, locale } = useI18n();
    const { props, url } = usePage<RequesterPageProps>();
    const appMeta = props.appMeta;
    const requester = props.requesterAuth?.user;
    const [mobileOpen, setMobileOpen] = useState(false);

    const deptName = locale === 'am'
        ? requester?.department?.name_am ?? requester?.department?.name_en
        : requester?.department?.name_en;

    const navItems: NavItem[] = [
        { label: t('requester.nav_dashboard'), routeName: 'requester.dashboard', pattern: '/requester/dashboard' },
        { label: t('requester.nav_advisory'), routeName: 'requester.advisory.index', pattern: '/requester/advisory' },
        { label: t('requester.nav_lawsuit'), routeName: 'requester.lawsuit-requests.index', pattern: '/requester/lawsuit-requests' },
    ];

    const isActive = (pattern: string) => url.startsWith(pattern);

    const logout = () => {
        router.post(route('requester.logout'));
    };

    return (
        <div className="min-h-screen bg-app">
            {/* Top navigation bar */}
            <header className="sticky top-0 z-30 border-b border-[color:var(--border)] bg-[color:var(--surface)] shadow-sm">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
                    {/* Logo / brand */}
                    <Link href={route('requester.dashboard')} className="flex items-center gap-3 shrink-0">
                        <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-[color:var(--primary-soft)] text-[color:var(--primary)]">
                            {appMeta?.logo_url ? (
                                <img src={appMeta.logo_url} alt={appMeta.application_short_name} className="h-full w-full object-cover rounded-xl" />
                            ) : (
                                <ApplicationLogo className="h-5 w-5 fill-current" />
                            )}
                        </span>
                        <div className="hidden sm:block">
                            <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                                {t('requester.portal_name')}
                            </p>
                            <p className="text-xs text-[color:var(--muted)]">{appMeta?.organization_name}</p>
                        </div>
                    </Link>

                    {/* Desktop nav */}
                    <nav className="hidden items-center gap-1 md:flex">
                        {navItems.map((item) => (
                            <Link
                                key={item.routeName}
                                href={route(item.routeName)}
                                className={`rounded-xl px-4 py-2 text-sm font-medium transition ${
                                    isActive(item.pattern)
                                        ? 'bg-[color:var(--primary)] text-white'
                                        : 'text-[color:var(--text)] hover:bg-[color:var(--surface-muted)]'
                                }`}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    {/* Right side controls */}
                    <div className="flex items-center gap-3">
                        {(props.availableLocales?.length ?? 0) > 1 ? <LanguageSwitcher /> : null}
                        {appMeta?.appearance?.allow_user_theme_switching ? <ThemeSwitcher /> : null}

                        {requester ? (
                            <div className="flex items-center gap-2">
                                <div className="hidden text-right sm:block">
                                    <p className="text-sm font-semibold text-[color:var(--text)]">{requester.full_name}</p>
                                    <p className="text-xs text-[color:var(--muted)]">{deptName}</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={logout}
                                    className="btn-base btn-secondary focus-ring text-xs"
                                >
                                    {t('common.logout')}
                                </button>
                            </div>
                        ) : null}

                        {/* Mobile menu toggle */}
                        <button
                            type="button"
                            onClick={() => setMobileOpen(!mobileOpen)}
                            className="flex h-9 w-9 items-center justify-center rounded-xl border border-[color:var(--border)] md:hidden"
                            aria-label="Toggle menu"
                        >
                            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                                {mobileOpen
                                    ? <path strokeLinecap="round" d="M6 18L18 6M6 6l12 12" />
                                    : <path strokeLinecap="round" d="M4 6h16M4 12h16M4 18h16" />}
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Mobile nav drawer */}
                {mobileOpen ? (
                    <div className="border-t border-[color:var(--border)] bg-[color:var(--surface)] px-4 pb-4 md:hidden">
                        <nav className="flex flex-col gap-1 pt-2">
                            {navItems.map((item) => (
                                <Link
                                    key={item.routeName}
                                    href={route(item.routeName)}
                                    onClick={() => setMobileOpen(false)}
                                    className={`rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                                        isActive(item.pattern)
                                            ? 'bg-[color:var(--primary)] text-white'
                                            : 'text-[color:var(--text)] hover:bg-[color:var(--surface-muted)]'
                                    }`}
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </nav>
                    </div>
                ) : null}
            </header>

            {/* Breadcrumbs */}
            {breadcrumbs && breadcrumbs.length > 0 ? (
                <div className="border-b border-[color:var(--border)]/60 bg-[color:var(--surface-muted)]">
                    <div className="mx-auto flex max-w-7xl items-center gap-2 px-4 py-2 sm:px-6">
                        {breadcrumbs.map((crumb, i) => (
                            <span key={i} className="flex items-center gap-2">
                                {i > 0 ? <span className="text-[color:var(--muted)]">/</span> : null}
                                {crumb.href ? (
                                    <Link href={crumb.href} className="text-xs text-[color:var(--primary)] hover:underline">
                                        {crumb.label}
                                    </Link>
                                ) : (
                                    <span className="text-xs text-[color:var(--muted)]">{crumb.label}</span>
                                )}
                            </span>
                        ))}
                    </div>
                </div>
            ) : null}

            {/* Main content */}
            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6">
                {children}
            </main>
        </div>
    );
}
