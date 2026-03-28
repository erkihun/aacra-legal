import DataTable from '@/Components/Ui/DataTable';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { translateRoleName } from '@/lib/access';
import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';

type RoleRow = {
    id: string;
    name: string;
    users_count: number;
    permissions_count: number;
    is_protected: boolean;
};

export default function RolesIndex({ roles }: any) {
    const { t } = useI18n();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.roles') },
            ]}
        >
            <Head title={t('roles.index_title')} />

            <PageContainer>
                <SectionHeader eyebrow={t('roles.eyebrow')} title={t('roles.index_title')} description={t('roles.index_description')} />

                <DataTable<RoleRow>
                    rows={roles}
                    rowKey={(row) => row.id}
                    emptyTitle={t('roles.index_title')}
                    emptyDescription={t('roles.empty_description')}
                    actions={(row) => [{ label: t('common.edit'), href: route('roles.edit', row.id) }]}
                    columns={[
                        {
                            key: 'name',
                            header: t('roles.role'),
                            cell: (row) => (
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-semibold text-[color:var(--text)]">{translateRoleName(row.name, t)}</span>
                                    {row.is_protected ? (
                                        <span className="rounded-full bg-[color:var(--primary-soft)] px-3 py-1 text-[11px] font-semibold uppercase text-[color:var(--primary)]">
                                            {t('roles.protected')}
                                        </span>
                                    ) : null}
                                </div>
                            ),
                        },
                        {
                            key: 'users',
                            header: t('roles.assigned_users'),
                            cell: (row) => row.users_count,
                        },
                        {
                            key: 'permissions',
                            header: t('roles.permissions'),
                            cell: (row) => row.permissions_count,
                        },
                    ]}
                />
            </PageContainer>
        </AuthenticatedLayout>
    );
}
