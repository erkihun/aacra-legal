import DataTable from '@/Components/Ui/DataTable';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import PageContainer from '@/Components/Ui/PageContainer';
import Pagination from '@/Components/Ui/Pagination';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type Option = {
    value: string;
    label: string;
};

type AuditPageProps = {
    filters: {
        search?: string;
        actor_id?: string;
        event?: string;
        subject_type?: string;
    };
    items: {
        data: Array<{
            id: string;
            log_name?: string | null;
            description?: string | null;
            event?: string | null;
            subject_type?: string | null;
            subject_id?: string | null;
            causer?: string | null;
            changes_summary?: string | null;
            created_at?: string | null;
        }>;
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
        };
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    actorOptions: Option[];
    subjectTypeOptions: Option[];
    eventOptions: Option[];
};

export default function AuditLogsIndex({
    filters,
    items,
    actorOptions,
    subjectTypeOptions,
    eventOptions,
}: AuditPageProps) {
    const { t } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [isFiltering, setIsFiltering] = useState(false);
    const { data, setData } = useForm({
        search: filters.search ?? '',
        actor_id: filters.actor_id ?? '',
        event: filters.event ?? '',
        subject_type: filters.subject_type ?? '',
    });

    const applyFilters = () => {
        setIsFiltering(true);

        router.get(route('audit-logs.index'), data, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.audit_logs') },
            ]}
        >
            <Head title={t('navigation.audit_logs')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('audit.eyebrow')}
                    title={t('audit.title')}
                    description={t('audit.description')}
                />

                <FiltersToolbar
                    title={t('audit.search')}
                    actions={
                        <>
                            <button type="button" onClick={() => router.get(route('audit-logs.index'))} className="btn-base btn-secondary focus-ring">
                                {t('common.reset')}
                            </button>
                            <button type="button" onClick={applyFilters} className="btn-base btn-primary focus-ring" disabled={isFiltering}>
                                {isFiltering ? `${t('common.apply_filters')}...` : t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <FilterInput label={t('audit.search')} value={data.search} onChange={(value) => setData('search', value)} />
                    <FilterSelect label={t('audit.actor')} value={data.actor_id} options={actorOptions} onChange={(value) => setData('actor_id', value)} />
                    <FilterSelect label={t('audit.event')} value={data.event} options={eventOptions} onChange={(value) => setData('event', value)} />
                    <FilterSelect label={t('audit.subject')} value={data.subject_type} options={subjectTypeOptions} onChange={(value) => setData('subject_type', value)} />
                </FiltersToolbar>

                <SurfaceCard>
                    <div className="mb-5 flex items-center justify-between gap-3">
                        <h2 className="text-xl font-semibold text-[color:var(--text)]">{t('audit.timeline')}</h2>
                        <p className="text-sm text-[color:var(--muted)]">
                            {items.meta.total} {t('audit.entries')}
                        </p>
                    </div>

                    <DataTable
                        rows={items.data}
                        rowKey={(row) => row.id}
                        emptyTitle={t('audit.timeline')}
                        emptyDescription={t('audit.empty')}
                        columns={[
                            {
                                key: 'event',
                                header: t('audit.event'),
                                cell: (row) => (
                                    <div>
                                        <p className="text-xs uppercase text-[color:var(--primary)]">
                                            {row.event ?? t('audit.no_event')}
                                        </p>
                                        <p className="mt-2 font-semibold text-[color:var(--text)]">
                                            {row.description ?? t('audit.no_description')}
                                        </p>
                                    </div>
                                ),
                            },
                            {
                                key: 'subject',
                                header: t('audit.subject'),
                                cell: (row) => (
                                    <div>
                                        <p className="text-[color:var(--text)]">{row.subject_type ?? t('audit.no_subject')}</p>
                                        <p className="mt-1 text-sm text-[color:var(--muted)]">{row.causer ?? t('audit.system_actor')}</p>
                                    </div>
                                ),
                            },
                            {
                                key: 'created_at',
                                header: t('reports.completed_at'),
                                cell: (row) => formatDateTime(row.created_at, t('common.not_available')),
                            },
                            {
                                key: 'changes_summary',
                                header: t('audit.changes'),
                                cell: (row) => row.changes_summary ?? t('audit.no_changes'),
                            },
                        ]}
                    />
                </SurfaceCard>

                <Pagination links={items.links} />
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function FilterInput({
    label,
    value,
    onChange,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <label className="block space-y-2">
            <span className="text-sm font-medium text-[color:var(--text)]">{label}</span>
            <input value={value} onChange={(event) => onChange(event.target.value)} className="input-ui" />
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
