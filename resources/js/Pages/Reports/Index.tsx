import DataTable from '@/Components/Ui/DataTable';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import MetricCard from '@/Components/Ui/MetricCard';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

type Row = Record<string, string | number | null>;

type Option = {
    value: string;
    label: string;
};

type ReportsPageProps = {
    filters: {
        date_from?: string;
        date_to?: string;
        department_id?: string;
        team_id?: string;
        status?: string;
        priority?: string;
        expert_id?: string;
    };
    cases_by_status: Array<{
        status: string;
        total: number;
    }>;
    advisory_by_department: Array<{
        department: string;
        total: number;
    }>;
    expert_workload: Array<{
        expert: string;
        advisory: number;
        cases: number;
        total: number;
    }>;
    turnaround: {
        average_advisory_days: number;
        average_case_days: number;
        rows: Array<{
            module: string;
            reference: string;
            subject: string;
            opened_at: string | null;
            completed_at: string | null;
            turnaround_days: number;
        }>;
    };
    hearing_schedule: Array<{
        case_number: string;
        plaintiff: string;
        court: string | null;
        assigned_expert: string | null;
        next_hearing_date: string | null;
        status: string | null;
    }>;
    overdue_items: Array<{
        module: string;
        reference: string;
        subject: string;
        owner: string | null;
        due_date: string | null;
        status: string | null;
    }>;
    filter_options: {
        departments: Option[];
        teams: Option[];
        experts: Option[];
    };
};

function ReportSection({
    title,
    exportType,
    rows,
    columns,
    canExport,
    filters,
    emptyLabel,
}: {
    title: string;
    exportType: string;
    rows: Row[];
    columns: Array<{ key: string; header: string }>;
    canExport: boolean;
    filters: ReportsPageProps['filters'];
    emptyLabel: string;
}) {
    const { t } = useI18n();

    return (
        <SurfaceCard>
            <div className="mb-5 flex items-center justify-between gap-3">
                <h2 className="text-xl font-semibold text-[color:var(--text)]">{title}</h2>
                {canExport ? (
                    <Link href={route('reports.export', exportType)} data={filters} className="btn-base btn-secondary focus-ring">
                        {t('reports.export_excel')}
                    </Link>
                ) : null}
            </div>

            <DataTable
                rows={rows}
                rowKey={(_, index) => `${exportType}-${index}`}
                emptyTitle={title}
                emptyDescription={emptyLabel}
                columns={columns.map((column) => ({
                    key: column.key,
                    header: column.header,
                    cell: (row: Row) => row[column.key] ?? '',
                }))}
            />
        </SurfaceCard>
    );
}

