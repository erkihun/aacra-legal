import DataTable from '@/Components/Ui/DataTable';
import EmptyState from '@/Components/Ui/EmptyState';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import PageContainer from '@/Components/Ui/PageContainer';
import Pagination from '@/Components/Ui/Pagination';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type PublicPostRow = {
    id: string;
    route_key: string;
    title: string;
    slug: string;
    summary: string;
    status: string;
    published_at?: string | null;
    locale?: string | null;
    author?: string | null;
    cover_image_url?: string | null;
    public_url?: string | null;
    can_manage: boolean;
};

export default function PublicPostsIndex({ filters, posts, can, statusOptions, localeOptions }: any) {
    const { t } = useI18n();
    const { formatDate } = useDateFormatter();
    const [isFiltering, setIsFiltering] = useState(false);
    const form = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
        locale: filters.locale ?? '',
    });

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.public_posts') },
            ]}
        >
            <Head title={t('public_posts.index_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('public_posts.eyebrow')}
                    title={t('public_posts.index_title')}
                    description={t('public_posts.index_description')}
                    action={
                        can.create ? (
                            <Link href={route('public-posts.create')} className="btn-base btn-primary focus-ring">
                                {t('public_posts.new_post')}
                            </Link>
                        ) : undefined
                    }
                />

                <FiltersToolbar
                    title={t('public_posts.filters')}
                    actions={
                        <>
                            <button type="button" className="btn-base btn-secondary focus-ring" onClick={() => router.get(route('public-posts.index'))}>
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                className="btn-base btn-primary focus-ring"
                                disabled={isFiltering}
                                onClick={() => {
                                    setIsFiltering(true);
                                    router.get(route('public-posts.index'), form.data, {
                                        preserveState: true,
                                        replace: true,
                                        onFinish: () => setIsFiltering(false),
                                    });
                                }}
                            >
                                {t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <input
                        value={form.data.search}
                        onChange={(event) => form.setData('search', event.target.value)}
                        className="input-ui"
                        placeholder={t('public_posts.search_placeholder')}
                    />
                    <select value={form.data.status} onChange={(event) => form.setData('status', event.target.value)} className="select-ui">
                        <option value="">{t('common.all_statuses')}</option>
                        {statusOptions.map((option: any) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <select value={form.data.locale} onChange={(event) => form.setData('locale', event.target.value)} className="select-ui">
                        <option value="">{t('common.language')}</option>
                        {localeOptions.map((option: any) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </FiltersToolbar>

                {posts.data.length === 0 ? (
                    <EmptyState title={t('public_posts.empty_title')} description={t('public_posts.empty_description')} />
                ) : (
                    <>
                        <DataTable<PublicPostRow>
                            rows={posts.data}
                            rowKey={(row) => row.id}
                            emptyTitle={t('public_posts.empty_title')}
                            emptyDescription={t('public_posts.empty_description')}
                            actions={(row) => [
                                { label: t('common.view'), href: route('public-posts.show', row.route_key) },
                                ...(row.can_manage ? [{ label: t('common.edit'), href: route('public-posts.edit', row.route_key) }] : []),
                                ...(row.public_url ? [{ label: t('public_posts.open_public'), href: row.public_url }] : []),
                            ]}
                            columns={[
                                {
                                    key: 'title',
                                    header: t('public_posts.fields.title'),
                                    cell: (row) => (
                                        <div className="flex items-center gap-3">
                                            <div className="h-14 w-16 overflow-hidden rounded-xl bg-[color:var(--surface-muted)]">
                                                {row.cover_image_url ? (
                                                    <img src={row.cover_image_url} alt={row.title} className="h-full w-full object-cover" />
                                                ) : null}
                                            </div>
                                            <div>
                                                <p className="font-semibold text-[color:var(--text)]">{row.title}</p>
                                                <p className="mt-1 text-xs uppercase text-[color:var(--muted)]">{row.slug}</p>
                                            </div>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'meta',
                                    header: t('public_posts.meta'),
                                    cell: (row) => (
                                        <div>
                                            <p className="text-sm text-[color:var(--text)]">{row.author ?? t('common.not_available')}</p>
                                            <p className="mt-1 text-sm text-[color:var(--muted)]">
                                                {row.locale ? t(`settings.locale_options.${row.locale}`) : t('common.not_set')}
                                            </p>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'published_at',
                                    header: t('public_posts.fields.published_at'),
                                    cell: (row) => row.published_at ? formatDate(row.published_at) : t('common.not_set'),
                                },
                                {
                                    key: 'status',
                                    header: t('common.status'),
                                    cell: (row) => <StatusBadge value={row.status} />,
                                },
                            ]}
                        />
                        <Pagination links={posts.links} />
                    </>
                )}
            </PageContainer>
        </AuthenticatedLayout>
    );
}
