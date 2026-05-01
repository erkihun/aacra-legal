import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import { buildLetterRenderable, LetterItem, LetterSheet } from '../LetterTemplates/shared';

type Props = {
    letterItem: LetterItem;
    can: {
        update: boolean;
        delete: boolean;
        preview: boolean;
        print: boolean;
        download: boolean;
    };
};

export default function LetterShow({ letterItem, can }: Props) {
    const { t } = useI18n();
    const { formatDateTime, formatDate } = useDateFormatter();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.letters'), href: route('letters.index') },
                { label: letterItem.reference_number ?? t('letters.detail_title') },
            ]}
        >
            <Head title={letterItem.reference_number ?? t('letters.detail_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('letters.eyebrow')}
                    title={letterItem.reference_number ?? t('letters.detail_title')}
                    description={letterItem.subject ?? letterItem.template?.name ?? ''}
                    action={
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('letters.index')} />
                            {can.preview ? (
                                <Link href={route('letters.preview', letterItem.id)} className="btn-base btn-secondary focus-ring">
                                    {t('letters.actions.preview_pdf')}
                                </Link>
                            ) : null}
                            {can.download ? (
                                <a href={route('letters.download-pdf', letterItem.id)} className="btn-base btn-secondary focus-ring">
                                    {t('letters.actions.download_pdf')}
                                </a>
                            ) : null}
                            {can.print ? (
                                <a href={route('letters.print', letterItem.id)} target="_blank" rel="noreferrer" className="btn-base btn-secondary focus-ring">
                                    {t('letters.actions.print_pdf')}
                                </a>
                            ) : null}
                            {can.update ? (
                                <Link href={route('letters.edit', letterItem.id)} className="btn-base btn-primary focus-ring">
                                    {t('common.edit')}
                                </Link>
                            ) : null}
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={letterItem.status ?? 'draft'} label={letterItem.status ? t(`letters.status.${letterItem.status}`) : undefined} />
                </div>

                <div className="grid gap-4 xl:grid-cols-[0.92fr,1.08fr]">
                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.overview')}</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Detail label={t('letters.fields.reference_number')} value={letterItem.reference_number} />
                            <Detail label={t('letters.fields.letter_date')} value={formatDate(letterItem.letter_date, '-')} />
                            <Detail label={t('letters.fields.template')} value={letterItem.template?.name} />
                            <Detail label={t('letters.fields.recipient_name')} value={letterItem.recipient_name} />
                            <Detail label={t('letters.fields.recipient_title')} value={letterItem.recipient_title} />
                            <Detail label={t('letters.fields.recipient_organization')} value={letterItem.recipient_organization} />
                            <Detail label={t('letters.fields.signer_full_name')} value={letterItem.signer_full_name} />
                            <Detail label={t('letters.fields.signer_title')} value={letterItem.signer_title} />
                            <Detail label={t('letters.fields.created_by')} value={letterItem.creator?.name} />
                            <Detail label={t('letters.fields.updated_by')} value={letterItem.updater?.name} />
                            <Detail label={t('letters.fields.updated_at')} value={formatDateTime(letterItem.updated_at, '-')} />
                            <Detail label={t('common.notes')} value={letterItem.notes} className="md:col-span-2" />
                        </div>
                    </SurfaceCard>

                    <SurfaceCard className="space-y-4">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.fields.template_assets')}</h2>
                        <AssetPreview title={t('letter_templates.fields.header_image')} imageUrl={letterItem.header_image_url ?? null} emptyLabel={t('letters.empty_header_image')} />
                        <AssetPreview title={t('letter_templates.fields.footer_image')} imageUrl={letterItem.footer_image_url ?? null} emptyLabel={t('letters.empty_footer_image')} />
                        <AssetPreview title={t('letters.fields.signature_image')} imageUrl={letterItem.signature_image_url ?? null} emptyLabel={t('letters.empty_signature_image')} />
                    </SurfaceCard>
                </div>

                <SurfaceCard className="space-y-4 overflow-hidden">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.sections.preview')}</h2>
                        {can.print ? (
                            <a href={route('letters.print', letterItem.id)} target="_blank" rel="noreferrer" className="btn-base btn-secondary focus-ring">
                                {t('letters.actions.print_pdf')}
                            </a>
                        ) : null}
                    </div>
                    <div className="overflow-x-auto rounded-3xl bg-slate-100 p-4 dark:bg-slate-900">
                        <LetterSheet
                            document={buildLetterRenderable(letterItem)}
                            labels={{
                                subject: t('letters.preview.subject'),
                                cc: t('letters.preview.cc'),
                                enclosure: t('letters.preview.enclosure'),
                                reference: t('letters.preview.reference'),
                                date: t('letters.preview.date'),
                            }}
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
