import ApplicationLogo from '@/Components/ApplicationLogo';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import Breadcrumbs, { BreadcrumbItem } from '@/Components/Ui/Breadcrumbs';
import { cn } from '@/lib/cn';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Dialog, DialogPanel, Menu, MenuButton, MenuItem, MenuItems, Transition, TransitionChild } from '@headlessui/react';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useEffect, useMemo, useState } from 'react';

type NavItem = {
    key: string;
    label: string;
    routeName?: string;
    permissions?: string[];
    icon: ReactNode;
};

type NavSection = {
    key: string;
    title: string;
    items: NavItem[];
};

const collapseStorageKey = 'ldms-sidebar-collapsed';

function navigationIcon(path: string) {
    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8">
            <path d={path} strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function SidebarItem({
    item,
    collapsed,
}: {
    item: NavItem;
    collapsed: boolean;
}) {
    const active = item.routeName ? route().current(item.routeName) : false;
    const { t } = useI18n();

    if (!item.routeName) {
        return (
            <div className="surface-muted flex items-center justify-between gap-3 px-3 py-3 text-[15px] text-[color:var(--muted-strong)]">
                <div className="flex items-center gap-3">
                    <span className="text-[color:var(--muted)]">{item.icon}</span>
                    {!collapsed ? <span>{item.label}</span> : null}
                </div>
                {!collapsed ? (
                    <span className="rounded-full bg-[color:var(--primary-soft)] px-2 py-1 text-[10px] font-semibold uppercase text-[color:var(--primary)]">
                        {t('common.coming_soon')}
                    </span>
                ) : null}
            </div>
        );
    }

    return (
        <Link
            href={route(item.routeName)}
            className={cn(
                'focus-ring flex items-center gap-3 rounded-2xl px-3 py-3 text-[15px] font-medium transition',
                active
                    ? 'bg-[var(--primary)] text-white shadow-sm dark:text-slate-950'
                    : 'text-[color:var(--muted-strong)] hover:bg-[color:var(--surface-muted)]',
                collapsed && 'justify-center px-2',
            )}
            title={collapsed ? item.label : undefined}
        >
            <span>{item.icon}</span>
            {!collapsed ? <span>{item.label}</span> : null}
        </Link>
    );
}

function SidebarContent({
    sections,
    collapsed,
}: {
    sections: NavSection[];
    collapsed: boolean;
}) {
    return (
        <div className="space-y-6">
            {sections.map((section) => (
                <section key={section.key} className="space-y-2">
                    {!collapsed ? (
                        <p className="px-3 text-[13px] font-semibold uppercase text-[color:var(--muted)]">
                            {section.title}
                        </p>
                    ) : null}
                    <div className="space-y-1.5">
                        {section.items.map((item) => (
                            <SidebarItem key={item.key} item={item} collapsed={collapsed} />
                        ))}
                    </div>
                </section>
            ))}
        </div>
    );
}

function UserMenu({ userName, userEmail }: { userName?: string; userEmail?: string }) {
    const { t } = useI18n();

    return (
        <Menu as="div" className="relative">
            <MenuButton className="focus-ring flex items-center gap-3 rounded-full border px-2 py-2 ps-3 text-left transition hover:bg-[color:var(--surface-muted)]" style={{ borderColor: 'var(--border)' }}>
                <span className="hidden text-right sm:block">
                    <span className="block text-sm font-semibold text-[color:var(--text)]">{userName}</span>
                    <span className="block text-xs text-[color:var(--muted)]">{userEmail}</span>
                </span>
                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-[color:var(--primary-soft)] text-sm font-semibold text-[color:var(--primary)]">
                    {userName?.slice(0, 2).toUpperCase()}
                </span>
            </MenuButton>
            <MenuItems anchor="bottom end" className="surface-card-strong z-20 mt-2 w-56 p-2 outline-none">
                <MenuItem>
                    <Link href={route('profile.edit')} className="block rounded-2xl px-3 py-2 text-sm text-[color:var(--text)] transition hover:bg-[color:var(--surface-muted)]">
                        {t('navigation.profile')}
                    </Link>
                </MenuItem>
                <MenuItem>
                    <Link
                        href={route('notifications.index')}
                        className="block rounded-2xl px-3 py-2 text-sm text-[color:var(--text)] transition hover:bg-[color:var(--surface-muted)]"
                    >
                        {t('navigation.notifications')}
                    </Link>
                </MenuItem>
                <div className="my-2 h-px" style={{ backgroundColor: 'var(--border)' }} />
                <MenuItem>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="block w-full rounded-2xl px-3 py-2 text-left text-sm text-rose-500 transition hover:bg-[color:var(--danger-soft)]"
                    >
                        {t('common.logout')}
                    </Link>
                </MenuItem>
            </MenuItems>
        </Menu>
    );
}

export default function AuthenticatedLayout({
    header,
    breadcrumbs = [],
    children,
}: PropsWithChildren<{ header?: ReactNode; breadcrumbs?: BreadcrumbItem[] }>) {
    const { props } = usePage<PageProps>();
    const user = props.auth.user;
    const { t } = useI18n();
    const appMeta = props.appMeta;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [collapsed, setCollapsed] = useState<boolean>(() => {
        if (typeof window === 'undefined') {
            return false;
        }

        const storedValue = window.localStorage.getItem(collapseStorageKey);

        if (storedValue !== null) {
            return storedValue === 'true';
        }

        return props.appMeta.appearance.sidebar_compact_default;
    });
    const unreadNotifications = props.notificationSummary?.unread_count ?? 0;

    useEffect(() => {
        window.localStorage.setItem(collapseStorageKey, String(collapsed));
    }, [collapsed]);

    const hasAnyPermission = (permissions?: string[]) =>
        permissions === undefined ||
        user?.roles.includes('Super Admin') ||
        permissions.some((permission) => user?.permissions.includes(permission));

    const sections: NavSection[] = useMemo(
        () => [
            {
                key: 'workspace',
                title: t('common.workspace'),
                items: [
                    {
                        key: 'dashboard',
                        label: t('navigation.dashboard'),
                        routeName: 'dashboard',
                        permissions: ['dashboard.view'],
                        icon: navigationIcon('M4 12.5 12 4l8 8.5V20H4v-7.5Z'),
                    },
                    {
                        key: 'advisory',
                        label: t('navigation.advisory_requests'),
                        routeName: 'advisory.index',
                        permissions: ['advisory.view_any', 'advisory.view_own', 'advisory-requests.view'],
                        icon: navigationIcon('M7 5.5h10A1.5 1.5 0 0 1 18.5 7v10a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 17V7A1.5 1.5 0 0 1 7 5.5Zm2.5 4h5m-5 4h5'),
                    },
                    {
                        key: 'cases',
                        label: t('navigation.legal_cases'),
                        routeName: 'cases.index',
                        permissions: ['cases.view_any', 'cases.view_own', 'legal-cases.view'],
                        icon: navigationIcon('M7 6.5h10A1.5 1.5 0 0 1 18.5 8v8A1.5 1.5 0 0 1 17 17.5H7A1.5 1.5 0 0 1 5.5 16V8A1.5 1.5 0 0 1 7 6.5Zm2 3h6m-6 3h6'),
                    },
                    {
                        key: 'reports',
                        label: t('navigation.reports'),
                        routeName: 'reports.index',
                        permissions: ['reports.view'],
                        icon: navigationIcon('M6 18.5V9.5m6 9V5.5m6 13v-6'),
                    },
                    {
                        key: 'notifications',
                        label: unreadNotifications > 0 ? `${t('navigation.notifications')} (${unreadNotifications})` : t('navigation.notifications'),
                        routeName: 'notifications.index',
                        icon: navigationIcon('M9.5 18h5m-6-3h7A1.5 1.5 0 0 0 17 13.5V10a5 5 0 1 0-10 0v3.5A1.5 1.5 0 0 0 8.5 15Z'),
                    },
                    {
                        key: 'audit-logs',
                        label: t('navigation.audit_logs'),
                        routeName: 'audit-logs.index',
                        permissions: ['audit.view', 'audit-logs.view'],
                        icon: navigationIcon('M12 4v8l4.5 2.5M20 12A8 8 0 1 1 4 12a8 8 0 0 1 16 0Z'),
                    },
                ].filter((item) => hasAnyPermission(item.permissions)),
            },
            {
                key: 'administration',
                title: t('navigation.administration'),
                items: [
                    {
                        key: 'users',
                        label: t('navigation.users'),
                        routeName: 'users.index',
                        permissions: ['users.view'],
                        icon: navigationIcon('M12 12a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Zm-6 6a6 6 0 1 1 12 0M18 7h3m-1.5-1.5v3'),
                    },
                    {
                        key: 'roles',
                        label: t('navigation.roles'),
                        routeName: 'roles.index',
                        permissions: ['roles.manage'],
                        icon: navigationIcon('M9.5 6.5 12 4l2.5 2.5 3.5.5.5 3.5L21 13l-2.5 2.5-.5 3.5-3.5.5L12 22l-2.5-2.5-3.5-.5-.5-3.5L3 13l2.5-2.5.5-3.5 3.5-.5ZM12 10v6m-3-3h6'),
                    },
                    {
                        key: 'system-settings',
                        label: t('navigation.system_settings'),
                        routeName: 'settings.index',
                        permissions: ['settings.manage'],
                        icon: navigationIcon('M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm8 3.5-2.2.8a6.8 6.8 0 0 1-.5 1.2l1 2.1-2.1 2.1-2.1-1a6.8 6.8 0 0 1-1.2.5L12 20l-1.1-2.2a6.8 6.8 0 0 1-1.2-.5l-2.1 1-2.1-2.1 1-2.1a6.8 6.8 0 0 1-.5-1.2L4 12l2.2-1.1a6.8 6.8 0 0 1 .5-1.2l-1-2.1 2.1-2.1 2.1 1a6.8 6.8 0 0 1 1.2-.5L12 4l1.1 2.2a6.8 6.8 0 0 1 1.2.5l2.1-1 2.1 2.1-1 2.1c.2.4.4.8.5 1.2L20 12Z'),
                    },
                    {
                        key: 'public-posts',
                        label: t('navigation.public_posts'),
                        routeName: 'public-posts.index',
                        permissions: ['public-posts.view', 'public-posts.manage'],
                        icon: navigationIcon('M6 6.5h12v11H6v-11Zm2.5 3h7m-7 3h5'),
                    },
                ].filter((item) => hasAnyPermission(item.permissions)),
            },
            {
                key: 'master-data',
                title: t('navigation.master_data'),
                items: [
                    {
                        key: 'departments',
                        label: t('navigation.departments'),
                        routeName: 'departments.index',
                        permissions: ['departments.view', 'departments.manage'],
                        icon: navigationIcon('M5.5 18.5h13M7 18.5v-8h3v8m4 0v-11h3v11M4.5 8.5 12 4l7.5 4.5'),
                    },
                    {
                        key: 'teams',
                        label: t('navigation.teams'),
                        routeName: 'teams.index',
                        permissions: ['teams.view', 'teams.manage'],
                        icon: navigationIcon('M7.5 10a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm9 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4ZM12 18a3.5 3.5 0 0 1 7 0M5 18a3.5 3.5 0 0 1 7 0'),
                    },
                    {
                        key: 'advisory-categories',
                        label: t('navigation.advisory_categories'),
                        routeName: 'advisory-categories.index',
                        permissions: ['advisory-categories.view', 'advisory-categories.manage'],
                        icon: navigationIcon('M6 7.5h12M6 12h12M6 16.5h7'),
                    },
                    {
                        key: 'courts',
                        label: t('navigation.courts'),
                        routeName: 'courts.index',
                        permissions: ['courts.view', 'courts.manage'],
                        icon: navigationIcon('M5.5 18.5h13M8 15.5V9m8 6.5V9M4.5 9 12 4.5 19.5 9'),
                    },
                    {
                        key: 'legal-case-types',
                        label: t('navigation.legal_case_types'),
                        routeName: 'legal-case-types.index',
                        permissions: ['legal-case-types.view', 'legal-case-types.manage'],
                        icon: navigationIcon('M8 6.5h8M8 11.5h8M8 16.5h5M5.5 6.5h.01M5.5 11.5h.01M5.5 16.5h.01'),
                    },
                ].filter((item) => hasAnyPermission(item.permissions)),
            },
        ],
        [t, unreadNotifications, user],
    );

    const sidebarFrameWidthClass = collapsed ? 'lg:w-[7.5rem]' : 'lg:w-[20.75rem]';
    const contentOffsetClass = collapsed ? 'lg:pl-[7.625rem]' : 'lg:pl-[20.875rem]';

    const shellSidebar = (
        <div className="surface-card-strong surface-shell-square flex h-full flex-col gap-6 p-4">
            <div className="flex items-center justify-between gap-3">
                <Link href={route(appMeta.default_dashboard_route)} className={cn('flex items-center gap-3', collapsed && 'justify-center')}>
                    <span className="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl bg-[color:var(--primary-soft)] text-[color:var(--primary)]">
                        {appMeta.logo_url ? (
                            <img src={appMeta.logo_url} alt={appMeta.application_short_name} className="h-full w-full object-cover" />
                        ) : (
                            <ApplicationLogo className="h-7 w-7 fill-current" />
                        )}
                    </span>
                    {!collapsed ? (
                        <div>
                            <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">{appMeta.application_short_name}</p>
                            <p className="mt-1 text-xs text-[color:var(--muted)]">{appMeta.legal_department_name || appMeta.organization_name}</p>
                        </div>
                    ) : null}
                </Link>

                <button
                    type="button"
                    onClick={() => setCollapsed((current) => !current)}
                    className="focus-ring hidden rounded-full border p-2 text-[color:var(--muted-strong)] transition hover:bg-[color:var(--surface-muted)] lg:inline-flex"
                    style={{ borderColor: 'var(--border)' }}
                >
                    <svg viewBox="0 0 24 24" className={cn('h-4 w-4 transition', collapsed && 'rotate-180')} fill="none" stroke="currentColor" strokeWidth="1.8">
                        <path d="m15 6-6 6 6 6" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                </button>
            </div>

            <div className="min-h-0 flex-1 overflow-y-auto pe-1">
                <SidebarContent sections={sections} collapsed={collapsed} />
            </div>

            <div className="mt-auto">
                <div className="surface-muted p-3">
                    {!collapsed ? (
                        <>
                            <p className="text-[13px] font-semibold uppercase text-[color:var(--muted)]">
                                {t('common.user_menu')}
                            </p>
                            <p className="mt-2 text-base font-semibold text-[color:var(--text)]">{user?.name}</p>
                            <p className="text-sm text-[color:var(--muted)]">{user?.email}</p>
                        </>
                    ) : (
                        <div className="flex justify-center text-[color:var(--muted-strong)]">
                            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8">
                                <path d="M12 12a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Zm-6 6a6 6 0 1 1 12 0" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-app lg:h-screen lg:overflow-hidden">
            <aside className={cn('hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:block lg:px-2 lg:py-3', sidebarFrameWidthClass)}>
                <div className="h-full overflow-hidden">{shellSidebar}</div>
            </aside>

            <div className={cn('min-h-screen lg:h-screen', contentOffsetClass)}>
                <div className="flex min-h-screen flex-col px-3 py-3 sm:px-4 lg:h-screen lg:px-4">
                    <header className="surface-card-strong surface-shell-square sticky top-3 z-30 mb-4 flex-none px-4 py-4 sm:px-5">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex min-w-0 items-center gap-3">
                                <button
                                    type="button"
                                    onClick={() => setMobileOpen(true)}
                                    className="focus-ring inline-flex rounded-full border p-2 text-[color:var(--muted-strong)] transition hover:bg-[color:var(--surface-muted)] lg:hidden"
                                    style={{ borderColor: 'var(--border)' }}
                                >
                                    <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8">
                                        <path d="M4 7h16M4 12h16M4 17h16" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </button>

                                <div className="min-w-0">
                                    <Breadcrumbs items={breadcrumbs} className="mb-1" />
                                    <button
                                        type="button"
                                        className="focus-ring hidden items-center gap-3 rounded-full border px-4 py-2 text-sm text-[color:var(--muted)] transition hover:bg-[color:var(--surface-muted)] md:inline-flex"
                                        style={{ borderColor: 'var(--border)' }}
                                    >
                                        <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8">
                                            <path d="m21 21-4.35-4.35M10.75 18a7.25 7.25 0 1 1 0-14.5 7.25 7.25 0 0 1 0 14.5Z" strokeLinecap="round" strokeLinejoin="round" />
                                        </svg>
                                        <span>{t('common.search_placeholder')}</span>
                                    </button>
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                {appMeta.appearance.allow_user_theme_switching ? <ThemeSwitcher /> : null}
                                <div className="hidden md:block">
                                    <LanguageSwitcher />
                                </div>
                                <Link
                                    href={route('notifications.index')}
                                    className="focus-ring relative inline-flex h-11 w-11 items-center justify-center rounded-full border text-[color:var(--muted-strong)] transition hover:bg-[color:var(--surface-muted)]"
                                    style={{ borderColor: 'var(--border)' }}
                                >
                                    <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8">
                                        <path d="M9.5 18h5m-6-3h7A1.5 1.5 0 0 0 17 13.5V10a5 5 0 1 0-10 0v3.5A1.5 1.5 0 0 0 8.5 15Z" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                    {unreadNotifications > 0 ? (
                                        <span className="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">
                                            {unreadNotifications}
                                        </span>
                                    ) : null}
                                </Link>
                                <UserMenu userName={user?.name} userEmail={user?.email} />
                            </div>
                        </div>
                    </header>

                    <div className="min-h-0 flex-1 overflow-y-auto pe-1">
                        {appMeta.security.maintenance_banner_enabled ? (
                            <div className="section-shell mb-4 border-amber-400/30 bg-amber-500/10 py-4 text-sm text-amber-700 dark:text-amber-200">
                                {t('common.maintenance_notice')}
                            </div>
                        ) : null}

                        <div className="space-y-4">
                            {header ? header : null}
                            <main className="pb-6">{children}</main>
                        </div>
                    </div>
                </div>
            </div>

            <Transition show={mobileOpen}>
                <Dialog onClose={setMobileOpen} className="relative z-40 lg:hidden">
                    <TransitionChild
                        enter="transition ease-out duration-200"
                        enterFrom="opacity-0"
                        enterTo="opacity-100"
                        leave="transition ease-in duration-150"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                    >
                        <div className="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" />
                    </TransitionChild>
                    <div className="fixed inset-0 flex p-3">
                        <TransitionChild
                            enter="transition ease-out duration-200"
                            enterFrom="-translate-x-6 opacity-0"
                            enterTo="translate-x-0 opacity-100"
                            leave="transition ease-in duration-150"
                            leaveFrom="translate-x-0 opacity-100"
                            leaveTo="-translate-x-6 opacity-0"
                        >
                            <DialogPanel className="h-full w-full max-w-[20rem] overflow-hidden">{shellSidebar}</DialogPanel>
                        </TransitionChild>
                    </div>
                </Dialog>
            </Transition>
        </div>
    );
}
