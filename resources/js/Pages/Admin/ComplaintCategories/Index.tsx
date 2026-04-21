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

type ComplaintCategoryRow = {
    id: string;
    code: string;
    name_en: string;
    name_am: string;
    description?: string | null;
    is_active: boolean;
    can: {
        update: boolean;
        delete: boolean;
    };
};

export default function ComplaintCategoriesIndex({ filters, categories, can }: any) {
    const { t, locale } = useI18n();
    const [isFiltering, setIsFiltering] = useState(false);
    const [pendingDelete, setPendingDelete] = useState<ComplaintCategoryRow | null>(null);
    const [processingDelete, setProcessingDelete] = useState(false);
    const form = useForm({
        search: filters.search ?? '',
        is_active: filters.is_active ?? '',
    });
    const categoryRows: ComplaintCategoryRow[] = Array.isArray(categories.data) ? categories.data : [];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.complaint_categories') },
            ]}
        >
            <Head title={t('complaint_categories.index_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('complaint_categories.eyebrow')}
                    title={t('complaint_categories.index_title')}
                    description={t('complaint_categories.index_description')}
                    action={
                        can.create ? (
                            <Link href={route('complaint-categories.create')} className="btn-base btn-primary focus-ring">
                                {t('complaint_categories.new_record')}
                            </Link>
                        ) : undefined
                    }
                />

                <FiltersToolbar
                    title={t('complaint_categories.filters')}
                    actions={
                        <>
                            <button type="button" className="btn-base btn-secondary focus-ring" onClick={() => router.get(route('complaint-categories.index'))}>
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                className="btn-base btn-primary focus-ring"
                                disabled={isFiltering}
                                onClick={() => {
                                    setIsFiltering(true);
                                    router.get(route('complaint-categories.index'), form.data, {
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
                    <input value={form.data.search} onChange={(event) => form.setData('search', event.target.value)} className="input-ui" placeholder={t('complaint_categories.search_placeholder')} />
                    <select value={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.value)} className="select-ui">
                        <option value="">{t('common.status')}</option>
                        <option value="1">{t('common.active')}</option>
                        <option value="0">{t('common.inactive')}</option>
                    </select>
                </FiltersToolbar>

                {categoryRows.length === 0 ? (
                    <EmptyState title={t('complaint_categories.empty_title')} description={t('complaint_categories.empty_description')} />
                ) : (
                    <>
                        <DataTable<ComplaintCategoryRow>
                            rows={categoryRows}
                            rowKey={(row) => row.id}
                            emptyTitle={t('complaint_categories.empty_title')}
                            emptyDescription={t('complaint_categories.empty_description')}
                            actions={(row) => [
                                { label: t('common.view'), href: route('complaint-categories.show', row.id) },
                                ...(row.can.update ? [{ label: t('common.edit'), href: route('complaint-categories.edit', row.id) }] : []),
                                ...(row.can.delete ? [{ label: t('common.delete'), onClick: () => setPendingDelete(row) }] : []),
                            ]}
                            columns={[
                                { key: 'code', header: t('common.code'), cell: (row) => row.code },
                                {
                                    key: 'name',
                                    header: t('common.name'),
                                    cell: (row) => (
                                        <div>
                                            <p className="font-semibold text-[color:var(--text)]">{locale === 'am' ? row.name_am : row.name_en}</p>
                                            <p className="mt-1 text-sm text-[color:var(--muted)]">{row.description ?? t('common.not_available')}</p>
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
                            links={Array.isArray(categories.links) ? categories.links : []}
                            meta={{
                                current_page: categories.current_page,
                                from: categories.from,
                                last_page: categories.last_page,
                                to: categories.to,
                                total: categories.total,
                            }}
                        />
                    </>
                )}
            </PageContainer>

            <ConfirmationDialog
                open={pendingDelete !== null}
                title={t('complaint_categories.delete_title')}
                description={t('complaint_categories.delete_confirm')}
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
                    router.delete(route('complaint-categories.destroy', pendingDelete.id), {
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
