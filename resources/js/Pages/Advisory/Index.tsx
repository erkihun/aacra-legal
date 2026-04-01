import DataTable from '@/Components/Ui/DataTable';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type AdvisoryListProps = {
    filters: {
        search?: string;
        status?: string;
        request_type?: string;
    };
    requests: {
        data: Array<{
            id: string;
            request_number: string;
            subject: string;
            request_type: string;
            status: string;
            priority: string;
            can_update?: boolean;
            due_date?: string | null;
            department?: {
                name_en: string;
                name_am?: string | null;
            };
            assigned_legal_expert?: {
                name: string;
            } | null;
        }>;
    };
    can: {
        create: boolean;
    };
    statusOptions: Array<{ label: string; value: string }>;
    typeOptions: Array<{ label: string; value: string }>;
};

export default function AdvisoryIndex({
    filters,
    requests,
    can,
    statusOptions,
    typeOptions,
}: AdvisoryListProps) {
    const { t, locale } = useI18n();
    const [isFiltering, setIsFiltering] = useState(false);
    const { data, setData } = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
        request_type: filters.request_type ?? '',
    });

    const submitFilters = () => {
        setIsFiltering(true);

        router.get(route('advisory.index'), data, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.advisory_requests') },
            ]}
        >
            <Head title={t('navigation.advisory_requests')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('advisory.eyebrow')}
                    title={t('advisory.index_title')}
                    description={t('advisory.index_description')}
                    action={can.create ? (
                        <Link href={route('advisory.create')} className="btn-base btn-primary focus-ring">
                            {t('advisory.new_request')}
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
                                    setData({
                                        search: '',
                                        status: '',
                                        request_type: '',
                                    });
                                    router.get(route('advisory.index'));
                                }}
                                className="btn-base btn-secondary focus-ring"
                            >
                                {t('common.reset')}
                            </button>
                            <button type="button" onClick={submitFilters} className="btn-base btn-primary focus-ring" disabled={isFiltering}>
                                {isFiltering ? `${t('common.apply_filters')}...` : t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <input
                        value={data.search}
                        onChange={(event) => setData('search', event.target.value)}
                        placeholder={t('advisory.search_placeholder')}
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
                    <select
                        value={data.request_type}
                        onChange={(event) => setData('request_type', event.target.value)}
                        className="select-ui"
                    >
                        <option value="">{t('common.all_types')}</option>
                        {typeOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <div className="surface-muted flex items-center px-4 text-sm text-[color:var(--muted)]">
                        {requests.data.length} {t('common.records')}
                    </div>
                </FiltersToolbar>

                <DataTable
                    rows={requests.data}
                    rowKey={(row) => row.id}
                    emptyTitle={t('advisory.empty_title')}
                    emptyDescription={t('advisory.empty_description')}
                    actions={(row) => [
                        { label: t('common.view'), href: route('advisory.show', { advisoryRequest: row.id }) },
                        ...(row.can_update
                            ? [{ label: t('common.edit'), href: route('advisory.edit', { advisoryRequest: row.id }) }]
                            : []),
                    ]}
                    columns={[
                        {
                            key: 'reference',
                            header: t('reports.reference'),
                            cell: (row) => (
                                <div>
                                    <p className="font-semibold text-[color:var(--text)]">{row.request_number}</p>
                                    <p className="mt-1 text-xs uppercase text-[color:var(--muted)]">
                                        {row.request_type}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            key: 'subject',
                            header: t('reports.subject'),
                            cell: (row) => (
                                <div>
                                    <p className="font-medium text-[color:var(--text)]">{row.subject}</p>
                                    <p className="mt-1 text-sm text-[color:var(--muted)]">
                                        {(locale === 'am' ? row.department?.name_am : row.department?.name_en) ?? row.department?.name_en}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            key: 'expert',
                            header: t('advisory.expert'),
                            cell: (row) => row.assigned_legal_expert?.name ?? t('common.unassigned'),
                        },
                        {
                            key: 'priority',
                            header: t('advisory.priority'),
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
