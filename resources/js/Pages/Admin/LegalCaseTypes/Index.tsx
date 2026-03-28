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

type LegalCaseTypeRow = {
    id: string;
    code: string;
    name_en: string;
    name_am: string;
    description?: string | null;
    is_active: boolean;
};

export default function LegalCaseTypesIndex({ filters, caseTypes, can }: any) {
    const { t, locale } = useI18n();
    const [isFiltering, setIsFiltering] = useState(false);
    const form = useForm({
        search: filters.search ?? '',
        is_active: filters.is_active ?? '',
    });

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('navigation.dashboard'), href: route('dashboard') }, { label: t('navigation.legal_case_types') }]}>
            <Head title={t('legal_case_types.index_title')} />
            <PageContainer>
                <SectionHeader
                    eyebrow={t('legal_case_types.eyebrow')}
                    title={t('legal_case_types.index_title')}
                    description={t('legal_case_types.index_description')}
                    action={
                        can.create ? (
                            <Link href={route('legal-case-types.create')} className="btn-base btn-primary focus-ring">
                                {t('legal_case_types.new_record')}
                            </Link>
                        ) : undefined
                    }
                />
                <FiltersToolbar
                    title={t('legal_case_types.filters')}
                    actions={
                        <>
                            <button type="button" className="btn-base btn-secondary focus-ring" onClick={() => router.get(route('legal-case-types.index'))}>
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                className="btn-base btn-primary focus-ring"
                                disabled={isFiltering}
                                onClick={() => {
                                    setIsFiltering(true);
                                    router.get(route('legal-case-types.index'), form.data, {
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
                    <input value={form.data.search} onChange={(event) => form.setData('search', event.target.value)} className="input-ui" placeholder={t('legal_case_types.search_placeholder')} />
                    <select value={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.value)} className="select-ui">
                        <option value="">{t('common.status')}</option>
                        <option value="1">{t('common.active')}</option>
                        <option value="0">{t('common.inactive')}</option>
                    </select>
                </FiltersToolbar>
                {caseTypes.data.length === 0 ? (
                    <EmptyState title={t('legal_case_types.empty_title')} description={t('legal_case_types.empty_description')} />
                ) : (
                    <>
                        <DataTable<LegalCaseTypeRow>
                            rows={caseTypes.data}
                            rowKey={(row) => row.id}
                            emptyTitle={t('legal_case_types.empty_title')}
                            emptyDescription={t('legal_case_types.empty_description')}
                            actions={(row) => [
                                { label: t('common.view'), href: route('legal-case-types.show', row.id) },
                                ...(can.update ? [{ label: t('common.edit'), href: route('legal-case-types.edit', row.id) }] : []),
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
                                { key: 'status', header: t('common.status'), cell: (row) => <StatusBadge value={row.is_active ? 'active' : 'inactive'} /> },
                            ]}
                        />
                        <Pagination links={caseTypes.links} />
                    </>
                )}
            </PageContainer>
        </AuthenticatedLayout>
    );
}
