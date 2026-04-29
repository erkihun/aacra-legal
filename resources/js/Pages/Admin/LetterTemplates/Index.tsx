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

type LetterTemplateRow = {
    row_number: number;
    id: string;
    name: string;
    code: string;
    document_type?: string | null;
    language: 'en' | 'am';
    page_size: string;
    orientation: 'portrait' | 'landscape';
    reference_prefix?: string | null;
    is_active: boolean;
    is_default: boolean;
    letters_count?: number;
    updated_at?: string | null;
    can: {
        update: boolean;
        delete: boolean;
        preview: boolean;
        print: boolean;
        duplicate: boolean;
    };
};

export default function LetterTemplatesIndex({ filters, templates, can }: any) {
    const { t } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [isFiltering, setIsFiltering] = useState(false);
    const [pendingDelete, setPendingDelete] = useState<LetterTemplateRow | null>(null);
    const [processingDelete, setProcessingDelete] = useState(false);
    const form = useForm({
        search: filters.search ?? '',
        language: filters.language ?? '',
        is_active: filters.is_active ?? '',
    });
    const templateRows: LetterTemplateRow[] = Array.isArray(templates.data)
        ? templates.data.map((row: Omit<LetterTemplateRow, 'row_number'>, index: number) => ({
              ...row,
              row_number: ((templates.current_page ?? 1) - 1) * (templates.per_page ?? templates.data.length ?? 0) + index + 1,
          }))
        : [];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.letter_templates') },
            ]}
        >
            <Head title={t('letter_templates.index_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('letter_templates.eyebrow')}
                    title={t('letter_templates.index_title')}
                    description={t('letter_templates.index_description')}
                    action={
                        can.create ? (
                            <Link href={route('letter-templates.create')} className="btn-base btn-primary focus-ring">
                                {t('letter_templates.new_template')}
                            </Link>
                        ) : undefined
                    }
                />

                <FiltersToolbar
                    title={t('letter_templates.filters')}
                    actions={
                        <>
                            <button type="button" className="btn-base btn-secondary focus-ring" onClick={() => router.get(route('letter-templates.index'))}>
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                className="btn-base btn-primary focus-ring"
                                disabled={isFiltering}
                                onClick={() => {
                                    setIsFiltering(true);
                                    router.get(route('letter-templates.index'), form.data, {
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
                        placeholder={t('letter_templates.search_placeholder')}
                    />
                    <select value={form.data.language} onChange={(event) => form.setData('language', event.target.value)} className="select-ui">
                        <option value="">{t('common.language')}</option>
                        <option value="en">{t('letter_templates.languages.en')}</option>
                        <option value="am">{t('letter_templates.languages.am')}</option>
                    </select>
                    <select value={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.value)} className="select-ui">
                        <option value="">{t('common.status')}</option>
                        <option value="1">{t('common.active')}</option>
                        <option value="0">{t('common.inactive')}</option>
                    </select>
                </FiltersToolbar>

                {templateRows.length === 0 ? (
                    <EmptyState title={t('letter_templates.empty_title')} description={t('letter_templates.empty_description')} />
                ) : (
                    <>
                        <DataTable<LetterTemplateRow>
                            rows={templateRows}
                            rowKey={(row) => row.id}
                            emptyTitle={t('letter_templates.empty_title')}
                            emptyDescription={t('letter_templates.empty_description')}
                            actions={(row) => [
                                { label: t('common.view'), href: route('letter-templates.show', row.id) },
                                ...(row.can.preview ? [{ label: t('letter_templates.actions.preview'), href: route('letter-templates.preview', row.id) }] : []),
                                ...(row.can.print ? [{ label: t('letter_templates.actions.print'), href: route('letter-templates.print', row.id) }] : []),
                                ...(row.can.update ? [{ label: t('common.edit'), href: route('letter-templates.edit', row.id) }] : []),
                                ...(row.can.duplicate
                                    ? [{
                                        label: t('letter_templates.actions.duplicate'),
                                        onClick: () => router.post(route('letter-templates.duplicate', row.id)),
                                    }]
                                    : []),
                                ...(row.can.delete ? [{ label: t('common.delete'), onClick: () => setPendingDelete(row) }] : []),
                            ]}
                            columns={[
                                { key: 'number', header: '#', cell: (row) => row.row_number, className: 'w-16' },
                                {
                                    key: 'name',
                                    header: t('letter_templates.fields.name'),
                                    cell: (row) => (
                                        <div>
                                            <p className="font-semibold text-[color:var(--text)]">{row.name}</p>
                                            <p className="mt-1 text-sm text-[color:var(--muted)]">{row.code}</p>
                                        </div>
                                    ),
                                },
                                { key: 'code', header: t('common.code'), cell: (row) => row.code },
                                { key: 'language', header: t('common.language'), cell: (row) => t(`letter_templates.languages.${row.language}`) },
                                { key: 'reference_prefix', header: t('letter_templates.fields.reference_prefix'), cell: (row) => row.reference_prefix ?? t('common.not_available') },
                                {
                                    key: 'status',
                                    header: t('common.status'),
                                    cell: (row) => (
                                        <div className="flex flex-wrap items-center gap-2">
                                            <StatusBadge value={row.is_active ? 'active' : 'inactive'} />
                                            {row.is_default ? (
                                                <span className="inline-flex rounded-full bg-emerald-500/12 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-500/18 dark:text-emerald-300">
                                                    {t('letter_templates.default_template')}
                                                </span>
                                            ) : null}
                                        </div>
                                    ),
                                },
                                {
                                    key: 'updated_at',
                                    header: t('letter_templates.fields.updated_at'),
                                    cell: (row) => formatDateTime(row.updated_at, '-'),
                                },
                            ]}
                        />
                        <Pagination
                            links={Array.isArray(templates.links) ? templates.links : []}
                            meta={{
                                current_page: templates.current_page,
                                from: templates.from,
                                last_page: templates.last_page,
                                to: templates.to,
                                total: templates.total,
                            }}
                        />
                    </>
                )}
            </PageContainer>

            <ConfirmationDialog
                open={pendingDelete !== null}
                title={t('letter_templates.delete_title')}
                description={t('letter_templates.delete_confirm')}
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
                    router.delete(route('letter-templates.destroy', pendingDelete.id), {
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
