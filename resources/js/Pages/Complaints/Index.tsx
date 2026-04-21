import DataTable from '@/Components/Ui/DataTable';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import PageContainer from '@/Components/Ui/PageContainer';
import Pagination from '@/Components/Ui/Pagination';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

type ComplaintRow = {
    id: string;
    complaint_number: string;
    subject: string;
    complainant_name: string;
    complainant_type: string;
    status: string;
    is_overdue?: boolean;
    submitted_at?: string | null;
    department_response_deadline_at?: string | null;
    department?: { name_en?: string | null; name_am?: string | null } | null;
    branch?: { name_en?: string | null; name_am?: string | null } | null;
    can?: {
        update?: boolean;
        respond_department?: boolean;
        forward_to_committee?: boolean;
        review_committee?: boolean;
    };
};

type Option = { value: string; label: string };

type Props = {
    filters: Record<string, string>;
    complaints: {
        data: ComplaintRow[];
        links?: {
            first?: string | null;
            last?: string | null;
            prev?: string | null;
            next?: string | null;
        };
        meta?: {
            links?: Array<{ url: string | null; label: string; active: boolean }>;
            current_page?: number;
            from?: number | null;
            last_page?: number;
            to?: number | null;
            total?: number;
        };
    };
    statusOptions: Option[];
    complainantTypeOptions: Option[];
    branches: Array<{ id: string; name_en: string; name_am?: string | null }>;
    departments: Array<{ id: string; name_en: string; name_am?: string | null }>;
    can: {
        create: boolean;
        viewReports: boolean;
        manageSettings: boolean;
    };
};

export default function ComplaintIndex({ filters, complaints, statusOptions, complainantTypeOptions, branches, departments, can }: Props) {
    const form = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
        branch_id: filters.branch_id ?? '',
        department_id: filters.department_id ?? '',
        complainant_type: filters.complainant_type ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });

    const applyFilters = () => {
        router.get(route('complaints.index'), form.data, {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        router.get(route('complaints.index'));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Complaints' },
            ]}
        >
            <Head title="Complaints" />
            <PageContainer>
                <SectionHeader
                    eyebrow="Complaint management"
                    title="Complaints"
                    description="Track complaint intake, department response deadlines, escalation, and committee review in one place."
                    action={
                        <div className="flex gap-2">
                            {can.viewReports ? <Link href={route('complaints.reports')} className="btn-base btn-secondary focus-ring">Reports</Link> : null}
                            {can.manageSettings ? <Link href={route('complaints.settings')} className="btn-base btn-secondary focus-ring">Settings</Link> : null}
                            {can.create ? <Link href={route('complaints.create')} className="btn-base btn-primary focus-ring">New Complaint</Link> : null}
                        </div>
                    }
                />

                <FiltersToolbar
                    title="Filters"
                    actions={
                        <>
                            <button type="button" onClick={resetFilters} className="btn-base btn-secondary focus-ring">
                                Reset
                            </button>
                            <button type="button" onClick={applyFilters} className="btn-base btn-primary focus-ring">
                                Apply Filters
                            </button>
                        </>
                    }
                >
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">Search</span>
                        <input className="input-ui" value={form.data.search} onChange={(event) => form.setData('search', event.target.value)} />
                    </label>
                    <SelectFilter label="Status" value={form.data.status} onChange={(value) => form.setData('status', value)} options={statusOptions} />
                    <SelectFilter
                        label="Branch"
                        value={form.data.branch_id}
                        onChange={(value) => form.setData('branch_id', value)}
                        options={branches.map((branch) => ({ value: branch.id, label: branch.name_en }))}
                    />
                    <SelectFilter
                        label="Department"
                        value={form.data.department_id}
                        onChange={(value) => form.setData('department_id', value)}
                        options={departments.map((department) => ({ value: department.id, label: department.name_en }))}
                    />
                    <SelectFilter label="Complainant Type" value={form.data.complainant_type} onChange={(value) => form.setData('complainant_type', value)} options={complainantTypeOptions} />
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">Date From</span>
                        <input type="date" className="input-ui" value={form.data.date_from} onChange={(event) => form.setData('date_from', event.target.value)} />
                    </label>
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">Date To</span>
                        <input type="date" className="input-ui" value={form.data.date_to} onChange={(event) => form.setData('date_to', event.target.value)} />
                    </label>
                </FiltersToolbar>

                <SurfaceCard>
                    <DataTable
                        rows={complaints.data}
                        rowKey={(row) => row.id}
                        emptyTitle="No complaints found"
                        emptyDescription="Adjust the current filters or submit the first complaint."
                        columns={[
                            { key: 'complaint_number', header: 'Complaint No.', cell: (row) => row.complaint_number },
                            { key: 'subject', header: 'Subject', cell: (row) => <div className="space-y-1"><p>{row.subject}</p>{row.is_overdue ? <span className="text-xs font-semibold uppercase tracking-[0.14em] text-rose-500">Overdue</span> : null}</div> },
                            { key: 'complainant', header: 'Complainant', cell: (row) => <div className="space-y-1"><p>{row.complainant_name}</p><p className="text-xs text-[color:var(--muted)]">{row.complainant_type.replaceAll('_', ' ')}</p></div> },
                            { key: 'department', header: 'Department', cell: (row) => row.department?.name_en ?? '-' },
                            { key: 'branch', header: 'Branch', cell: (row) => row.branch?.name_en ?? '-' },
                            { key: 'submitted_at', header: 'Submitted / Deadline', cell: (row) => <div className="space-y-1 text-sm"><p>{row.submitted_at ? new Date(row.submitted_at).toLocaleDateString() : '-'}</p><p className="text-[color:var(--muted)]">{row.department_response_deadline_at ? new Date(row.department_response_deadline_at).toLocaleDateString() : '-'}</p></div> },
                            { key: 'status', header: 'Status', cell: (row) => <StatusBadge value={row.status} /> },
                        ]}
                        actions={(row) => {
                            const items = [{ label: 'View', href: route('complaints.show', row.id) }];

                            if (row.can?.update) {
                                items.push({ label: 'Edit', href: route('complaints.edit', row.id) });
                            }

                            if (row.can?.respond_department) {
                                items.push({ label: 'Respond', href: `${route('complaints.show', row.id)}#department-response` });
                            }

                            if (row.can?.forward_to_committee) {
                                items.push({ label: 'Forward', href: `${route('complaints.show', row.id)}#complainant-actions` });
                            }

                            if (row.can?.review_committee) {
                                items.push({ label: 'Committee Review', href: `${route('complaints.show', row.id)}#committee-review` });
                            }

                            return items;
                        }}
                    />
                </SurfaceCard>

                <Pagination links={Array.isArray(complaints.meta?.links) ? complaints.meta.links : []} meta={complaints.meta} />
            </PageContainer>
        </AuthenticatedLayout>
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
