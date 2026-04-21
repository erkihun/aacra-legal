import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import DataTable from '@/Components/Ui/DataTable';
import EmptyState from '@/Components/Ui/EmptyState';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import PageContainer from '@/Components/Ui/PageContainer';
import Pagination from '@/Components/Ui/Pagination';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type BranchRow = {
    id: string;
    row_number: number;
    code: string;
    name_en: string;
    name_am?: string | null;
    region?: string | null;
    city?: string | null;
    phone?: string | null;
    email?: string | null;
    is_active: boolean;
    can: {
        update: boolean;
        delete: boolean;
    };
};

export default function BranchesIndex({ filters, branches, can }: any) {
    const { t, locale } = useI18n();
    const [isFiltering, setIsFiltering] = useState(false);
    const [pendingDelete, setPendingDelete] = useState<BranchRow | null>(null);
    const [processingDelete, setProcessingDelete] = useState(false);
    const form = useForm({
        search: filters.search ?? '',
        location: filters.location ?? '',
        is_active: filters.is_active ?? '',
    });
    const branchRows: BranchRow[] = Array.isArray(branches.data)
        ? branches.data.map((row: Omit<BranchRow, 'row_number'>, index: number) => ({
              ...row,
              row_number: ((branches.current_page ?? 1) - 1) * (branches.per_page ?? branches.data.length ?? 0) + index + 1,
          }))
        : [];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.branches') },
            ]}
        >
            <Head title={t('branches.index_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('branches.eyebrow')}
                    title={t('branches.index_title')}
                    description={t('branches.index_description')}
                    action={
                        can.create ? (
                            <Link href={route('branches.create')} className="btn-base btn-primary focus-ring">
                                {t('branches.new_branch')}
                            </Link>
                        ) : undefined
                    }
                />

                <FiltersToolbar
                    title={t('branches.filters')}
                    actions={
                        <>
                            <button type="button" className="btn-base btn-secondary focus-ring" onClick={() => router.get(route('branches.index'))}>
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                className="btn-base btn-primary focus-ring"
                                disabled={isFiltering}
                                onClick={() => {
                                    setIsFiltering(true);
                                    router.get(route('branches.index'), form.data, {
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
                        placeholder={t('branches.search_placeholder')}
                    />
                    <input
                        value={form.data.location}
                        onChange={(event) => form.setData('location', event.target.value)}
                        className="input-ui"
                        placeholder={t('branches.location_placeholder')}
                    />
                    <select value={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.value)} className="select-ui">
                        <option value="">{t('common.status')}</option>
                        <option value="1">{t('common.active')}</option>
                        <option value="0">{t('common.inactive')}</option>
                    </select>
                </FiltersToolbar>

                {branchRows.length === 0 ? (
                    <EmptyState title={t('branches.empty_title')} description={t('branches.empty_description')} />
                ) : (
                    <>
                        <DataTable<BranchRow>
                            rows={branchRows}
                            rowKey={(row) => row.id}
                            emptyTitle={t('branches.empty_title')}
                            emptyDescription={t('branches.empty_description')}
                            actions={(row) => [
                                { label: t('common.view'), href: route('branches.show', row.id) },
                                ...(row.can.update ? [{ label: t('common.edit'), href: route('branches.edit', row.id) }] : []),
                                ...(row.can.delete ? [{ label: t('common.delete'), onClick: () => setPendingDelete(row) }] : []),
                            ]}
                            columns={[
                                {
                                    key: 'number',
                                    header: '#',
                                    cell: (row) => row.row_number,
                                    className: 'w-16',
                                },
                                {
                                    key: 'name',
                                    header: t('branches.branch_name'),
                                    cell: (row) => (
                                        <div>
                                            <p className="font-semibold text-[color:var(--text)]">{locale === 'am' ? row.name_am || row.name_en : row.name_en || row.name_am}</p>
                                            <p className="mt-1 text-sm text-[color:var(--muted)]">{row.code}</p>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'code',
                                    header: t('common.code'),
                                    cell: (row) => row.code,
                                },
                                {
                                    key: 'location',
                                    header: t('branches.location'),
                                    cell: (row) => formatLocation(row, t),
                                },
                                {
                                    key: 'phone',
                                    header: t('branches.phone'),
                                    cell: (row) => row.phone ?? t('common.not_available'),
                                },
                                {
                                    key: 'email',
                                    header: t('branches.email'),
                                    cell: (row) => row.email ?? t('common.not_available'),
                                },
                                {
                                    key: 'status',
                                    header: t('common.status'),
                                    cell: (row) => <StatusBadge value={row.is_active ? 'active' : 'inactive'} />,
                                },
                            ]}
                        />
                        <Pagination
                            links={Array.isArray(branches.links) ? branches.links : []}
                            meta={{
                                current_page: branches.current_page,
                                from: branches.from,
                                last_page: branches.last_page,
                                to: branches.to,
                                total: branches.total,
                            }}
                        />
                    </>
                )}
            </PageContainer>

            <ConfirmationDialog
                open={pendingDelete !== null}
                title={t('branches.delete_title')}
                description={t('branches.delete_confirm')}
                confirmLabel={t('common.delete')}
                processing={processingDelete}
                onCancel={() => {
                    if (!processingDelete) {
                        setPendingDelete(null);
                    }
                }}
                onConfirm={() => {
                    if (!pendingDelete) {
                        return;
                    }

                    setProcessingDelete(true);
                    router.delete(route('branches.destroy', pendingDelete.id), {
                        preserveScroll: true,
                        onFinish: () => {
                            setProcessingDelete(false);
                            setPendingDelete(null);
                        },
                    });
                }}
            />
        </AuthenticatedLayout>
    );
}

function formatLocation(row: BranchRow, t: (key: string) => string) {
    const parts = [row.region, row.city].filter(Boolean);

    if (parts.length === 0) {
        return t('common.not_available');
    }

    return parts.join(', ');
}
