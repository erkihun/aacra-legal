import MetricCard from '@/Components/Ui/MetricCard';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';

export default function TeamShow({ teamItem, can }: any) {
    const { t, locale } = useI18n();
    const teamName = locale === 'am' ? teamItem.name_am : teamItem.name_en;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.teams'), href: route('teams.index') },
                { label: teamName },
            ]}
        >
            <Head title={teamName} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('teams.eyebrow')}
                    title={teamName}
                    description={teamItem.code}
                    action={
                        can.update ? (
                            <Link href={route('teams.edit', teamItem.id)} className="btn-base btn-primary focus-ring">
                                {t('common.edit')}
                            </Link>
                        ) : undefined
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={teamItem.type} />
                    <StatusBadge value={teamItem.is_active ? 'active' : 'inactive'} />
                </div>

                <div className="grid gap-4 xl:grid-cols-[1.15fr,0.85fr]">
                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.overview')}</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Detail label={t('common.code')} value={teamItem.code} />
                            <Detail label={t('teams.type')} value={teamItem.type} />
                            <Detail label={t('teams.name_en')} value={teamItem.name_en} />
                            <Detail label={t('teams.name_am')} value={teamItem.name_am} />
                            <Detail label={t('teams.leader')} value={teamItem.leader?.name} />
                            <Detail label={t('common.status')} value={teamItem.is_active ? t('common.active') : t('common.inactive')} />
                        </div>
                    </SurfaceCard>

                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.related_records')}</h2>
                        <div className="mt-4">
                            <MetricCard label={t('navigation.users')} value={teamItem.stats.users} />
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
