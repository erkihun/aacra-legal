import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import DataTable from '@/Components/Ui/DataTable';
import EmptyState from '@/Components/Ui/EmptyState';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import PageContainer from '@/Components/Ui/PageContainer';
import Pagination from '@/Components/Ui/Pagination';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type LetterRow = {
    id: string;
    reference_number: string;
    subject?: string | null;
    recipient_name: string;
    template_name?: string | null;
    template_id?: string | null;
    letter_date?: string | null;
    status?: string | null;
    can: {
        update: boolean;
        delete: boolean;
        preview: boolean;
        print: boolean;
    };
};

export default function LettersIndex({ filters, letters, templates, can }: any) {
    const { t } = useI18n();
    const { formatDate } = useDateFormatter();
    const [isFiltering, setIsFiltering] = useState(false);
    const [pendingDelete, setPendingDelete] = useState<LetterRow | null>(null);
    const [processingDelete, setProcessingDelete] = useState(false);
    const form = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
        template_id: filters.template_id ?? '',
    });
    const rows: (LetterRow & { row_number: number })[] = Array.isArray(letters.data)
        ? letters.data.map((row: LetterRow, index: number) => ({
              ...row,
              row_number: ((letters.current_page ?? 1) - 1) * (letters.per_page ?? letters.data.length ?? 0) + index + 1,
          }))
        : [];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.letters') },
            ]}
        >
            <Head title={t('letters.index_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('letters.eyebrow')}
                    title={t('letters.index_title')}
                    description={t('letters.index_description')}
                    action={
                        can.create ? (
                            <Link href={route('letters.create')} className="btn-base btn-primary focus-ring">
                                {t('letters.new_letter')}
                            </Link>
                        ) : undefined
                    }
                />

                <FiltersToolbar
                    title={t('letters.filters')}
                    actions={
                        <>
                            <button type="button" className="btn-base btn-secondary focus-ring" onClick={() => router.get(route('letters.index'))}>
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                className="btn-base btn-primary focus-ring"
                                disabled={isFiltering}
                                onClick={() => {
                                    setIsFiltering(true);
                                    router.get(route('letters.index'), form.data, {
                                        preserveState: true,
                                        replace: true,
                                        onFinish: () => setIsFiltering(false),
                                    });
                                }}
                            >
                                {t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <input
                        value={form.data.search}
                        onChange={(event) => form.setData('search', event.target.value)}
                        className="input-ui"
                        placeholder={t('letters.search_placeholder')}
                    />
                    <select value={form.data.status} onChange={(event) => form.setData('status', event.target.value)} className="select-ui">
                        <option value="">{t('common.status')}</option>
                        <option value="draft">{t('letters.status.draft')}</option>
                        <option value="final">{t('letters.status.final')}</option>
                        <option value="archived">{t('letters.status.archived')}</option>
                    </select>
                    <select value={form.data.template_id} onChange={(event) => form.setData('template_id', event.target.value)} className="select-ui">
                        <option value="">{t('letters.fields.template')}</option>
                        {(Array.isArray(templates) ? templates : []).map((template: any) => (
                            <option key={template.id} value={template.id}>
                                {template.name}
                            </option>
                        ))}
                    </select>
                </FiltersToolbar>

                {rows.length === 0 ? (
                    <EmptyState title={t('letters.empty_title')} description={t('letters.empty_description')} />
                ) : (
                    <>
                        <DataTable
                            rows={rows}
                            rowKey={(row: LetterRow & { row_number: number }) => row.id}
                            emptyTitle={t('letters.empty_title')}
                            emptyDescription={t('letters.empty_description')}
                            actions={(row: LetterRow) => [
                                { label: t('common.view'), href: route('letters.show', row.id) },
                                ...(row.can.preview ? [{ label: t('letters.actions.preview'), href: route('letters.preview', row.id) }] : []),
                                ...(row.can.print ? [{ label: t('letters.actions.print'), href: route('letters.print', row.id) }] : []),
                                ...(row.can.update ? [{ label: t('common.edit'), href: route('letters.edit', row.id) }] : []),
                                ...(row.can.delete ? [{ label: t('common.delete'), onClick: () => setPendingDelete(row) }] : []),
                            ]}
                            columns={[
                                { key: 'number', header: '#', cell: (row: LetterRow & { row_number: number }) => row.row_number, className: 'w-16' },
                                { key: 'reference_number', header: t('letters.fields.reference_number'), cell: (row: LetterRow) => row.reference_number },
                                {
                                    key: 'subject',
                                    header: t('letters.fields.subject'),
                                    cell: (row: LetterRow) => (
                                        <div>
                                            <p className="font-semibold text-[color:var(--text)]">{row.subject ?? t('common.not_available')}</p>
                                            <p className="mt-1 text-sm text-[color:var(--muted)]">{row.template_name ?? '-'}</p>
                                        </div>
                                    ),
                                },
                                { key: 'recipient', header: t('letters.fields.recipient_name'), cell: (row: LetterRow) => row.recipient_name },
                                { key: 'date', header: t('letters.fields.letter_date'), cell: (row: LetterRow) => formatDate(row.letter_date, '-') },
                                { key: 'status', header: t('common.status'), cell: (row: LetterRow) => <StatusBadge value={row.status ?? 'draft'} label={row.status ? t(`letters.status.${row.status}`) : undefined} /> },
                            ]}
                        />
                        <Pagination
                            links={Array.isArray(letters.links) ? letters.links : []}
                            meta={{
                                current_page: letters.current_page,
                                from: letters.from,
                                last_page: letters.last_page,
                                to: letters.to,
                                total: letters.total,
                            }}
                        />
                    </>
                )}
            </PageContainer>

            <ConfirmationDialog
                open={pendingDelete !== null}
                title={t('letters.delete_title')}
                description={t('letters.delete_confirm')}
                confirmLabel={t('common.delete')}
                processing={processingDelete}
                onCancel={() => {
                    if (!processingDelete) {
                        setPendingDelete(null);
                    }
                }}
                onConfirm={() => {
                    if (!pendingDelete) {
                        return;
                    }

                    setProcessingDelete(true);
                    router.delete(route('letters.destroy', pendingDelete.id), {
                        preserveScroll: true,
                        onFinish: () => {
                            setProcessingDelete(false);
                            setPendingDelete(null);
                        },
                    });
                }}
            />
        </AuthenticatedLayout>
    );
}
