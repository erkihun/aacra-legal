import MetricCard from '@/Components/Ui/MetricCard';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

type DashboardProps = {
    role_context: {
        key: string;
        label: string;
        description: string;
    };
    requester_summary?: {
        total: number;
        pending: number;
        returned: number;
        completed: number;
    } | null;
    metrics: {
        open_cases: number;
        upcoming_hearings: number;
        pending_director_approvals: number;
        advisory_awaiting_assignment: number;
        overdue_advisory_requests: number;
        judgments_recorded_this_month: number;
        closed_matters_this_month: number;
        monthly_completions: {
            advisory: number;
            cases: number;
        };
    };
    cases_by_status: Array<{
        status: string;
        total: number;
    }>;
    work_by_expert: Array<{
        id: string;
        name: string;
        advisory: number;
        cases: number;
    }>;
    recent_advisories: Array<{
        id: string;
        request_number: string;
        subject: string;
        status: string;
        due_date?: string | null;
    }>;
    recent_cases: Array<{
        id: string;
        case_number: string;
        plaintiff: string;
        status: string;
        next_hearing_date?: string | null;
    }>;
    recently_updated_matters: Array<{
        id: string;
        module: string;
        reference: string;
        subject: string;
        status: string;
        updated_at?: string | null;
        route: string;
    }>;
};

function sumWorkload(item: { advisory: number; cases: number }) {
    return item.advisory + item.cases;
}

