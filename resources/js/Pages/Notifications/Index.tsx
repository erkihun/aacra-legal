import EmptyState from '@/Components/Ui/EmptyState';
import Pagination from '@/Components/Ui/Pagination';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';

type NotificationsPageProps = {
    notifications: {
        data: Array<{
            id: string;
            type: string;
            type_label: string;
            title: string;
            message: string;
            data: Record<string, string | null>;
            related_label?: string | null;
            url?: string | null;
            read_at?: string | null;
            created_at?: string | null;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    notificationSummary?: {
        unread_count: number;
    };
};

export default function NotificationsIndex({ notifications }: NotificationsPageProps) {
    const { t } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const { props } = usePage<PageProps>();
    const unreadCount = props.notificationSummary?.unread_count ?? 0;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.notifications') },
            ]}
        >
            <Head title={t('navigation.notifications')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('notifications.eyebrow')}
                    title={t('notifications.title')}
                    description={t('notifications.description')}
                    action={
                        <button
                            type="button"
                            onClick={() => router.patch(route('notifications.read-all'))}
                            className="btn-base btn-secondary focus-ring"
                        >
                            {t('notifications.mark_all_read')}
                        </button>
                    }
                />

                <div className="grid gap-4 md:grid-cols-3">
                    <SurfaceCard className="py-5">
                        <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                            {t('notifications.unread')}
                        </p>
                        <p className="mt-3 text-3xl font-semibold text-[color:var(--text)]">{unreadCount}</p>
                    </SurfaceCard>
                    <SurfaceCard className="py-5">
                        <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                            {t('notifications.total')}
                        </p>
                        <p className="mt-3 text-3xl font-semibold text-[color:var(--text)]">{notifications.data.length}</p>
                    </SurfaceCard>
                    <SurfaceCard className="py-5">
                        <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                            {t('notifications.last_updated')}
                        </p>
                        <p className="mt-3 text-sm font-semibold text-[color:var(--text)]">
                            {notifications.data[0]?.created_at
                                ? formatDateTime(notifications.data[0].created_at, t('common.not_available'))
                                : t('common.not_available')}
                        </p>
                    </SurfaceCard>
                </div>

                <div className="grid gap-4">
                    {notifications.data.length === 0 ? (
                        <EmptyState title={t('notifications.title')} description={t('notifications.empty')} />
                    ) : (
                        notifications.data.map((item) => (
                            <SurfaceCard
                                key={item.id}
                                className={item.read_at ? '' : 'border-cyan-300/30 bg-cyan-400/10 dark:border-cyan-400/20'}
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-3">
                                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{item.title}</h2>
                                            {!item.read_at ? (
                                                <span className="rounded-full bg-[var(--primary)] px-2 py-1 text-[11px] font-semibold uppercase text-white dark:text-slate-950">
                                                    {t('notifications.unread')}
                                                </span>
                                            ) : null}
                                        </div>
                                        <p className="text-sm font-medium text-[color:var(--muted-strong)]">{item.type_label}</p>
                                        <p className="text-sm leading-6 text-[color:var(--muted-strong)]">{item.message}</p>
                                        {item.data.case_number ? (
                                            <p className="text-sm text-[color:var(--muted-strong)]">
                                                {t('notifications.case_reference')}: {item.data.case_number}
                                            </p>
                                        ) : null}
                                        {item.data.request_number ? (
                                            <p className="text-sm text-[color:var(--muted-strong)]">
                                                {t('notifications.request_reference')}: {item.data.request_number}
                                            </p>
                                        ) : null}
                                        <p className="text-sm text-[color:var(--muted)]">
                                            {formatDateTime(item.created_at, t('common.not_available'))}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap gap-3">
                                        {item.url ? (
                                            <Link href={item.url} className="btn-base btn-primary focus-ring">
                                                {t('notifications.open')}
                                            </Link>
                                        ) : null}
                                        {!item.read_at ? (
                                            <button
                                                type="button"
                                                onClick={() => router.patch(route('notifications.read', item.id))}
                                                className="btn-base btn-secondary focus-ring"
                                            >
                                                {t('notifications.mark_read')}
                                            </button>
                                        ) : null}
                                    </div>
                                </div>
                            </SurfaceCard>
                        ))
                    )}
                </div>

                <Pagination links={notifications.links} />
            </PageContainer>
        </AuthenticatedLayout>
    );
}
