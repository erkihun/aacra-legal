import DataTable from '@/Components/Ui/DataTable';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useI18n } from '@/lib/i18n';

type PermissionRow = {
    id: string;
    name: string;
    group: string;
    group_label: string;
    label: string;
    description_en: string;
    description_am: string;
};

export default function PermissionsIndex({ permissions }: { permissions: PermissionRow[] }) {
    const { t } = useI18n();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.roles'), href: route('roles.index') },
                { label: t('permissions.index_title') },
            ]}
        >
            <Head title={t('permissions.index_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('roles.eyebrow')}
                    title={t('permissions.index_title')}
                    description={t('permissions.index_description')}
                    action={
                        <Link href={route('roles.index')} className="btn-base btn-secondary focus-ring">
                            {t('permissions.back_to_roles')}
                        </Link>
                    }
                />

                <DataTable<PermissionRow>
                    rows={permissions}
                    rowKey={(row) => row.id}
                    emptyTitle={t('permissions.index_title')}
                    emptyDescription={t('permissions.empty_description')}
                    actions={(row) => [
                        { label: t('common.view'), href: route('permissions.edit', row.id) },
                        { label: t('common.edit'), href: route('permissions.edit', row.id) },
                    ]}
                    columns={[
                        {
                            key: 'name',
                            header: t('permissions.permission_key'),
                            cell: (row) => (
                                <div className="space-y-1">
                                    <p className="font-semibold text-[color:var(--text)]">{row.name}</p>
                                    <p className="text-xs text-[color:var(--primary)]">{row.group_label}</p>
                                </div>
                            ),
                        },
                        {
                            key: 'label',
                            header: t('permissions.permission_label'),
                            cell: (row) => row.label,
                        },
                        {
                            key: 'description_en',
                            header: t('permissions.description_en'),
                            cell: (row) => (
                                <p className="max-w-xl text-sm leading-6 text-[color:var(--muted-strong)]">
                                    {row.description_en}
                                </p>
                            ),
                        },
                        {
                            key: 'description_am',
                            header: t('permissions.description_am'),
                            cell: (row) => (
                                <p className="max-w-xl text-sm leading-6 text-[color:var(--muted-strong)]">
                                    {row.description_am}
                                </p>
                            ),
                        },
                    ]}
                />
            </PageContainer>
        </AuthenticatedLayout>
    );
}
