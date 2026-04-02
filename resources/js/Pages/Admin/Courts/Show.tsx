import BackButton from '@/Components/Ui/BackButton';
import MetricCard from '@/Components/Ui/MetricCard';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';

export default function CourtShow({ courtItem, can }: any) {
    const { t, locale } = useI18n();
    const courtName = locale === 'am' ? courtItem.name_am : courtItem.name_en;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.courts'), href: route('courts.index') },
                { label: courtName },
            ]}
        >
            <Head title={courtName} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('courts.eyebrow')}
                    title={courtName}
                    description={courtItem.code}
                    action={
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('courts.index')} />
                            {can.update ? (
                                <Link href={route('courts.edit', courtItem.id)} className="btn-base btn-primary focus-ring">
                                    {t('common.edit')}
                                </Link>
                            ) : null}
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={courtItem.is_active ? 'active' : 'inactive'} />
                </div>

                <div className="grid gap-4 xl:grid-cols-[1.15fr,0.85fr]">
                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.overview')}</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Detail label={t('common.code')} value={courtItem.code} />
                            <Detail label={t('courts.level')} value={courtItem.level} />
                            <Detail label={t('courts.name_en')} value={courtItem.name_en} />
                            <Detail label={t('courts.name_am')} value={courtItem.name_am} />
                            <Detail label={t('courts.city')} value={courtItem.city} />
                            <Detail label={t('common.status')} value={courtItem.is_active ? t('common.active') : t('common.inactive')} />
                        </div>
                    </SurfaceCard>

                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.related_records')}</h2>
                        <div className="mt-4">
                            <MetricCard label={t('navigation.legal_cases')} value={courtItem.stats.legal_cases} />
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
