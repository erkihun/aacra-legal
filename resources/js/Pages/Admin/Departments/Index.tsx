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

type DepartmentRow = {
    id: string;
    code: string;
    name_en: string;
    name_am: string;
    is_active: boolean;
};

export default function DepartmentsIndex({ filters, departments, can }: any) {
    const { t, locale } = useI18n();
    const [isFiltering, setIsFiltering] = useState(false);
    const form = useForm({
        search: filters.search ?? '',
        is_active: filters.is_active ?? '',
    });

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.departments') },
            ]}
        >
            <Head title={t('departments.index_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('departments.eyebrow')}
                    title={t('departments.index_title')}
                    description={t('departments.index_description')}
                    action={
                        can.create ? (
                            <Link href={route('departments.create')} className="btn-base btn-primary focus-ring">
                                {t('departments.new_department')}
                            </Link>
                        ) : undefined
                    }
                />

                <FiltersToolbar
                    title={t('departments.filters')}
                    actions={
                        <>
                            <button type="button" className="btn-base btn-secondary focus-ring" onClick={() => router.get(route('departments.index'))}>
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                className="btn-base btn-primary focus-ring"
                                disabled={isFiltering}
                                onClick={() => {
                                    setIsFiltering(true);
                                    router.get(route('departments.index'), form.data, {
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
                        placeholder={t('departments.search_placeholder')}
                    />
                    <select value={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.value)} className="select-ui">
                        <option value="">{t('common.status')}</option>
                        <option value="1">{t('common.active')}</option>
                        <option value="0">{t('common.inactive')}</option>
                    </select>
                </FiltersToolbar>

                {departments.data.length === 0 ? (
                    <EmptyState title={t('departments.empty_title')} description={t('departments.empty_description')} />
                ) : (
                    <>
                        <DataTable<DepartmentRow>
                            rows={departments.data}
                            rowKey={(row) => row.id}
                            emptyTitle={t('departments.empty_title')}
                            emptyDescription={t('departments.empty_description')}
                            actions={(row) => [
                                { label: t('common.view'), href: route('departments.show', row.id) },
                                ...(can.update ? [{ label: t('common.edit'), href: route('departments.edit', row.id) }] : []),
                            ]}
                            columns={[
                                { key: 'code', header: t('common.code'), cell: (row) => row.code },
                                {
                                    key: 'name',
                                    header: t('common.name'),
                                    cell: (row) => (
                                        <div>
                                            <p className="font-semibold text-[color:var(--text)]">{locale === 'am' ? row.name_am : row.name_en}</p>
                                            <p className="mt-1 text-sm text-[color:var(--muted)]">{locale === 'am' ? row.name_en : row.name_am}</p>
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
                        <Pagination links={departments.links} />
                    </>
                )}
            </PageContainer>
        </AuthenticatedLayout>
    );
}
