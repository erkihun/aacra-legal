import DataTable from '@/Components/Ui/DataTable';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type CasesIndexProps = {
    filters: {
        search?: string;
        status?: string;
    };
    cases: {
        data: Array<{
            id: string;
            case_number: string;
            external_court_file_number?: string | null;
            plaintiff: string;
            defendant: string;
            status: string;
            priority: string;
        }>;
    };
    can: {
        create: boolean;
    };
    statusOptions: Array<{ label: string; value: string }>;
};

export default function CasesIndex({
    filters,
    cases,
    can,
    statusOptions,
}: CasesIndexProps) {
    const { t } = useI18n();
    const [isFiltering, setIsFiltering] = useState(false);
    const { data, setData } = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
    });

    const applyFilters = () => {
        setIsFiltering(true);

        router.get(route('cases.index'), data, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.legal_cases') },
            ]}
        >
            <Head title={t('navigation.legal_cases')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('cases.eyebrow')}
                    title={t('cases.index_title')}
                    description={t('cases.index_description')}
                    action={can.create ? (
                        <Link href={route('cases.create')} className="btn-base btn-primary focus-ring">
                            {t('cases.register_case')}
                        </Link>
                    ) : undefined}
                />

                <FiltersToolbar
                    title={t('common.apply_filters')}
                    actions={
                        <>
                            <button
                                type="button"
                                onClick={() => {
                                    setData({ search: '', status: '' });
                                    router.get(route('cases.index'));
                                }}
                                className="btn-base btn-secondary focus-ring"
                            >
                                {t('common.reset')}
                            </button>
                            <button type="button" onClick={applyFilters} className="btn-base btn-primary focus-ring" disabled={isFiltering}>
                                {isFiltering ? `${t('common.apply_filters')}...` : t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <input
                        value={data.search}
                        onChange={(event) => setData('search', event.target.value)}
                        placeholder={t('cases.search_placeholder')}
                        className="input-ui"
                    />
                    <select
                        value={data.status}
                        onChange={(event) => setData('status', event.target.value)}
                        className="select-ui"
                    >
                        <option value="">{t('common.all_statuses')}</option>
                        {statusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <div className="surface-muted flex items-center px-4 text-sm text-[color:var(--muted)]">
                        {cases.data.length} {t('common.records')}
                    </div>
                </FiltersToolbar>

                <DataTable
                    rows={cases.data}
                    rowKey={(row) => row.id}
                    emptyTitle={t('cases.empty_title')}
                    emptyDescription={t('cases.empty_description')}
                    actions={(row) => [{ label: t('notifications.open'), href: route('cases.show', row.id) }]}
                    columns={[
                        {
                            key: 'case',
                            header: t('reports.case_number'),
                            cell: (row) => (
                                <div>
                                    <p className="font-semibold text-[color:var(--text)]">{row.case_number}</p>
                                    <p className="mt-1 text-xs uppercase text-[color:var(--muted)]">
                                        {t('cases.court_file_number')}: {row.external_court_file_number ?? t('common.not_provided')}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            key: 'parties',
                            header: t('reports.subject'),
                            cell: (row) => (
                                <div>
                                    <p className="font-medium text-[color:var(--text)]">{row.plaintiff}</p>
                                    <p className="mt-1 text-sm text-[color:var(--muted)]">
                                        {t('common.versus')} {row.defendant}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            key: 'priority',
                            header: t('reports.priority'),
                            cell: (row) => <StatusBadge value={row.priority} />,
                        },
                        {
                            key: 'status',
                            header: t('reports.status'),
                            cell: (row) => <StatusBadge value={row.status} />,
                        },
                    ]}
                />
            </PageContainer>
        </AuthenticatedLayout>
    );
}
