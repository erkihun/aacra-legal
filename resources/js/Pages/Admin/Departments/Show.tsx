import MetricCard from '@/Components/Ui/MetricCard';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';

export default function DepartmentShow({ departmentItem, can }: any) {
    const { t, locale } = useI18n();
    const departmentName = locale === 'am' ? departmentItem.name_am : departmentItem.name_en;
    const alternateName = locale === 'am' ? departmentItem.name_en : departmentItem.name_am;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.departments'), href: route('departments.index') },
                { label: departmentName },
            ]}
        >
            <Head title={departmentName} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('departments.eyebrow')}
                    title={departmentName}
                    description={departmentItem.code}
                    action={
                        can.update ? (
                            <Link href={route('departments.edit', departmentItem.id)} className="btn-base btn-primary focus-ring">
                                {t('common.edit')}
                            </Link>
                        ) : undefined
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={departmentItem.is_active ? 'active' : 'inactive'} />
                </div>

                <div className="grid gap-4 xl:grid-cols-[1.15fr,0.85fr]">
                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.overview')}</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Detail label={t('common.code')} value={departmentItem.code} />
                            <Detail label={t('common.status')} value={departmentItem.is_active ? t('common.active') : t('common.inactive')} />
                            <Detail label={t('departments.name_en')} value={departmentItem.name_en} />
                            <Detail label={t('departments.name_am')} value={departmentItem.name_am} />
                            <Detail label={t('common.name')} value={departmentName} />
                            <Detail label={t('common.description')} value={alternateName} />
                        </div>
                    </SurfaceCard>

                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.related_records')}</h2>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <MetricCard label={t('navigation.users')} value={departmentItem.stats.users} />
                            <MetricCard label={t('navigation.advisory_requests')} value={departmentItem.stats.advisory_requests} />
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
