import RequesterLayout from '@/Layouts/RequesterLayout';
import StatusBadge from '@/Components/Ui/StatusBadge';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';

type DashboardProps = {
    requester: {
        full_name: string;
        department?: { name_en: string; name_am?: string | null };
    };
    stats: {
        advisory: { total: number; pending: number; completed: number };
        lawsuit: { total: number; pending: number; approved: number };
    };
    recentAdvisory: Array<{ id: string; request_number: string; subject: string; status: string; date_submitted: string }>;
    recentLawsuit: Array<{ id: string; request_code: string; subject: string; status: string; date_submitted: string }>;
};

export default function RequesterDashboard({ requester, stats, recentAdvisory, recentLawsuit }: DashboardProps) {
    const { t, locale } = useI18n();

    const deptName = locale === 'am'
        ? requester.department?.name_am ?? requester.department?.name_en
        : requester.department?.name_en;

    return (
        <RequesterLayout breadcrumbs={[{ label: t('requester.nav_dashboard') }]}>
            <Head title={t('requester.nav_dashboard')} />

            <div className="space-y-8">
                {/* Welcome */}
                <div className="space-y-1">
                    <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">{t('requester.portal_name')}</p>
                    <h1 className="text-2xl font-bold text-[color:var(--text)]">
                        {t('requester.welcome_back')}, {requester.full_name}
                    </h1>
                    {deptName ? <p className="text-sm text-[color:var(--muted)]">{deptName}</p> : null}
                </div>

                {/* Quick actions */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <QuickAction
                        title={t('requester.nav_advisory')}
                        description={t('requester.advisory_quick_desc')}
                        href={route('requester.advisory.create')}
                        buttonLabel={t('requester.new_advisory')}
                        color="primary"
                    />
                    <QuickAction
                        title={t('requester.nav_lawsuit')}
                        description={t('requester.lawsuit_quick_desc')}
                        href={route('requester.lawsuit-requests.create')}
                        buttonLabel={t('requester.new_lawsuit')}
                        color="primary"
                    />
                    <QuickAction
                        title={t('requester.my_requests')}
                        description={t('requester.my_requests_desc')}
                        href={route('requester.advisory.index')}
                        buttonLabel={t('requester.view_all')}
                        color="secondary"
                    />
                </div>

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label={t('requester.stat_advisory_total')} value={stats.advisory.total} />
                    <StatCard label={t('requester.stat_advisory_pending')} value={stats.advisory.pending} />
                    <StatCard label={t('requester.stat_lawsuit_total')} value={stats.lawsuit.total} />
                    <StatCard label={t('requester.stat_lawsuit_approved')} value={stats.lawsuit.approved} />
                </div>

                {/* Recent requests */}
                <div className="grid gap-6 lg:grid-cols-2">
                    <RecentList
                        title={t('requester.recent_advisory')}
                        items={recentAdvisory}
                        getCode={(r) => r.request_number}
                        getRoute={(r) => route('requester.advisory.show', { advisoryRequest: r.id })}
                        emptyLabel={t('requester.no_advisory_yet')}
                    />
                    <RecentList
                        title={t('requester.recent_lawsuit')}
                        items={recentLawsuit}
                        getCode={(r) => r.request_code}
                        getRoute={(r) => route('requester.lawsuit-requests.show', { lawsuitRequest: r.id })}
                        emptyLabel={t('requester.no_lawsuit_yet')}
                    />
                </div>
            </div>
        </RequesterLayout>
    );
}

function StatCard({ label, value }: { label: string; value: number }) {
    return (
        <div className="surface-card rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)] p-5">
            <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-3xl font-bold text-[color:var(--text)]">{value}</p>
        </div>
    );
}

function QuickAction({
    title, description, href, buttonLabel, color,
}: {
    title: string;
    description: string;
    href: string;
    buttonLabel: string;
    color: 'primary' | 'secondary';
}) {
    return (
        <div className="flex flex-col justify-between gap-4 rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)] p-6">
            <div className="space-y-1">
                <h3 className="text-sm font-semibold text-[color:var(--text)]">{title}</h3>
                <p className="text-xs text-[color:var(--muted)]">{description}</p>
            </div>
            <Link
                href={href}
                className={`btn-base focus-ring self-start ${color === 'primary' ? 'btn-primary' : 'btn-secondary'}`}
            >
                {buttonLabel}
            </Link>
        </div>
    );
}

type RecentItem = { id: string; subject: string; status: string; date_submitted: string };

function RecentList<T extends RecentItem>({
    title, items, getCode, getRoute, emptyLabel,
}: {
    title: string;
    items: T[];
    getCode: (item: T) => string;
    getRoute: (item: T) => string;
    emptyLabel: string;
}) {
    return (
        <div className="rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)]">
            <div className="border-b border-[color:var(--border)] px-5 py-4">
                <h3 className="text-sm font-semibold text-[color:var(--text)]">{title}</h3>
            </div>
            {items.length === 0 ? (
                <div className="px-5 py-8 text-center text-sm text-[color:var(--muted)]">{emptyLabel}</div>
            ) : (
                <ul className="divide-y divide-[color:var(--border)]">
                    {items.map((item) => (
                        <li key={item.id}>
                            <Link
                                href={getRoute(item)}
                                className="flex items-center justify-between gap-4 px-5 py-3 hover:bg-[color:var(--surface-muted)]"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium text-[color:var(--text)]">{item.subject}</p>
                                    <p className="text-xs text-[color:var(--muted)]">{getCode(item)} · {item.date_submitted}</p>
                                </div>
                                <StatusBadge value={item.status} />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
