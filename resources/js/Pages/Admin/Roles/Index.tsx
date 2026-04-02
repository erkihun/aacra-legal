import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import DataTable from '@/Components/Ui/DataTable';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { translateRoleName } from '@/lib/access';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

type RoleRow = {
    id: string;
    name: string;
    users_count: number;
    permissions_count: number;
    created_at?: string | null;
    is_protected: boolean;
    permissions_locked: boolean;
};

export default function RolesIndex({ roles }: { roles: RoleRow[] }) {
    const { t } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [roleToDelete, setRoleToDelete] = useState<RoleRow | null>(null);

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.roles') },
            ]}
        >
            <Head title={t('roles.index_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('roles.eyebrow')}
                    title={t('roles.index_title')}
                    description={t('roles.index_description')}
                    action={
                        <Link href={route('roles.create')} className="btn-base btn-primary focus-ring">
                            {t('roles.create_action')}
                        </Link>
                    }
                />

                <DataTable<RoleRow>
                    rows={roles}
                    rowKey={(row) => row.id}
                    emptyTitle={t('roles.index_title')}
                    emptyDescription={t('roles.empty_description')}
                    actions={(row) => [
                        { label: t('common.view'), href: route('roles.edit', row.id) },
                        { label: t('common.edit'), href: route('roles.edit', row.id) },
                        ...(row.is_protected
                            ? []
                            : [{ label: t('common.delete'), onClick: () => setRoleToDelete(row) }]),
                    ]}
                    columns={[
                        {
                            key: 'name',
                            header: t('roles.role'),
                            cell: (row) => (
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-semibold text-[color:var(--text)]">
                                        {translateRoleName(row.name, t)}
                                    </span>
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
                        {
                            key: 'created_at',
                            header: t('roles.created_at'),
                            cell: (row) => formatDateTime(row.created_at, t('common.not_set')),
                        },
                    ]}
                />
            </PageContainer>

            <ConfirmationDialog
                open={roleToDelete !== null}
                title={t('roles.delete_title')}
                description={t('roles.delete_confirm')}
                confirmLabel={t('common.delete')}
                onCancel={() => setRoleToDelete(null)}
                onConfirm={() => {
                    if (!roleToDelete) {
                        return;
                    }

                    router.delete(route('roles.destroy', roleToDelete.id), {
                        onSuccess: () => {
                            setRoleToDelete(null);
                        },
                    });
                }}
            />
        </AuthenticatedLayout>
    );
}
