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
import { useState } from 'react';

type LawsuitRequestListProps = {
    filters: {
        search?: string;
        status?: string;
    };
    requests: {
        data: Array<{
            id: string;
            request_code: string;
            subject: string;
            status: string;
            can_update?: boolean;
            can_delete?: boolean;
            date_submitted?: string | null;
            requesting_department?: {
                name_en: string;
                name_am?: string | null;
            };
        }>;
    };
    can: {
        create: boolean;
    };
    statusOptions: Array<{ label: string; value: string }>;
};

export default function LawsuitRequestIndex({
    filters,
    requests,
    can,
    statusOptions,
}: LawsuitRequestListProps) {
    const { t, locale } = useI18n();
    const [isFiltering, setIsFiltering] = useState(false);
    const [requestToDelete, setRequestToDelete] = useState<LawsuitRequestListProps['requests']['data'][number] | null>(null);
    const { data, setData } = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
    });
    const deleteForm = useForm({});

    const submitFilters = () => {
        setIsFiltering(true);
        router.get(route('lawsuit-requests.index'), data, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.lawsuit_requests') },
            ]}
        >
            <Head title={t('navigation.lawsuit_requests')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('lawsuit_requests.eyebrow')}
                    title={t('lawsuit_requests.index_title')}
                    description={t('lawsuit_requests.index_description')}
                    action={can.create ? (
                        <Link href={route('lawsuit-requests.create')} className="btn-base btn-primary focus-ring">
                            {t('lawsuit_requests.new_request')}
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
                                    router.get(route('lawsuit-requests.index'));
                                }}
                                className="btn-base btn-secondary focus-ring"
                            >
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                onClick={submitFilters}
                                className="btn-base btn-primary focus-ring"
                                disabled={isFiltering}
                            >
                                {isFiltering ? `${t('common.apply_filters')}...` : t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <input
                        value={data.search}
                        onChange={(e) => setData('search', e.target.value)}
                        placeholder={t('lawsuit_requests.search_placeholder')}
                        className="input-ui"
                    />
                    <select
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
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
                        {requests.data.length} {t('common.records')}
                    </div>
                </FiltersToolbar>

                <DataTable
                    rows={requests.data}
                    rowKey={(row) => row.id}
                    emptyTitle={t('lawsuit_requests.empty_title')}
                    emptyDescription={t('lawsuit_requests.empty_description')}
                    columns={[
                        {
                            key: 'reference',
                            header: t('reports.reference'),
                            cell: (row) => (
                                <div>
                                    <p className="font-semibold text-[color:var(--text)]">{row.request_code}</p>
                                    <p className="mt-1 text-xs uppercase text-[color:var(--muted)]">
                                        {row.date_submitted}
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
                                        {(locale === 'am' ? row.requesting_department?.name_am : row.requesting_department?.name_en) ?? row.requesting_department?.name_en}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            key: 'status',
                            header: t('reports.status'),
                            cell: (row) => <StatusBadge value={row.status} />,
                        },
                        {
                            key: 'actions',
                            header: t('common.actions'),
                            className: 'w-56',
                            cell: (row) => (
                                <div className="flex flex-wrap gap-2">
                                    <Link
                                        href={route('lawsuit-requests.show', { lawsuitFilingRequest: row.id })}
                                        className="btn-base btn-secondary focus-ring"
                                    >
                                        {t('common.view')}
                                    </Link>
                                    {row.can_update ? (
                                        <Link
                                            href={route('lawsuit-requests.edit', { lawsuitFilingRequest: row.id })}
                                            className="btn-base btn-secondary focus-ring"
                                        >
                                            {t('common.edit')}
                                        </Link>
                                    ) : null}
                                    {row.can_delete ? (
                                        <button
                                            type="button"
                                            onClick={() => setRequestToDelete(row)}
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
                open={requestToDelete !== null}
                title={t('lawsuit_requests.delete_title')}
                description={t('lawsuit_requests.delete_description')}
                confirmLabel={t('common.delete')}
                onCancel={() => setRequestToDelete(null)}
                onConfirm={() => {
                    if (!requestToDelete) return;

                    deleteForm.delete(route('lawsuit-requests.destroy', { lawsuitFilingRequest: requestToDelete.id }), {
                        preserveScroll: true,
                        onSuccess: () => {
                            finishSuccessfulSubmission(deleteForm, {
                                afterSuccess: () => setRequestToDelete(null),
                            });
                        },
                    });
                }}
                processing={deleteForm.processing}
            />
        </AuthenticatedLayout>
    );
}
