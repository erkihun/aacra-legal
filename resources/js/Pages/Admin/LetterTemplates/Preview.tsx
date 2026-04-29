import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import { buildTemplateRenderable, defaultPreviewData, LetterSheet, LetterTemplateItem, PreviewData } from './shared';

type Props = {
    templateItem: LetterTemplateItem;
    previewData?: PreviewData;
};

export default function LetterTemplatePreview({ templateItem, previewData = defaultPreviewData }: Props) {
    const { t } = useI18n();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.letter_templates'), href: route('letter-templates.index') },
                { label: templateItem.name, href: route('letter-templates.show', templateItem.id) },
                { label: t('letter_templates.actions.preview') },
            ]}
        >
            <Head title={`${templateItem.name} ${t('letter_templates.actions.preview')}`} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('letter_templates.eyebrow')}
                    title={t('letter_templates.preview_title')}
                    description={templateItem.name}
                    action={
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('letter-templates.show', templateItem.id)} />
                            <Link href={route('letter-templates.print', templateItem.id)} className="btn-base btn-primary focus-ring">
                                {t('letter_templates.actions.print')}
                            </Link>
                        </div>
                    }
                />

                <SurfaceCard className="overflow-hidden">
                    <div className="overflow-x-auto rounded-3xl bg-slate-100 p-4 dark:bg-slate-900">
                        <LetterSheet
                            document={buildTemplateRenderable(templateItem, previewData)}
                            labels={{
                                subject: t('letter_templates.preview.subject'),
                                cc: t('letter_templates.preview.cc'),
                                enclosure: t('letter_templates.preview.enclosure'),
                                reference: t('letter_templates.preview.reference'),
                                date: t('letter_templates.preview.date'),
                            }}
                        />
                    </div>
                </SurfaceCard>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
