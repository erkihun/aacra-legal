import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import { LetterItem } from '../LetterTemplates/shared';

type Props = {
    letterItem: LetterItem;
    pdfUrl: string;
    downloadUrl: string;
    printUrl?: string | null;
    canPrint: boolean;
};

export default function LetterPreview({ letterItem, pdfUrl, downloadUrl, printUrl, canPrint }: Props) {
    const { t } = useI18n();
    const viewerUrl = `${pdfUrl}#toolbar=0&navpanes=0&view=FitH`;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.letters'), href: route('letters.index') },
                { label: letterItem.reference_number ?? t('letters.detail_title'), href: route('letters.show', letterItem.id) },
                { label: t('letters.actions.preview') },
            ]}
        >
            <Head title={`${letterItem.reference_number ?? t('letters.detail_title')} ${t('letters.actions.preview')}`} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('letters.eyebrow')}
                    title={t('letters.preview_title')}
                    description={letterItem.reference_number ?? letterItem.subject ?? ''}
                    action={
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('letters.show', letterItem.id)} />
                            <a href={downloadUrl} className="btn-base btn-secondary focus-ring">
                                {t('letters.actions.download_pdf')}
                            </a>
                            {canPrint && printUrl ? (
                                <a href={printUrl} target="_blank" rel="noreferrer" className="btn-base btn-primary focus-ring">
                                    {t('letters.actions.print_pdf')}
                                </a>
                            ) : null}
                        </div>
                    }
                />

                <SurfaceCard className="space-y-4 overflow-hidden">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.viewer.title')}</h2>
                            <p className="mt-1 text-sm text-[color:var(--muted)]">{t('letters.viewer.description')}</p>
                        </div>
                        <a href={pdfUrl} target="_blank" rel="noreferrer" className="btn-base btn-secondary focus-ring">
                            {t('letters.actions.open_pdf')}
                        </a>
                    </div>

                    <div className="rounded-3xl border border-[color:var(--border)] bg-slate-100 p-2 dark:bg-slate-900">
                        <iframe
                            src={viewerUrl}
                            title={letterItem.reference_number ?? t('letters.preview_title')}
                            className="h-[78vh] min-h-[640px] w-full rounded-[1.35rem] bg-white"
                        />
                    </div>

                    <p className="text-sm text-[color:var(--muted)]">
                        {t('letters.viewer.fallback')}
                    </p>
                </SurfaceCard>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
