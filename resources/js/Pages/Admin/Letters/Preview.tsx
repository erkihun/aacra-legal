import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import { buildLetterRenderable, LetterItem, LetterSheet } from '../LetterTemplates/shared';

type Props = {
    letterItem: LetterItem;
};

export default function LetterPreview({ letterItem }: Props) {
    const { t } = useI18n();

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
                            <Link href={route('letters.print', letterItem.id)} className="btn-base btn-primary focus-ring">
                                {t('letters.actions.print')}
                            </Link>
                        </div>
                    }
                />

                <SurfaceCard className="overflow-hidden">
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
