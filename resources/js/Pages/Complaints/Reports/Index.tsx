import DataTable from '@/Components/Ui/DataTable';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import MetricCard from '@/Components/Ui/MetricCard';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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
        branches: Option[];
        departments: Option[];
    };
};

export default function ComplaintReports({ filters, metrics, by_status, by_department, by_branch, by_complainant_type, rows, filterOptions }: Props) {
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
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Complaints', href: route('complaints.index') },
                { label: 'Complaint Reports' },
            ]}
        >
            <Head title="Complaint Reports" />
            <PageContainer>
                <SectionHeader
                    eyebrow="Complaint management"
                    title="Complaint Reports"
                    description="Review complaint volume, overdue items, escalation, and committee outcomes across branches and departments."
                    action={<Link href={route('complaints.index')} className="btn-base btn-secondary focus-ring">Open Complaints</Link>}
                />

                <FiltersToolbar
                    title="Report Filters"
                    actions={
                        <>
                            <button type="button" onClick={() => router.get(route('complaints.reports'))} className="btn-base btn-secondary focus-ring">
                                Reset
                            </button>
                            <button
                                type="button"
                                onClick={() => router.get(route('complaints.reports'), form.data, { preserveState: true, replace: true })}
                                className="btn-base btn-primary focus-ring"
                            >
                                Apply Filters
                            </button>
                        </>
                    }
                >
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">Search</span>
                        <input className="input-ui" value={form.data.search} onChange={(event) => form.setData('search', event.target.value)} />
                    </label>
                    <SelectFilter label="Branch" value={form.data.branch_id} onChange={(value) => form.setData('branch_id', value)} options={filterOptions.branches} />
                    <SelectFilter label="Department" value={form.data.department_id} onChange={(value) => form.setData('department_id', value)} options={filterOptions.departments} />
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">Complainant Type</span>
                        <input className="input-ui" value={form.data.complainant_type} onChange={(event) => form.setData('complainant_type', event.target.value)} />
                    </label>
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">Status</span>
                        <input className="input-ui" value={form.data.status} onChange={(event) => form.setData('status', event.target.value)} />
                    </label>
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">Date From</span>
                        <input type="date" className="input-ui" value={form.data.date_from} onChange={(event) => form.setData('date_from', event.target.value)} />
                    </label>
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">Date To</span>
                        <input type="date" className="input-ui" value={form.data.date_to} onChange={(event) => form.setData('date_to', event.target.value)} />
                    </label>
                </FiltersToolbar>

                <div className="stat-grid">
                    <MetricCard label="Total Complaints" value={metrics.total} />
                    <MetricCard label="Open Complaints" value={metrics.open} />
                    <MetricCard label="Overdue Complaints" value={metrics.overdue} />
                    <MetricCard label="Escalated Complaints" value={metrics.escalated} />
                    <MetricCard label="Committee Decided" value={metrics.committee_decided} />
                    <MetricCard label="Resolved Complaints" value={metrics.resolved} />
                </div>

                <div className="grid gap-4 xl:grid-cols-4">
                    <SimpleTable title="By Status" rows={by_status.map((row) => ({ label: row.status, total: row.total }))} />
                    <SimpleTable title="By Department" rows={by_department} />
                    <SimpleTable title="By Branch" rows={by_branch} />
                    <SimpleTable title="By Complainant Type" rows={by_complainant_type} />
                </div>

                <SurfaceCard>
                    <DataTable
                        rows={rows}
                        rowKey={(row, index) => `${row.complaint_number}-${index}`}
                        emptyTitle="No complaints found"
                        emptyDescription="No complaint rows match the current report filters."
                        columns={[
                            { key: 'complaint_number', header: 'Complaint No.', cell: (row) => row.complaint_number },
                            { key: 'subject', header: 'Subject', cell: (row) => row.subject },
                            { key: 'complainant', header: 'Complainant', cell: (row) => row.complainant },
                            { key: 'complainant_type', header: 'Type', cell: (row) => row.complainant_type ?? '-' },
                            { key: 'branch', header: 'Branch', cell: (row) => row.branch ?? '-' },
                            { key: 'department', header: 'Department', cell: (row) => row.department ?? '-' },
                            { key: 'status', header: 'Status', cell: (row) => row.status ?? '-' },
                            { key: 'submitted_at', header: 'Submitted', cell: (row) => row.submitted_at ?? '-' },
                            { key: 'deadline', header: 'Deadline', cell: (row) => row.deadline ?? '-' },
                        ]}
                    />
                </SurfaceCard>
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function SimpleTable({ title, rows }: { title: string; rows: SummaryRow[] }) {
    return (
        <SurfaceCard>
            <h2 className="text-lg font-semibold text-[color:var(--text)]">{title}</h2>
            <div className="mt-4 space-y-2">
                {rows.length === 0 ? <p className="text-sm text-[color:var(--muted)]">No records.</p> : null}
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

function SelectFilter({ label, value, onChange, options }: { label: string; value: string; onChange: (value: string) => void; options: Option[] }) {
    return (
        <label className="block space-y-2">
            <span className="text-sm font-medium text-[color:var(--text)]">{label}</span>
            <select className="select-ui" value={value} onChange={(event) => onChange(event.target.value)}>
                <option value="">All</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}
