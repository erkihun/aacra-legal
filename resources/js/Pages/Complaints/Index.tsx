import DataTable from '@/Components/Ui/DataTable';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import LocalizedDateInput from '@/Components/Ui/LocalizedDateInput';
import PageContainer from '@/Components/Ui/PageContainer';
import Pagination from '@/Components/Ui/Pagination';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { localizeName, translateComplaintValue } from '@/Pages/Complaints/shared';
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

    const { locale, t } = useI18n();
    const { formatDate } = useDateFormatter();

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
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.complaints') },
            ]}
        >
            <Head title={t('navigation.complaints')} />
            <PageContainer>
                <SectionHeader
                    eyebrow={t('complaints.index.eyebrow')}
                    title={t('complaints.index.title')}
                    description={t('complaints.index.description')}
                    action={
                        <div className="flex gap-2">
                            {can.viewReports ? <Link href={route('complaints.reports')} className="btn-base btn-secondary focus-ring">{t('navigation.complaint_reports')}</Link> : null}
                            {can.manageSettings ? <Link href={route('complaints.settings')} className="btn-base btn-secondary focus-ring">{t('navigation.complaint_settings')}</Link> : null}
                            {can.create ? <Link href={route('complaints.create')} className="btn-base btn-primary focus-ring">{t('complaints.actions.new')}</Link> : null}
                        </div>
                    }
                />

                <FiltersToolbar
                    title={t('complaints.filters.title')}
                    actions={
                        <>
                            <button type="button" onClick={resetFilters} className="btn-base btn-secondary focus-ring">
                                {t('common.reset')}
                            </button>
                            <button type="button" onClick={applyFilters} className="btn-base btn-primary focus-ring">
                                {t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">{t('complaints.filters.search')}</span>
                        <input
                            className="input-ui"
                            value={form.data.search}
                            placeholder={t('complaints.filters.search_placeholder')}
                            onChange={(event) => form.setData('search', event.target.value)}
                        />
                    </label>
                    <SelectFilter label={t('common.status')} value={form.data.status} onChange={(value) => form.setData('status', value)} options={statusOptions} allLabel={t('common.all')} />
                    <SelectFilter
                        label={t('complaints.filters.branch')}
                        value={form.data.branch_id}
                        onChange={(value) => form.setData('branch_id', value)}
                        options={branches.map((branch) => ({ value: branch.id, label: localizeName(branch, locale) }))}
                        allLabel={t('common.all')}
                    />
                    <SelectFilter
                        label={t('complaints.filters.department')}
                        value={form.data.department_id}
                        onChange={(value) => form.setData('department_id', value)}
                        options={departments.map((department) => ({ value: department.id, label: localizeName(department, locale) }))}
                        allLabel={t('common.all')}
                    />
                    <SelectFilter
                        label={t('complaints.filters.complainant_type')}
                        value={form.data.complainant_type}
                        onChange={(value) => form.setData('complainant_type', value)}
                        options={complainantTypeOptions}
                        allLabel={t('common.all')}
                    />
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">{t('complaints.filters.date_from')}</span>
                        <LocalizedDateInput className="input-ui" value={form.data.date_from} onChange={(value) => form.setData('date_from', value)} />
                    </label>
                    <label className="block space-y-2">
                        <span className="text-sm font-medium text-[color:var(--text)]">{t('complaints.filters.date_to')}</span>
                        <LocalizedDateInput className="input-ui" value={form.data.date_to} onChange={(value) => form.setData('date_to', value)} />
                    </label>
                </FiltersToolbar>

                <SurfaceCard>
                    <DataTable
                        rows={complaints.data}
                        rowKey={(row) => row.id}
                        emptyTitle={t('complaints.empty.index_title')}
                        emptyDescription={t('complaints.empty.index_description')}
                        columns={[
                            { key: 'complaint_number', header: t('complaints.table.complaint_number'), cell: (row) => row.complaint_number },
                            {
                                key: 'subject',
                                header: t('complaints.table.subject'),
                                cell: (row) => (
                                    <div className="space-y-1">
                                        <p>{row.subject}</p>
                                        {row.is_overdue ? <span className="text-xs font-semibold uppercase tracking-[0.14em] text-rose-500">{t('complaints.labels.overdue')}</span> : null}
                                    </div>
                                ),
                            },
                            {
                                key: 'complainant',
                                header: t('complaints.table.complainant'),
                                cell: (row) => (
                                    <div className="space-y-1">
                                        <p>{row.complainant_name}</p>
                                        <p className="text-xs text-[color:var(--muted)]">{translateComplaintValue('complaints.complainant_types', row.complainant_type, t)}</p>
                                    </div>
                                ),
                            },
                            { key: 'department', header: t('complaints.table.department'), cell: (row) => localizeName(row.department, locale) },
                            { key: 'branch', header: t('complaints.table.branch'), cell: (row) => localizeName(row.branch, locale) },
                            {
                                key: 'submitted_at',
                                header: t('complaints.table.submitted_deadline'),
                                cell: (row) => (
                                    <div className="space-y-1 text-sm">
                                        <p>{formatDate(row.submitted_at, '-')}</p>
                                        <p className="text-[color:var(--muted)]">{formatDate(row.department_response_deadline_at, '-')}</p>
                                    </div>
                                ),
                            },
                            { key: 'status', header: t('common.status'), cell: (row) => <StatusBadge value={row.status} /> },
                        ]}
                        actions={(row) => {
                            const items = [{ label: t('common.view'), href: route('complaints.show', row.id) }];

                            if (row.can?.update) {
                                items.push({ label: t('common.edit'), href: route('complaints.edit', row.id) });
                            }

                            if (row.can?.respond_department) {
                                items.push({ label: t('complaints.actions.respond'), href: `${route('complaints.show', row.id)}#department-response` });
                            }

                            if (row.can?.forward_to_committee) {
                                items.push({ label: t('complaints.actions.forward'), href: `${route('complaints.show', row.id)}#complainant-actions` });
                            }

                            if (row.can?.review_committee) {
                                items.push({ label: t('complaints.actions.committee_review'), href: `${route('complaints.show', row.id)}#committee-review` });
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
