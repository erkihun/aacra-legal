import DataTable from '@/Components/Ui/DataTable';
import EmptyState from '@/Components/Ui/EmptyState';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import PageContainer from '@/Components/Ui/PageContainer';
import Pagination from '@/Components/Ui/Pagination';
import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { translateRoleName } from '@/lib/access';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type UserRow = {
    id: string;
    name: string;
    email: string;
    employee_number?: string | null;
    avatar_url?: string | null;
    roles: string[];
    department?: { id: string; name_en: string; name_am: string } | null;
    team?: { id: string; name_en: string; name_am: string } | null;
    is_active: boolean;
    can: {
        update: boolean;
        ban: boolean;
        delete: boolean;
    };
};

type UserSort = 'name' | 'email' | 'created_at' | 'last_login_at';
type UserSortDirection = 'asc' | 'desc';

type UsersPageProps = {
    filters: {
        search?: string;
        department_id?: string;
        team_id?: string;
        role?: string;
        is_active?: string;
        sort?: UserSort;
        direction?: UserSortDirection;
    };
    users: {
        data: UserRow[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
    filterOptions: {
        departments: Array<{ id: string; name_en: string; name_am: string }>;
        teams: Array<{ id: string; name_en: string; name_am: string }>;
        roles: Array<{ id: string; name: string }>;
    };
    can: {
        create: boolean;
        update: boolean;
        ban: boolean;
        delete: boolean;
    };
};

type PendingAction =
    | { type: 'ban'; user: UserRow }
    | { type: 'activate'; user: UserRow }
    | { type: 'delete'; user: UserRow };

export default function UsersIndex({ filters, users, filterOptions, can }: UsersPageProps) {
    const { t, locale } = useI18n();
    const [isFiltering, setIsFiltering] = useState(false);
    const [pendingAction, setPendingAction] = useState<PendingAction | null>(null);
    const [isActionProcessing, setIsActionProcessing] = useState(false);
    const form = useForm({
        search: filters.search ?? '',
        department_id: filters.department_id ?? '',
        team_id: filters.team_id ?? '',
        role: filters.role ?? '',
        is_active: filters.is_active ?? '',
        sort: filters.sort ?? 'name',
        direction: filters.direction ?? 'asc',
    });
    const redirectTo = typeof window === 'undefined' ? route('users.index') : `${window.location.pathname}${window.location.search}`;

    const handleConfirmAction = () => {
        if (pendingAction === null) {
            return;
        }

        setIsActionProcessing(true);

        if (pendingAction.type === 'delete') {
            router.delete(route('users.destroy', pendingAction.user.id), {
                data: { redirect_to: redirectTo },
                preserveScroll: true,
                onFinish: () => {
                    setIsActionProcessing(false);
                    setPendingAction(null);
                },
            });

            return;
        }

        router.patch(
            route(pendingAction.type === 'ban' ? 'users.ban' : 'users.activate', pendingAction.user.id),
            { redirect_to: redirectTo },
            {
                preserveScroll: true,
                onFinish: () => {
                    setIsActionProcessing(false);
                    setPendingAction(null);
                },
            },
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.users') },
            ]}
        >
            <Head title={t('users.index_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('users.eyebrow')}
                    title={t('users.index_title')}
                    description={t('users.index_description')}
                    action={
                        can.create ? (
                            <Link href={route('users.create')} className="btn-base btn-primary focus-ring">
                                {t('users.new_user')}
                            </Link>
                        ) : undefined
                    }
                />

                <FiltersToolbar
                    title={t('users.filters')}
                    actions={
                        <>
                            <button type="button" className="btn-base btn-secondary focus-ring" onClick={() => router.get(route('users.index'))}>
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                className="btn-base btn-primary focus-ring"
                                disabled={isFiltering}
                                onClick={() => {
                                    setIsFiltering(true);
                                    router.get(route('users.index'), form.data, {
                                        preserveState: true,
                                        replace: true,
                                        onFinish: () => setIsFiltering(false),
                                    });
                                }}
                            >
                                {t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <input
                        value={form.data.search}
                        onChange={(event) => form.setData('search', event.target.value)}
                        className="input-ui"
                        placeholder={t('users.search_placeholder')}
                    />
                    <select value={form.data.department_id} onChange={(event) => form.setData('department_id', event.target.value)} className="select-ui">
                        <option value="">{t('navigation.departments')}</option>
                        {filterOptions.departments.map((department: any) => (
                            <option key={department.id} value={department.id}>
                                {(locale === 'am' ? department.name_am : department.name_en) ?? department.name_en}
                            </option>
                        ))}
                    </select>
                    <select value={form.data.team_id} onChange={(event) => form.setData('team_id', event.target.value)} className="select-ui">
                        <option value="">{t('navigation.teams')}</option>
                        {filterOptions.teams.map((team: any) => (
                            <option key={team.id} value={team.id}>
                                {(locale === 'am' ? team.name_am : team.name_en) ?? team.name_en}
                            </option>
                        ))}
                    </select>
                    <select value={form.data.role} onChange={(event) => form.setData('role', event.target.value)} className="select-ui">
                        <option value="">{t('navigation.roles')}</option>
                        {filterOptions.roles.map((role: any) => (
                            <option key={role.id} value={role.name}>
                                {translateRoleName(role.name, t)}
                            </option>
                        ))}
                    </select>
                    <select value={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.value)} className="select-ui">
                        <option value="">{t('common.status')}</option>
                        <option value="1">{t('common.active')}</option>
                        <option value="0">{t('common.inactive')}</option>
                    </select>
                    <select
                        value={form.data.sort}
                        onChange={(event) => form.setData('sort', event.target.value as UserSort)}
                        className="select-ui"
                    >
                        <option value="name">{t('auth.name')}</option>
                        <option value="email">{t('profile.email')}</option>
                        <option value="created_at">{t('users.created_at')}</option>
                        <option value="last_login_at">{t('users.last_login')}</option>
                    </select>
                    <select
                        value={form.data.direction}
                        onChange={(event) => form.setData('direction', event.target.value as UserSortDirection)}
                        className="select-ui"
                    >
                        <option value="asc">{t('common.ascending')}</option>
                        <option value="desc">{t('common.descending')}</option>
                    </select>
                </FiltersToolbar>

                {users.data.length === 0 ? (
                    <EmptyState title={t('users.empty_title')} description={t('users.empty_description')} />
                ) : (
                    <>
                        <div className="mb-4 flex items-center justify-between gap-3 text-sm text-[color:var(--muted)]">
                            <p>
                                {users.from}-{users.to} / {users.total} {t('common.records')}
                            </p>
                            <p>
                                {users.current_page} / {users.last_page}
                            </p>
                        </div>
                        <DataTable<UserRow>
                            rows={users.data}
                            rowKey={(row) => row.id}
                            emptyTitle={t('users.empty_title')}
                            emptyDescription={t('users.empty_description')}
                            actions={(row) => {
                                const actions: Array<{ label: string; href?: string; onClick?: () => void }> = [
                                    { label: t('common.view'), href: route('users.show', row.id) },
                                ];

                                if (row.can.update) {
                                    actions.push({ label: t('common.edit'), href: route('users.edit', row.id) });
                                }

                                if (row.can.ban) {
                                    actions.push({
                                        label: row.is_active ? t('common.ban') : t('common.activate'),
                                        onClick: () =>
                                            setPendingAction({
                                                type: row.is_active ? 'ban' : 'activate',
                                                user: row,
                                            }),
                                    });
                                }

                                if (row.can.delete) {
                                    actions.push({
                                        label: t('common.delete'),
                                        onClick: () => setPendingAction({ type: 'delete', user: row }),
                                    });
                                }

                                return actions;
                            }}
                            columns={[
                                {
                                    key: 'name',
                                    header: t('auth.name'),
                                    cell: (row) => (
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-[color:var(--primary-soft)] text-sm font-semibold text-[color:var(--primary)]">
                                                {row.avatar_url ? (
                                                    <img src={row.avatar_url} alt={row.name} className="h-full w-full object-cover" />
                                                ) : (
                                                    row.name.slice(0, 2).toUpperCase()
                                                )}
                                            </div>
                                            <div>
                                                <p className="font-semibold text-[color:var(--text)]">{row.name}</p>
                                                <p className="mt-1 text-sm text-[color:var(--muted)]">{row.email}</p>
                                            </div>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'employee',
                                    header: t('users.employee_number'),
                                    cell: (row) => row.employee_number ?? t('common.not_available'),
                                },
                                {
                                    key: 'role',
                                    header: t('navigation.roles'),
                                    cell: (row) => (row.roles[0] ? translateRoleName(row.roles[0], t) : t('common.not_set')),
                                },
                                {
                                    key: 'assignment',
                                    header: t('users.assignment'),
                                    cell: (row) => (
                                        <div>
                                            <p className="text-[color:var(--text)]">
                                                {(locale === 'am' ? row.department?.name_am : row.department?.name_en) ?? row.department?.name_en ?? t('common.not_set')}
                                            </p>
                                            <p className="mt-1 text-sm text-[color:var(--muted)]">
                                                {(locale === 'am' ? row.team?.name_am : row.team?.name_en) ?? row.team?.name_en ?? t('common.unassigned')}
                                            </p>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'status',
                                    header: t('common.status'),
                                    cell: (row) => <StatusBadge value={row.is_active ? 'active' : 'inactive'} />,
                                },
                            ]}
                        />
                        <Pagination
                            links={users.links}
                            meta={{
                                current_page: users.current_page,
                                from: users.from,
                                last_page: users.last_page,
                                to: users.to,
                                total: users.total,
                            }}
                        />
                    </>
                )}
            </PageContainer>

            <ConfirmationDialog
                open={pendingAction !== null}
                title={
                    pendingAction?.type === 'delete'
                        ? t('users.delete_title')
                        : pendingAction?.type === 'activate'
                          ? t('users.activate_title')
                          : t('users.ban_title')
                }
                description={
                    pendingAction?.type === 'delete'
                        ? t('users.delete_confirm')
                        : pendingAction?.type === 'activate'
                          ? t('users.activate_confirm')
                          : t('users.ban_confirm')
                }
                confirmLabel={
                    pendingAction?.type === 'delete'
                        ? t('common.delete')
                        : pendingAction?.type === 'activate'
                          ? t('common.activate')
                          : t('common.ban')
                }
                processing={isActionProcessing}
                onCancel={() => {
                    if (! isActionProcessing) {
                        setPendingAction(null);
                    }
                }}
                onConfirm={handleConfirmAction}
            />
        </AuthenticatedLayout>
    );
}
