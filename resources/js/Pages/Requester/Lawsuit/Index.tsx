import RequesterLayout from '@/Layouts/RequesterLayout';
import StatusBadge from '@/Components/Ui/StatusBadge';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';

type LawsuitIndexProps = {
    filters: { search?: string; status?: string };
    requests: {
        data: Array<{
            id: string;
            request_code: string;
            subject: string;
            status: string;
            date_submitted: string | null;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    statusOptions: Array<{ label: string; value: string }>;
};

export default function RequesterLawsuitIndex({ filters, requests, statusOptions }: LawsuitIndexProps) {
    const { t } = useI18n();
    const { data, setData } = useForm({ search: filters.search ?? '', status: filters.status ?? '' });

    const applyFilters = () => {
        router.get(route('requester.lawsuit-requests.index'), data, { preserveState: true, replace: true });
    };

    const resetFilters = () => {
        setData({ search: '', status: '' });
        router.get(route('requester.lawsuit-requests.index'));
    };

    return (
        <RequesterLayout
            breadcrumbs={[
                { label: t('requester.nav_dashboard'), href: route('requester.dashboard') },
                { label: t('requester.nav_lawsuit') },
            ]}
        >
            <Head title={t('requester.nav_lawsuit')} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-bold text-[color:var(--text)]">{t('requester.lawsuit_requests')}</h1>
                        <p className="mt-1 text-sm text-[color:var(--muted)]">{t('requester.lawsuit_index_desc')}</p>
                    </div>
                    <Link href={route('requester.lawsuit-requests.create')} className="btn-base btn-primary focus-ring">
                        {t('requester.new_lawsuit')}
                    </Link>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap items-center gap-3 rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)] p-4">
                    <input
                        value={data.search}
                        onChange={(e) => setData('search', e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                        placeholder={t('requester.search_placeholder')}
                        className="input-ui min-w-[200px] flex-1"
                    />
                    <select
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                        className="select-ui"
                    >
                        <option value="">{t('requester.all_statuses')}</option>
                        {statusOptions.map((opt) => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                    </select>
                    <button type="button" onClick={applyFilters} className="btn-base btn-primary focus-ring">
                        {t('requester.apply_filters')}
                    </button>
                    <button type="button" onClick={resetFilters} className="btn-base btn-secondary focus-ring">
                        {t('requester.reset_filters')}
                    </button>
                </div>

                {/* Table */}
                <div className="overflow-hidden rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)]">
                    {requests.data.length === 0 ? (
                        <div className="px-6 py-16 text-center">
                            <p className="text-sm font-medium text-[color:var(--text)]">{t('requester.no_lawsuit_yet')}</p>
                            <p className="mt-1 text-xs text-[color:var(--muted)]">{t('requester.lawsuit_empty_hint')}</p>
                        </div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-[color:var(--border)] text-left text-xs font-semibold uppercase text-[color:var(--muted)]">
                                    <th className="px-5 py-3">{t('requester.request_code')}</th>
                                    <th className="px-5 py-3">{t('requester.subject')}</th>
                                    <th className="px-5 py-3">{t('requester.date_submitted')}</th>
                                    <th className="px-5 py-3">{t('common.status')}</th>
                                    <th className="px-5 py-3 text-right">{t('common.actions')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-[color:var(--border)]">
                                {requests.data.map((item) => (
                                    <tr key={item.id} className="hover:bg-[color:var(--surface-muted)]">
                                        <td className="px-5 py-3 font-mono text-xs text-[color:var(--muted)]">{item.request_code}</td>
                                        <td className="max-w-xs px-5 py-3">
                                            <p className="truncate text-[color:var(--text)]">{item.subject}</p>
                                        </td>
                                        <td className="px-5 py-3 text-xs text-[color:var(--muted)]">{item.date_submitted ?? '—'}</td>
                                        <td className="px-5 py-3">
                                            <StatusBadge value={item.status} />
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            <Link
                                                href={route('requester.lawsuit-requests.show', { lawsuitRequest: item.id })}
                                                className="btn-base btn-secondary focus-ring text-xs"
                                            >
                                                {t('common.view')}
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* Pagination */}
                {requests.links.length > 3 ? (
                    <div className="flex flex-wrap items-center justify-center gap-1">
                        {requests.links.map((link, i) => (
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className={`rounded-lg px-3 py-1.5 text-sm ${link.active ? 'bg-[color:var(--primary)] text-white' : 'text-[color:var(--text)] hover:bg-[color:var(--surface-muted)]'}`}
                                />
                            ) : (
                                <span
                                    key={i}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className="rounded-lg px-3 py-1.5 text-sm text-[color:var(--muted)]"
                                />
                            )
                        ))}
                    </div>
                ) : null}
            </div>
        </RequesterLayout>
    );
}