function dashboardIcon(path: string) {
    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8">
            <path d={path} strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

export default function Dashboard({
    role_context,
    requester_summary,
    metrics,
    cases_by_status,
    work_by_expert,
    recent_advisories,
    recent_cases,
    recently_updated_matters,
}: DashboardProps) {
    const { t } = useI18n();
    const { formatDate } = useDateFormatter();
    const { props } = usePage<PageProps>();
    const permissions = props.auth.user?.permissions ?? [];
    const canViewReports = permissions.includes('reports.view');
    const canViewAdvisory = permissions.includes('advisory.view_any') || permissions.includes('advisory.view_own') || permissions.includes('advisory-requests.view');
    const canViewCases = permissions.includes('cases.view_any') || permissions.includes('cases.view_own') || permissions.includes('legal-cases.view');
    const canCreateAdvisory = permissions.includes('advisory.create') || permissions.includes('advisory-requests.create');
    const maxCaseStatusTotal = Math.max(...cases_by_status.map((item) => item.total), 1);
    const maxWorkload = Math.max(...work_by_expert.map((item) => sumWorkload(item)), 1);
    const isRequesterDashboard = role_context.key === 'department_requester' && requester_summary !== null;
    const requesterSummary = requester_summary ?? {
        total: 0,
        pending: 0,
        returned: 0,
        completed: 0,
    };

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('navigation.dashboard') }]}>
            <Head title={t('navigation.dashboard')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t(`dashboard.role.${role_context.key}`)}
                    title={t('dashboard.title')}
                    description={t('dashboard.description')}
                    action={
                        isRequesterDashboard && canCreateAdvisory ? (
                            <Link href={route('advisory.create')} className="btn-base btn-primary focus-ring">
                                {t('advisory.new_request')}
                            </Link>
                        ) : undefined
                    }
                />

                {isRequesterDashboard ? (
                    <div className="stat-grid">
                        <MetricCard
                            label={t('public.portal.total_requests')}
                            value={requesterSummary.total}
                            icon={dashboardIcon('M7 5.5h10A1.5 1.5 0 0 1 18.5 7v10a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 17V7A1.5 1.5 0 0 1 7 5.5Z')}
                        />
                        <MetricCard
                            label={t('public.portal.pending_requests')}
                            value={requesterSummary.pending}
                            icon={dashboardIcon('M12 7.5v5l3 1.5M20 12A8 8 0 1 1 4 12a8 8 0 0 1 16 0Z')}
                        />
                        <MetricCard
                            label={t('public.portal.returned_requests')}
                            value={requesterSummary.returned}
                            icon={dashboardIcon('M8 12h8m0 0-3-3m3 3-3 3M20 12A8 8 0 1 1 4 12a8 8 0 0 1 16 0Z')}
                        />
                        <MetricCard
                            label={t('public.portal.completed_requests')}
                            value={requesterSummary.completed}
                            icon={dashboardIcon('M7.5 12.5 10.5 15.5 16.5 8.5')}
                        />
                    </div>
                ) : null}

                <div className="stat-grid">
                    <MetricCard
                        label={t('dashboard.metrics.open_cases')}
                        value={metrics.open_cases}
                        icon={dashboardIcon('M7 6.5h10A1.5 1.5 0 0 1 18.5 8v8A1.5 1.5 0 0 1 17 17.5H7A1.5 1.5 0 0 1 5.5 16V8A1.5 1.5 0 0 1 7 6.5Z')}
                    />
                    <MetricCard
                        label={t('dashboard.metrics.upcoming_hearings')}
                        value={metrics.upcoming_hearings}
                        icon={dashboardIcon('M8 4.5v3M16 4.5v3M5.5 9.5h13M7 6.5h10A1.5 1.5 0 0 1 18.5 8v9A1.5 1.5 0 0 1 17 18.5H7A1.5 1.5 0 0 1 5.5 17V8A1.5 1.5 0 0 1 7 6.5Z')}
                    />
                    <MetricCard
                        label={t('dashboard.metrics.pending_director_approvals')}
                        value={metrics.pending_director_approvals}
                        icon={dashboardIcon('M12 7.5v5l3 1.5M20 12A8 8 0 1 1 4 12a8 8 0 0 1 16 0Z')}
                    />
                    <MetricCard
                        label={t('dashboard.metrics.overdue_advisory_requests')}
                        value={metrics.overdue_advisory_requests}
                        icon={dashboardIcon('M12 7v5m0 4h.01M5.8 19h12.4c1.1 0 1.8-1.2 1.3-2.15L13.3 6.2c-.55-1-1.95-1-2.5 0L4.5 16.85C4 17.8 4.7 19 5.8 19Z')}
                    />
                </div>

                {isRequesterDashboard ? (
                    <div className="grid gap-4 xl:grid-cols-[0.9fr,1.1fr]">
                        <SurfaceCard>
                            <SectionTitle title={t('public.portal.quick_actions')} />
                            <div className="mt-5 grid gap-3">
                                {canCreateAdvisory ? (
                                    <Link href={route('advisory.create')} className="surface-muted block px-4 py-4 transition hover:-translate-y-0.5">
                                        <p className="font-semibold text-[color:var(--text)]">{t('public.actions.submit_request')}</p>
                                        <p className="mt-2 text-sm text-[color:var(--muted)]">{t('public.portal.submit_request_hint')}</p>
                                    </Link>
                                ) : null}
                                {canViewAdvisory ? (
                                    <Link href={route('advisory.index')} className="surface-muted block px-4 py-4 transition hover:-translate-y-0.5">
                                        <p className="font-semibold text-[color:var(--text)]">{t('public.actions.track_requests')}</p>
                                        <p className="mt-2 text-sm text-[color:var(--muted)]">{t('public.portal.track_request_hint')}</p>
                                    </Link>
                                ) : null}
                                <Link href={route('notifications.index')} className="surface-muted block px-4 py-4 transition hover:-translate-y-0.5">
                                    <p className="font-semibold text-[color:var(--text)]">{t('navigation.notifications')}</p>
                                    <p className="mt-2 text-sm text-[color:var(--muted)]">{t('public.portal.notifications_hint')}</p>
                                </Link>
                            </div>
                        </SurfaceCard>

                        <SurfaceCard>
                            <SectionTitle title={t('public.portal.status_tracking_title')} />
                            <div className="mt-5 space-y-3">
                                {recent_advisories.length === 0 ? (
                                    <p className="text-sm text-[color:var(--muted)]">{t('dashboard.no_recent_advisories')}</p>
                                ) : (
                                    recent_advisories.map((item) => (
                                        <Link
                                            key={item.id}
                                            href={route('advisory.show', { advisoryRequest: item.id })}
                                            className="surface-muted block px-4 py-4 transition hover:-translate-y-0.5"
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <p className="text-xs uppercase text-[color:var(--muted)]">
                                                        {item.request_number}
                                                    </p>
                                                    <p className="mt-2 font-semibold text-[color:var(--text)]">{item.subject}</p>
                                                    <p className="mt-2 text-sm text-[color:var(--muted)]">
                                                        {item.due_date ? `${t('dashboard.due_date')}: ${formatDate(item.due_date)}` : t('dashboard.no_due_date')}
                                                    </p>
                                                </div>
                                                <StatusBadge value={item.status} />
                                            </div>
                                        </Link>
                                    ))
                                )}
                            </div>
                        </SurfaceCard>
                    </div>
                ) : null}

                <div className="grid gap-4 xl:grid-cols-[1.2fr,0.8fr]">
                    <SurfaceCard className="grid gap-5 lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                                {t('dashboard.role_context')}
                            </p>
                            <h2 className="mt-2 text-2xl font-semibold text-[color:var(--text)]">
                                {t(`dashboard.role.${role_context.key}`)}
                            </h2>
                            <p className="mt-3 max-w-2xl text-sm leading-6 text-[color:var(--muted-strong)]">
                                {t(`dashboard.role_description.${role_context.key}`)}
                            </p>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <MiniStat label={t('dashboard.metrics.awaiting_assignment')} value={metrics.advisory_awaiting_assignment} />
                            <MiniStat label={t('dashboard.metrics.judgments_this_month')} value={metrics.judgments_recorded_this_month} />
                            <MiniStat label={t('dashboard.metrics.closed_this_month')} value={metrics.closed_matters_this_month} />
                            <MiniStat label={t('dashboard.metrics.monthly_advisory')} value={metrics.monthly_completions.advisory} />
                        </div>
                    </SurfaceCard>

                    <SurfaceCard>
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                                    {t('dashboard.monthly_delivery')}
                                </p>
                                <h2 className="mt-2 text-2xl font-semibold text-[color:var(--text)]">
                                    {t('dashboard.monthly_delivery_title')}
                                </h2>
                            </div>
                        </div>

                        <div className="mt-5 grid gap-4">
                            <ProgressStat
                                label={t('navigation.advisory_requests')}
                                value={metrics.monthly_completions.advisory}
                                total={Math.max(metrics.monthly_completions.advisory, metrics.monthly_completions.cases, 1)}
                            />
                            <ProgressStat
                                label={t('navigation.legal_cases')}
                                value={metrics.monthly_completions.cases}
                                total={Math.max(metrics.monthly_completions.advisory, metrics.monthly_completions.cases, 1)}
                            />
                        </div>
                    </SurfaceCard>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <SurfaceCard>
                        <SectionTitle title={t('dashboard.cases_by_status')} href={route('cases.index')} linkLabel={t('common.view_all')} />

                        <div className="mt-5 space-y-4">
                            {cases_by_status.length === 0 ? (
                                <p className="text-sm text-[color:var(--muted)]">{t('dashboard.no_case_data')}</p>
                            ) : (
                                cases_by_status.map((item) => (
                                    <div key={item.status} className="surface-muted px-4 py-4">
                                        <div className="flex items-center justify-between gap-3 text-sm">
                                            <span className="font-medium text-[color:var(--text)]">{item.status}</span>
                                            <span className="font-semibold text-[color:var(--text)]">{item.total}</span>
                                        </div>
                                        <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                            <div
                                                className="h-full rounded-full bg-[var(--primary)]"
                                                style={{ width: `${Math.max((item.total / maxCaseStatusTotal) * 100, 6)}%` }}
                                            />
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </SurfaceCard>

                    <SurfaceCard>
                        <SectionTitle
                            title={t('dashboard.workload_by_expert')}
                            href={canViewReports ? route('reports.index') : undefined}
                            linkLabel={canViewReports ? t('dashboard.open_reports') : undefined}
                        />

                        <div className="mt-5 space-y-4">
                            {work_by_expert.length === 0 ? (
                                <p className="text-sm text-[color:var(--muted)]">{t('dashboard.no_workload_data')}</p>
                            ) : (
                                work_by_expert.map((expert) => (
                                    <div key={expert.id} className="surface-muted px-4 py-4">
                                        <div className="flex items-center justify-between gap-3">
                                            <div>
                                                <p className="font-semibold text-[color:var(--text)]">{expert.name}</p>
                                                <p className="mt-1 text-sm text-[color:var(--muted)]">
                                                    {t('dashboard.advisory_label')}: {expert.advisory} | {t('dashboard.cases_label')}: {expert.cases}
                                                </p>
                                            </div>
                                            <p className="text-sm font-semibold text-[color:var(--primary)]">{sumWorkload(expert)}</p>
                                        </div>
                                        <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                            <div
                                                className="h-full rounded-full bg-emerald-500"
                                                style={{ width: `${Math.max((sumWorkload(expert) / maxWorkload) * 100, 6)}%` }}
                                            />
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </SurfaceCard>
                </div>

                <div className="grid gap-4 xl:grid-cols-[0.95fr,1.05fr]">
                    <SurfaceCard>
                        <SectionTitle title={t('dashboard.recently_updated')} />
                        <div className="mt-5 space-y-3">
                            {recently_updated_matters.length === 0 ? (
                                <p className="text-sm text-[color:var(--muted)]">{t('dashboard.no_recent_updates')}</p>
                            ) : (
                                recently_updated_matters.map((matter) => (
                                    <Link
                                        key={`${matter.module}-${matter.id}`}
                                        href={matter.route}
                                        className="surface-muted block px-4 py-4 transition hover:-translate-y-0.5"
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <p className="text-xs uppercase text-[color:var(--muted)]">
                                                    {matter.module} | {matter.reference}
                                                </p>
                                                <p className="mt-2 font-semibold text-[color:var(--text)]">{matter.subject}</p>
                                                <p className="mt-2 text-sm text-[color:var(--muted)]">{formatDate(matter.updated_at)}</p>
                                            </div>
                                            <StatusBadge value={matter.status} />
                                        </div>
                                    </Link>
                                ))
                            )}
                        </div>
                    </SurfaceCard>

                    <div className="grid gap-4">
                        <MatterList
                            title={t('dashboard.recent_advisories')}
                            emptyLabel={t('dashboard.no_recent_advisories')}
                            href={canViewAdvisory ? route('advisory.index') : undefined}
                            hrefLabel={canViewAdvisory ? t('common.view_all') : undefined}
                            items={recent_advisories.map((item) => ({
                                id: item.id,
                                href: route('advisory.show', { advisoryRequest: item.id }),
                                reference: item.request_number,
                                subject: item.subject,
                                secondary: item.due_date ? `${t('dashboard.due_date')}: ${formatDate(item.due_date)}` : t('dashboard.no_due_date'),
                                status: item.status,
                            }))}
                        />

                        <MatterList
                            title={t('dashboard.recent_cases')}
                            emptyLabel={t('dashboard.no_recent_cases')}
                            href={canViewCases ? route('cases.index') : undefined}
                            hrefLabel={canViewCases ? t('common.view_all') : undefined}
                            items={recent_cases.map((item) => ({
                                id: item.id,
                                href: route('cases.show', item.id),
                                reference: item.case_number,
                                subject: item.plaintiff,
                                secondary: item.next_hearing_date ? `${t('dashboard.next_hearing')}: ${formatDate(item.next_hearing_date)}` : t('dashboard.no_hearing_scheduled'),
                                status: item.status,
                            }))}
                        />
                    </div>
                </div>
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function MiniStat({ label, value }: { label: string; value: number }) {
    return (
        <div className="surface-muted px-4 py-4">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-[color:var(--text)]">{value}</p>
        </div>
    );
}

function ProgressStat({
    label,
    value,
    total,
}: {
    label: string;
    value: number;
    total: number;
}) {
    return (
        <div className="surface-muted px-4 py-4">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-medium text-[color:var(--text)]">{label}</p>
                <p className="text-lg font-semibold text-[color:var(--text)]">{value}</p>
            </div>
            <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                <div className="h-full rounded-full bg-[var(--primary)]" style={{ width: `${Math.max((value / total) * 100, 6)}%` }} />
            </div>
        </div>
    );
}

function SectionTitle({
    title,
    href,
    linkLabel,
}: {
    title: string;
    href?: string;
    linkLabel?: string;
}) {
    return (
        <div className="flex items-center justify-between gap-3">
            <h2 className="text-xl font-semibold text-[color:var(--text)]">{title}</h2>
            {href && linkLabel ? (
                <Link href={href} className="text-sm font-medium text-[color:var(--primary)]">
                    {linkLabel}
                </Link>
            ) : null}
        </div>
    );
}

function MatterList({
    title,
    href,
    hrefLabel,
    emptyLabel,
    items,
}: {
    title: string;
    href?: string;
    hrefLabel?: string;
    emptyLabel: string;
    items: Array<{
        id: string;
        href: string;
        reference: string;
        subject: string;
        secondary: string;
        status: string;
    }>;
}) {
    return (
        <SurfaceCard>
            <SectionTitle title={title} href={href} linkLabel={hrefLabel} />

            <div className="mt-5 space-y-3">
                {items.length === 0 ? (
                    <p className="text-sm text-[color:var(--muted)]">{emptyLabel}</p>
                ) : (
                    items.map((item) => (
                        <Link key={item.id} href={item.href} className="surface-muted block px-4 py-4 transition hover:-translate-y-0.5">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-xs uppercase text-[color:var(--muted)]">{item.reference}</p>
                                    <p className="mt-2 font-semibold text-[color:var(--text)]">{item.subject}</p>
                                    <p className="mt-2 text-sm text-[color:var(--muted)]">{item.secondary}</p>
                                </div>
                                <StatusBadge value={item.status} />
                            </div>
                        </Link>
                    ))
                )}
            </div>
        </SurfaceCard>
    );
}
