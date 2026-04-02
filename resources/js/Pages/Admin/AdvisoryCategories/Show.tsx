import BackButton from '@/Components/Ui/BackButton';
import MetricCard from '@/Components/Ui/MetricCard';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';

export default function AdvisoryCategoryShow({ categoryItem, can }: any) {
    const { t, locale } = useI18n();
    const categoryName = locale === 'am' ? categoryItem.name_am : categoryItem.name_en;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.advisory_categories'), href: route('advisory-categories.index') },
                { label: categoryName },
            ]}
        >
            <Head title={categoryName} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('advisory_categories.eyebrow')}
                    title={categoryName}
                    description={categoryItem.code}
                    action={
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('advisory-categories.index')} />
                            {can.update ? (
                                <Link href={route('advisory-categories.edit', categoryItem.id)} className="btn-base btn-primary focus-ring">
                                    {t('common.edit')}
                                </Link>
                            ) : null}
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={categoryItem.is_active ? 'active' : 'inactive'} />
                </div>

                <div className="grid gap-4 xl:grid-cols-[1.15fr,0.85fr]">
                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.overview')}</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Detail label={t('common.code')} value={categoryItem.code} />
                            <Detail label={t('common.status')} value={categoryItem.is_active ? t('common.active') : t('common.inactive')} />
                            <Detail label={t('advisory_categories.name_en')} value={categoryItem.name_en} />
                            <Detail label={t('advisory_categories.name_am')} value={categoryItem.name_am} />
                            <Detail label={t('common.description')} value={categoryItem.description} />
                        </div>
                    </SurfaceCard>

                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.related_records')}</h2>
                        <div className="mt-4">
                            <MetricCard label={t('navigation.advisory_requests')} value={categoryItem.stats.advisory_requests} />
                        </div>
                    </SurfaceCard>
                </div>
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    const { t } = useI18n();

    return (
        <div className="surface-muted px-4 py-4">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-sm font-semibold text-[color:var(--text)]">{value ?? t('common.not_available')}</p>
        </div>
    );
}
