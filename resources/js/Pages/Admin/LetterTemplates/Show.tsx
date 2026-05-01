import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router } from '@inertiajs/react';
import { buildTemplateRenderable, defaultPreviewData, LetterSheet, LetterTemplateItem, PlaceholderField, previewDocumentLabels, PreviewData } from './shared';

type Props = {
    templateItem: LetterTemplateItem;
    previewData?: PreviewData;
    placeholderFields: PlaceholderField[];
    can: {
        update: boolean;
        delete: boolean;
        preview: boolean;
        print: boolean;
        duplicate: boolean;
        create_letter: boolean;
    };
};

export default function LetterTemplateShow({ templateItem, previewData = defaultPreviewData, placeholderFields, can }: Props) {
    const { t } = useI18n();
    const { formatDateTime } = useDateFormatter();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.letter_templates'), href: route('letter-templates.index') },
                { label: templateItem.name },
            ]}
        >
            <Head title={templateItem.name} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('letter_templates.eyebrow')}
                    title={templateItem.name}
                    description={templateItem.code}
                    action={
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('letter-templates.index')} />
                            {can.create_letter ? (
                                <Link href={route('letters.create', { template_id: templateItem.id })} className="btn-base btn-secondary focus-ring">
                                    {t('letters.new_letter')}
                                </Link>
                            ) : null}
                            {can.preview ? (
                                <Link href={route('letter-templates.preview', templateItem.id)} className="btn-base btn-secondary focus-ring">
                                    {t('letter_templates.actions.preview')}
                                </Link>
                            ) : null}
                            {can.print ? (
                                <Link href={route('letter-templates.print', templateItem.id)} className="btn-base btn-secondary focus-ring">
                                    {t('letter_templates.actions.print')}
                                </Link>
                            ) : null}
                            {can.duplicate ? (
                                <button type="button" className="btn-base btn-secondary focus-ring" onClick={() => router.post(route('letter-templates.duplicate', templateItem.id))}>
                                    {t('letter_templates.actions.duplicate')}
                                </button>
                            ) : null}
                            {can.update ? (
                                <Link href={route('letter-templates.edit', templateItem.id)} className="btn-base btn-primary focus-ring">
                                    {t('common.edit')}
                                </Link>
                            ) : null}
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={templateItem.is_active ? 'active' : 'inactive'} />
                    {templateItem.is_default ? (
                        <span className="inline-flex rounded-full bg-emerald-500/12 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-500/18 dark:text-emerald-300">
                            {t('letter_templates.default_template')}
                        </span>
                    ) : null}
                </div>

                <div className="grid gap-4 xl:grid-cols-[0.92fr,1.08fr]">
                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.overview')}</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Detail label={t('common.code')} value={templateItem.code} />
                            <Detail label={t('letter_templates.fields.document_type')} value={templateItem.document_type} />
                            <Detail label={t('common.language')} value={t(`letter_templates.languages.${templateItem.language}`)} />
                            <Detail label={t('letter_templates.fields.page_size')} value={`${templateItem.page_size} / ${t(`letter_templates.orientation.${templateItem.orientation}`)}`} />
                            <Detail label={t('letter_templates.fields.reference_prefix')} value={templateItem.reference_prefix} />
                            <Detail label={t('letter_templates.fields.reference_start_number')} value={String(templateItem.reference_start_number ?? '')} />
                            <Detail label={t('letter_templates.fields.current_reference_number')} value={String(templateItem.current_reference_number ?? 0)} />
                            <Detail label={t('letter_templates.fields.next_reference_number_preview')} value={templateItem.next_reference_number_preview} />
                            <Detail label={t('letter_templates.fields.templates_in_use')} value={String(templateItem.letters_count ?? 0)} />
                            <Detail label={t('letter_templates.fields.updated_at')} value={formatDateTime(templateItem.updated_at, '-')} />
                            <Detail label={t('letter_templates.fields.created_by')} value={templateItem.creator?.name} />
                            <Detail label={t('letter_templates.fields.updated_by')} value={templateItem.updater?.name} />
                            <Detail label={t('common.notes')} value={templateItem.notes} className="md:col-span-2" />
                        </div>
                    </SurfaceCard>

                    <SurfaceCard className="space-y-4">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.header_footer_assets')}</h2>
                        <div className="grid gap-4">
                            <AssetPreview title={t('letter_templates.fields.header_image')} imageUrl={templateItem.header_image_url ?? null} emptyLabel={t('letter_templates.empty_header_image')} />
                            <AssetPreview title={t('letter_templates.fields.footer_image')} imageUrl={templateItem.footer_image_url ?? null} emptyLabel={t('letter_templates.empty_footer_image')} />
                        </div>
                    </SurfaceCard>
                </div>

                <SurfaceCard>
                    <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.placeholders')}</h2>
                    <div className="mt-4 grid gap-3">
                        {placeholderFields.map((field) => (
                            <div key={field.token} className="surface-muted flex items-center justify-between gap-3 px-4 py-3">
                                <code className="text-sm font-semibold text-[color:var(--primary)]">{field.token}</code>
                                <span className="text-sm text-[color:var(--muted-strong)]">{field.description}</span>
                            </div>
                        ))}
                    </div>
                </SurfaceCard>

                <SurfaceCard className="space-y-4 overflow-hidden">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.preview')}</h2>
                        {can.print ? (
                            <Link href={route('letter-templates.print', templateItem.id)} className="btn-base btn-secondary focus-ring">
                                {t('letter_templates.actions.print')}
                            </Link>
                        ) : null}
                    </div>
                    <div className="overflow-x-auto rounded-3xl bg-slate-100 p-4 dark:bg-slate-900">
                        <LetterSheet
                            document={buildTemplateRenderable(templateItem, previewData)}
                            labels={previewDocumentLabels(templateItem.language)}
                        />
                    </div>
                </SurfaceCard>
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function Detail({ label, value, className }: { label: string; value?: string | null; className?: string }) {
    const { t } = useI18n();

    return (
        <div className={`surface-muted px-4 py-4 ${className ?? ''}`}>
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-sm font-semibold text-[color:var(--text)]">{value ?? t('common.not_available')}</p>
        </div>
    );
}

function AssetPreview({ title, imageUrl, emptyLabel }: { title: string; imageUrl: string | null; emptyLabel: string }) {
    return (
        <div className="surface-muted space-y-3 p-4">
            <p className="text-sm font-semibold text-[color:var(--text)]">{title}</p>
            {imageUrl ? (
                <img src={imageUrl} alt={title} className="max-h-44 w-full rounded-2xl border border-[color:var(--border)] object-contain bg-white p-3" />
            ) : (
                <p className="text-sm text-[color:var(--muted)]">{emptyLabel}</p>
            )}
        </div>
    );
}
