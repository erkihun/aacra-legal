import BackButton from '@/Components/Ui/BackButton';
import MetricCard from '@/Components/Ui/MetricCard';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';

export default function BranchShow({ branchItem, can }: any) {
    const { t, locale } = useI18n();
    const branchName = locale === 'am' ? branchItem.name_am || branchItem.name_en : branchItem.name_en || branchItem.name_am;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.branches'), href: route('branches.index') },
                { label: branchName },
            ]}
        >
            <Head title={branchName} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('branches.eyebrow')}
                    title={branchName}
                    description={branchItem.code}
                    action={
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('branches.index')} />
                            {can.update ? (
                                <Link href={route('branches.edit', branchItem.id)} className="btn-base btn-primary focus-ring">
                                    {t('common.edit')}
                                </Link>
                            ) : null}
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={branchItem.is_active ? 'active' : 'inactive'} />
                    {branchItem.is_head_office ? <StatusBadge value="opened" label={t('branches.head_office')} /> : null}
                </div>

                <div className="grid gap-4 xl:grid-cols-[1.2fr,0.8fr]">
                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.overview')}</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Detail label={t('common.code')} value={branchItem.code} />
                            <Detail label={t('branches.location')} value={formatLocation(branchItem, t)} />
                            <Detail label={t('branches.branch_name_en')} value={branchItem.name_en} />
                            <Detail label={t('branches.branch_name_am')} value={branchItem.name_am} />
                            <Detail label={t('branches.phone')} value={branchItem.phone} />
                            <Detail label={t('branches.email')} value={branchItem.email} />
                            <Detail label={t('branches.manager_name')} value={branchItem.manager_name} />
                            <Detail label={t('branches.address')} value={branchItem.address} />
                            <Detail label={t('branches.notes')} value={branchItem.notes} />
                            <Detail label={t('branches.created_at')} value={branchItem.created_at ? new Date(branchItem.created_at).toLocaleString() : null} />
                            <Detail label={t('branches.updated_at')} value={branchItem.updated_at ? new Date(branchItem.updated_at).toLocaleString() : null} />
                        </div>
                    </SurfaceCard>

                    <div className="space-y-4">
                        <SurfaceCard>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.related_records')}</h2>
                            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                <MetricCard label={t('navigation.users')} value={branchItem.stats.users} />
                                <MetricCard label={t('navigation.complaints')} value={branchItem.stats.complaints} />
                            </div>
                        </SurfaceCard>
                    </div>
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
            <p className="mt-2 text-sm font-semibold text-[color:var(--text)] whitespace-pre-line">{value ?? t('common.not_available')}</p>
        </div>
    );
}

function formatLocation(branchItem: any, t: (key: string) => string) {
    const parts = [branchItem.region, branchItem.city].filter(Boolean);

    return parts.length > 0 ? parts.join(', ') : t('common.not_available');
}
