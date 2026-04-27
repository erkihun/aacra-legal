import DataTable from '@/Components/Ui/DataTable';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import MetricCard from '@/Components/Ui/MetricCard';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';

type SummaryRow = { label: string; total: number };
type Option = { value: string; label: string };

type Props = {
    filters: Record<string, string>;
    metrics: {
        total: number;
        open: number;
        overdue: number;
        escalated: number;
        committee_decided: number;
        resolved: number;
    };
    by_status: Array<{ status: string; total: number }>;
    by_department: SummaryRow[];
    by_branch: SummaryRow[];
    by_complainant_type: SummaryRow[];
    rows: Array<Record<string, string | null>>;
    filterOptions: {
        statuses: Option[];
        complainantTypes: Option[];
        branches: Option[];
        departments: Option[];
    };
};

export default function ComplaintReports({ filters, metrics, by_status, by_department, by_branch, by_complainant_type, rows, filterOptions }: Props) {
    const { t } = useI18n();
    const form = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
        branch_id: filters.branch_id ?? '',
        department_id: filters.department_id ?? '',
        complainant_type: filters.complainant_type ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.complaints'), href: route('complaints.index') },
                { label: t('navigation.complaint_reports') },
            ]}
        >
            <Head title={t('complaints.reports.title')} />
            <PageContainer>
                <SectionHeader
                    eyebrow={t('complaints.index.eyebrow')}
                    title={t('complaints.reports.title')}
                    description={t('complaints.reports.description')}
                    action={<Link href={route('complaints.index')} className="btn-base btn-secondary focus-ring">{t('complaints.reports.actions.open_complaints')}</Link>}
                />

                <FiltersToolbar
                    title={t('complaints.reports.filters.title')}
                    actions={
                        <>
                            <button type="button" onClick={() => router.get(route('complaints.reports'))} className="btn-base btn-secondary focus-ring">
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                onClick={() => router.get(route('complaints.reports'), form.data, { preserveState: true, replace: true })}
                                className="btn-base btn-primary focus-ring"
                            >
                                {t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">{t('complaints.filters.search')}</span>
                        <input className="input-ui" value={form.data.search} onChange={(event) => form.setData('search', event.target.value)} />
                    </label>
                    <SelectFilter label={t('complaints.filters.branch')} value={form.data.branch_id} onChange={(value) => form.setData('branch_id', value)} options={filterOptions.branches} allLabel={t('common.all')} />
                    <SelectFilter label={t('complaints.filters.department')} value={form.data.department_id} onChange={(value) => form.setData('department_id', value)} options={filterOptions.departments} allLabel={t('common.all')} />
                    <SelectFilter
                        label={t('complaints.filters.complainant_type')}
                        value={form.data.complainant_type}
                        onChange={(value) => form.setData('complainant_type', value)}
                        options={filterOptions.complainantTypes}
                        allLabel={t('common.all')}
                    />
                    <SelectFilter label={t('common.status')} value={form.data.status} onChange={(value) => form.setData('status', value)} options={filterOptions.statuses} allLabel={t('common.all')} />
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">{t('complaints.filters.date_from')}</span>
                        <input type="date" className="input-ui" value={form.data.date_from} onChange={(event) => form.setData('date_from', event.target.value)} />
                    </label>
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">{t('complaints.filters.date_to')}</span>
                        <input type="date" className="input-ui" value={form.data.date_to} onChange={(event) => form.setData('date_to', event.target.value)} />
                    </label>
                </FiltersToolbar>

                <div className="stat-grid">
                    <MetricCard label={t('complaints.reports.metrics.total')} value={metrics.total} />
                    <MetricCard label={t('complaints.reports.metrics.open')} value={metrics.open} />
                    <MetricCard label={t('complaints.reports.metrics.overdue')} value={metrics.overdue} />
                    <MetricCard label={t('complaints.reports.metrics.escalated')} value={metrics.escalated} />
                    <MetricCard label={t('complaints.reports.metrics.committee_decided')} value={metrics.committee_decided} />
                    <MetricCard label={t('complaints.reports.metrics.resolved')} value={metrics.resolved} />
                </div>

                <div className="grid gap-4 xl:grid-cols-4">
                    <SimpleTable title={t('complaints.reports.groups.by_status')} rows={by_status.map((row) => ({ label: row.status, total: row.total }))} emptyLabel={t('complaints.reports.empty.summary')} />
                    <SimpleTable title={t('complaints.reports.groups.by_department')} rows={by_department} emptyLabel={t('complaints.reports.empty.summary')} />
                    <SimpleTable title={t('complaints.reports.groups.by_branch')} rows={by_branch} emptyLabel={t('complaints.reports.empty.summary')} />
                    <SimpleTable title={t('complaints.reports.groups.by_complainant_type')} rows={by_complainant_type} emptyLabel={t('complaints.reports.empty.summary')} />
                </div>

                <SurfaceCard>
                    <DataTable
                        rows={rows}
                        rowKey={(row, index) => `${row.complaint_number}-${index}`}
                        emptyTitle={t('complaints.reports.empty.table_title')}
                        emptyDescription={t('complaints.reports.empty.table_description')}
                        columns={[
                            { key: 'complaint_number', header: t('complaints.table.complaint_number'), cell: (row) => row.complaint_number },
                            { key: 'subject', header: t('complaints.table.subject'), cell: (row) => row.subject },
                            { key: 'complainant', header: t('complaints.table.complainant'), cell: (row) => row.complainant },
                            { key: 'complainant_type', header: t('complaints.filters.complainant_type'), cell: (row) => row.complainant_type_label ?? row.complainant_type ?? '-' },
                            { key: 'branch', header: t('complaints.table.branch'), cell: (row) => row.branch ?? '-' },
                            { key: 'department', header: t('complaints.table.department'), cell: (row) => row.department ?? '-' },
                            { key: 'status', header: t('common.status'), cell: (row) => row.status_label ?? row.status ?? '-' },
                            { key: 'submitted_at', header: t('complaints.labels.submitted'), cell: (row) => row.submitted_at ?? '-' },
                            { key: 'deadline', header: t('complaints.labels.response_deadline'), cell: (row) => row.deadline ?? '-' },
                        ]}
                    />
                </SurfaceCard>
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function SimpleTable({ title, rows, emptyLabel }: { title: string; rows: SummaryRow[]; emptyLabel: string }) {
    return (
        <SurfaceCard>
            <h2 className="text-lg font-semibold text-[color:var(--text)]">{title}</h2>
            <div className="mt-4 space-y-2">
                {rows.length === 0 ? <p className="text-sm text-[color:var(--muted)]">{emptyLabel}</p> : null}
                {rows.map((row) => (
                    <div key={`${title}-${row.label}`} className="flex items-center justify-between rounded-2xl border border-[color:var(--border)] px-4 py-3">
                        <span className="text-sm text-[color:var(--text)]">{row.label || '-'}</span>
                        <span className="text-sm font-semibold text-[color:var(--text)]">{row.total}</span>
                    </div>
                ))}
            </div>
        </SurfaceCard>
    );
}

function SelectFilter({
    label,
    value,
    onChange,
    options,
    allLabel,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: Option[];
    allLabel: string;
}) {
    return (
        <label className="block space-y-2">
            <span className="text-sm font-medium text-[color:var(--text)]">{label}</span>
            <select className="select-ui" value={value} onChange={(event) => onChange(event.target.value)}>
                <option value="">{allLabel}</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}