export default function Index({
    filters,
    cases_by_status,
    advisory_by_department,
    expert_workload,
    turnaround,
    hearing_schedule,
    overdue_items,
    filter_options,
}: ReportsPageProps) {
    const { t } = useI18n();
    const { props } = usePage<PageProps>();
    const canExport = props.auth.user?.permissions.includes('reports.export') ?? false;
    const [isFiltering, setIsFiltering] = useState(false);
    const { data, setData } = useForm({
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
        department_id: filters.department_id ?? '',
        team_id: filters.team_id ?? '',
        status: filters.status ?? '',
        priority: filters.priority ?? '',
        expert_id: filters.expert_id ?? '',
    });

    const applyFilters = () => {
        setIsFiltering(true);

        router.get(route('reports.index'), data, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const resetFilters = () => {
        router.get(route('reports.index'));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.reports') },
            ]}
        >
            <Head title={t('navigation.reports')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('reports.eyebrow')}
                    title={t('reports.title')}
                    description={t('reports.description')}
                />

                <div className="stat-grid">
                    <MetricCard label={t('reports.metrics.case_rows')} value={cases_by_status.reduce((sum, row) => sum + row.total, 0)} />
                    <MetricCard label={t('reports.metrics.advisory_rows')} value={advisory_by_department.reduce((sum, row) => sum + row.total, 0)} />
                    <MetricCard label={t('reports.metrics.average_advisory_days')} value={turnaround.average_advisory_days} />
                    <MetricCard label={t('reports.metrics.average_case_days')} value={turnaround.average_case_days} />
                </div>

                <FiltersToolbar
                    title={t('reports.filters')}
                    actions={
                        <>
                            <button type="button" onClick={resetFilters} className="btn-base btn-secondary focus-ring">
                                {t('common.reset')}
                            </button>
                            <button type="button" onClick={applyFilters} className="btn-base btn-primary focus-ring" disabled={isFiltering}>
                                {isFiltering ? `${t('common.apply_filters')}...` : t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <FilterInput label={t('reports.date_from')} type="date" value={data.date_from} onChange={(value) => setData('date_from', value)} />
                    <FilterInput label={t('reports.date_to')} type="date" value={data.date_to} onChange={(value) => setData('date_to', value)} />
                    <FilterSelect label={t('reports.department')} value={data.department_id} options={filter_options.departments} onChange={(value) => setData('department_id', value)} />
                    <FilterSelect label={t('reports.team')} value={data.team_id} options={filter_options.teams} onChange={(value) => setData('team_id', value)} />
                    <FilterInput label={t('reports.status')} value={data.status} onChange={(value) => setData('status', value)} placeholder={t('reports.status_placeholder')} />
                    <FilterInput label={t('reports.priority')} value={data.priority} onChange={(value) => setData('priority', value)} placeholder={t('reports.priority_placeholder')} />
                    <FilterSelect label={t('reports.expert')} value={data.expert_id} options={filter_options.experts} onChange={(value) => setData('expert_id', value)} />
                </FiltersToolbar>

                <div className="grid gap-4 xl:grid-cols-2">
                    <ReportSection
                        title={t('reports.cases_by_status')}
                        exportType="cases-by-status"
                        columns={[
                            { key: 'status', header: t('reports.status') },
                            { key: 'total', header: t('reports.total') },
                        ]}
                        rows={cases_by_status}
                        canExport={canExport}
                        filters={filters}
                        emptyLabel={t('reports.empty')}
                    />
                    <ReportSection
                        title={t('reports.advisory_by_department')}
                        exportType="advisory-by-department"
                        columns={[
                            { key: 'department', header: t('reports.department') },
                            { key: 'total', header: t('reports.total') },
                        ]}
                        rows={advisory_by_department}
                        canExport={canExport}
                        filters={filters}
                        emptyLabel={t('reports.empty')}
                    />
                </div>

                <div className="grid gap-4">
                    <ReportSection
                        title={t('reports.expert_workload')}
                        exportType="expert-workload"
                        columns={[
                            { key: 'expert', header: t('reports.expert') },
                            { key: 'advisory', header: t('reports.advisory') },
                            { key: 'cases', header: t('reports.cases') },
                            { key: 'total', header: t('reports.total') },
                        ]}
                        rows={expert_workload}
                        canExport={canExport}
                        filters={filters}
                        emptyLabel={t('reports.empty')}
                    />
                    <ReportSection
                        title={t('reports.turnaround')}
                        exportType="turnaround-times"
                        columns={[
                            { key: 'module', header: t('reports.module') },
                            { key: 'reference', header: t('reports.reference') },
                            { key: 'subject', header: t('reports.subject') },
                            { key: 'opened_at', header: t('reports.opened_at') },
                            { key: 'completed_at', header: t('reports.completed_at') },
                            { key: 'turnaround_days', header: t('reports.days') },
                        ]}
                        rows={turnaround.rows}
                        canExport={canExport}
                        filters={filters}
                        emptyLabel={t('reports.empty')}
                    />
                    <ReportSection
                        title={t('reports.hearing_schedule')}
                        exportType="hearing-schedule"
                        columns={[
                            { key: 'case_number', header: t('reports.case_number') },
                            { key: 'plaintiff', header: t('reports.plaintiff') },
                            { key: 'court', header: t('reports.court') },
                            { key: 'assigned_expert', header: t('reports.assigned_expert') },
                            { key: 'next_hearing_date', header: t('reports.next_hearing_date') },
                            { key: 'status', header: t('reports.status') },
                        ]}
                        rows={hearing_schedule}
                        canExport={canExport}
                        filters={filters}
                        emptyLabel={t('reports.empty')}
                    />
                    <ReportSection
                        title={t('reports.overdue_items')}
                        exportType="overdue-items"
                        columns={[
                            { key: 'module', header: t('reports.module') },
                            { key: 'reference', header: t('reports.reference') },
                            { key: 'subject', header: t('reports.subject') },
                            { key: 'owner', header: t('reports.owner') },
                            { key: 'due_date', header: t('reports.due_date') },
                            { key: 'status', header: t('reports.status') },
                        ]}
                        rows={overdue_items}
                        canExport={canExport}
                        filters={filters}
                        emptyLabel={t('reports.empty')}
                    />
                </div>
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function FilterInput({
    label,
    value,
    onChange,
    type = 'text',
    placeholder,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    type?: 'text' | 'date';
    placeholder?: string;
}) {
    return (
        <label className="block space-y-2">
            <span className="text-sm font-medium text-[color:var(--text)]">{label}</span>
            <input type={type} value={value} placeholder={placeholder} onChange={(event) => onChange(event.target.value)} className="input-ui" />
        </label>
    );
}

function FilterSelect({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string;
    options: Option[];
    onChange: (value: string) => void;
}) {
    return (
        <label className="block space-y-2">
            <span className="text-sm font-medium text-[color:var(--text)]">{label}</span>
            <select value={value} onChange={(event) => onChange(event.target.value)} className="select-ui">
                <option value=""></option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}
