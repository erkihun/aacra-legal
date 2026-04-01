import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import DataTable from '@/Components/Ui/DataTable';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { finishSuccessfulSubmission } from '@/lib/form-submission';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

type CaseRow = {
    id: string;
    case_number: string;
    external_court_file_number?: string | null;
    main_case_type?: 'civil-law' | 'labour-dispute' | 'crime' | null;
    police_station?: string | null;
    stolen_property_type?: string | null;
    plaintiff?: string | null;
    defendant?: string | null;
    status: string;
    assigned_team_leader?: {
        name: string;
    } | null;
    assigned_legal_expert?: {
        name: string;
    } | null;
    can_update?: boolean;
    can_delete?: boolean;
};

type CasesIndexProps = {
    filters: {
        search?: string;
        status?: string;
        main_case_type?: string;
    };
    cases: {
        data: CaseRow[];
    };
    can: {
        create: boolean;
    };
    statusOptions: Array<{ label: string; value: string }>;
    mainCaseTypeOptions: Array<{ label: string; value: 'civil-law' | 'labour-dispute' | 'crime' }>;
};

export default function CasesIndex({
    filters,
    cases,
    can,
    statusOptions,
    mainCaseTypeOptions,
}: CasesIndexProps) {
    const { t } = useI18n();
    const [isFiltering, setIsFiltering] = useState(false);
    const [caseToDelete, setCaseToDelete] = useState<CaseRow | null>(null);
    const { data, setData } = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
        main_case_type: filters.main_case_type ?? '',
    });
    const deleteForm = useForm({});

    const applyFilters = () => {
        setIsFiltering(true);

        router.get(route('cases.index'), data, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const visibleRows = useMemo(
        () => cases.data.map((row, index) => ({ ...row, row_number: index + 1 })),
        [cases.data],
    );

    const isCrimeView = data.main_case_type === 'crime';
    const isCivilLikeView = data.main_case_type === 'civil-law' || data.main_case_type === 'labour-dispute';

    const columns = useMemo(() => {
        if (isCrimeView) {
            return [
                {
                    key: 'number',
                    header: '#',
                    className: 'w-16',
                    cell: (row: CaseRow & { row_number: number }) => row.row_number,
                },
                {
                    key: 'case_number',
                    header: t('cases.case_number'),
                    cell: (row: CaseRow) => (
                        <div>
                            <p className="font-semibold text-[color:var(--text)]">{row.case_number}</p>
                            <p className="mt-1 text-xs uppercase text-[color:var(--muted)]">
                                {t('cases.main_case_type.crime')}
                            </p>
                        </div>
                    ),
                },
                {
                    key: 'police_station',
                    header: t('cases.police_station'),
                    cell: (row: CaseRow) => row.police_station ?? t('common.not_provided'),
                },
                {
                    key: 'stolen_property_type',
                    header: t('cases.stolen_property_type'),
                    cell: (row: CaseRow) => row.stolen_property_type ?? t('common.not_provided'),
                },
                {
                    key: 'status',
                    header: t('reports.status'),
                    cell: (row: CaseRow) => <StatusBadge value={row.status} />,
                },
            ];
        }

        if (isCivilLikeView) {
            return [
                {
                    key: 'case_number',
                    header: t('cases.case_number'),
                    cell: (row: CaseRow) => (
                        <div>
                            <p className="font-semibold text-[color:var(--text)]">{row.case_number}</p>
                            <p className="mt-1 text-xs uppercase text-[color:var(--muted)]">
                                {t('cases.court_file_number')}: {row.external_court_file_number ?? t('common.not_provided')}
                            </p>
                        </div>
                    ),
                },
                {
                    key: 'assignment',
                    header: t('common.assignment'),
                    cell: (row: CaseRow) => (
                        <div className="space-y-1">
                            <p className="text-sm font-medium text-[color:var(--text)]">
                                {row.assigned_team_leader?.name ?? t('common.unassigned')}
                            </p>
                            <p className="text-sm text-[color:var(--muted)]">
                                {row.assigned_legal_expert?.name ?? t('common.unassigned')}
                            </p>
                        </div>
                    ),
                },
                {
                    key: 'plaintiff',
                    header: t('cases.plaintiff'),
                    cell: (row: CaseRow) => row.plaintiff ?? t('common.not_provided'),
                },
                {
                    key: 'defendant',
                    header: t('cases.defendant'),
                    cell: (row: CaseRow) => row.defendant ?? t('common.not_provided'),
                },
                {
                    key: 'status',
                    header: t('reports.status'),
                    cell: (row: CaseRow) => <StatusBadge value={row.status} />,
                },
            ];
        }

        return [
            {
                key: 'case_number',
                header: t('cases.case_number'),
                cell: (row: CaseRow) => (
                    <div>
                        <p className="font-semibold text-[color:var(--text)]">{row.case_number}</p>
                        <p className="mt-1 text-xs uppercase text-[color:var(--muted)]">
                            {row.main_case_type ? t(`cases.main_case_type.${row.main_case_type}`) : t('common.not_set')}
                        </p>
                    </div>
                ),
            },
            {
                key: 'details',
                header: t('reports.subject'),
                cell: (row: CaseRow) => (
                    <div>
                        {row.main_case_type === 'crime' ? (
                            <>
                                <p className="font-medium text-[color:var(--text)]">{row.police_station ?? t('common.not_provided')}</p>
                                <p className="mt-1 text-sm text-[color:var(--muted)]">{row.stolen_property_type ?? t('common.not_provided')}</p>
                            </>
                        ) : (
                            <>
                                <p className="font-medium text-[color:var(--text)]">{row.plaintiff ?? t('common.not_provided')}</p>
                                <p className="mt-1 text-sm text-[color:var(--muted)]">{t('common.versus')} {row.defendant ?? t('common.not_provided')}</p>
                            </>
                        )}
                    </div>
                ),
            },
            {
                key: 'status',
                header: t('reports.status'),
                cell: (row: CaseRow) => <StatusBadge value={row.status} />,
            },
        ];
    }, [isCivilLikeView, isCrimeView, t]);

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

                <div className="flex flex-wrap gap-3">
                    <button
                        type="button"
                        onClick={() => {
                            setData('main_case_type', '');
                            router.get(route('cases.index'), { ...data, main_case_type: '' }, { preserveState: true, replace: true });
                        }}
                        className={`btn-base focus-ring ${data.main_case_type === '' ? 'btn-primary' : 'btn-secondary'}`}
                    >
                        {t('common.all')}
                    </button>
                    {mainCaseTypeOptions.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => {
                                setData('main_case_type', option.value);
                                router.get(route('cases.index'), { ...data, main_case_type: option.value }, { preserveState: true, replace: true });
                            }}
                            className={`btn-base focus-ring ${data.main_case_type === option.value ? 'btn-primary' : 'btn-secondary'}`}
                        >
                            {option.label}
                        </button>
                    ))}
                </div>

                <FiltersToolbar
                    title={t('common.apply_filters')}
                    actions={
                        <>
                            <button
                                type="button"
                                onClick={() => {
                                    setData({ search: '', status: '', main_case_type: '' });
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
                        {visibleRows.length} {t('common.records')}
                    </div>
                </FiltersToolbar>

                <DataTable
                    rows={visibleRows}
                    rowKey={(row) => row.id}
                    emptyTitle={t('cases.empty_title')}
                    emptyDescription={t('cases.empty_description')}
                    columns={[
                        ...columns,
                        {
                            key: 'actions',
                            header: t('common.actions'),
                            className: 'w-72',
                            cell: (row: CaseRow) => (
                                <div className="flex flex-wrap gap-2">
                                    <Link href={route('cases.show', { legalCase: row.id })} className="btn-base btn-secondary focus-ring">
                                        {t('common.view')}
                                    </Link>
                                    {row.can_update ? (
                                        <Link href={route('cases.edit', { legalCase: row.id })} className="btn-base btn-secondary focus-ring">
                                            {t('common.edit')}
                                        </Link>
                                    ) : null}
                                    {row.can_delete ? (
                                        <button
                                            type="button"
                                            onClick={() => setCaseToDelete(row)}
                                            className="btn-base btn-danger focus-ring"
                                        >
                                            {t('common.delete')}
                                        </button>
                                    ) : null}
                                </div>
                            ),
                        },
                    ]}
                />
            </PageContainer>

            <ConfirmationDialog
                open={caseToDelete !== null}
                title={t('cases.delete_title')}
                description={t('cases.delete_description')}
                confirmLabel={t('common.delete')}
                onCancel={() => setCaseToDelete(null)}
                onConfirm={() => {
                    if (!caseToDelete) {
                        return;
                    }

                    deleteForm.delete(route('cases.destroy', { legalCase: caseToDelete.id }), {
                        preserveScroll: true,
                        onSuccess: () => {
                            finishSuccessfulSubmission(deleteForm, {
                                afterSuccess: () => {
                                    setCaseToDelete(null);
                                },
                            });
                        },
                    });
                }}
                processing={deleteForm.processing}
            />
        </AuthenticatedLayout>
    );
}
