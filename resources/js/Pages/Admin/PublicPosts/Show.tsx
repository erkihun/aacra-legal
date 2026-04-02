import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function PublicPostShow({ postItem, can }: any) {
    const { t } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [deleteOpen, setDeleteOpen] = useState(false);
    const statusForm = useForm({
        published_at: postItem.published_at ? String(postItem.published_at).slice(0, 16) : '',
    });

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.public_posts'), href: route('public-posts.index') },
                { label: postItem.title },
            ]}
        >
            <Head title={postItem.title} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('public_posts.eyebrow')}
                    title={postItem.title}
                    description={postItem.summary}
                    action={
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('public-posts.index')} />
                            {can.update ? (
                                <Link href={route('public-posts.edit', postItem.route_key)} className="btn-base btn-primary focus-ring">
                                    {t('common.edit')}
                                </Link>
                            ) : null}
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={postItem.status} />
                    {postItem.locale ? (
                        <span className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold uppercase text-[color:var(--muted-strong)]">
                            {postItem.locale}
                        </span>
                    ) : null}
                </div>

                <div className="grid gap-4 xl:grid-cols-[1.15fr,0.85fr]">
                    <SurfaceCard>
                        {postItem.cover_image_url ? (
                            <img src={postItem.cover_image_url} alt={postItem.title} className="h-72 w-full rounded-2xl object-cover" />
                        ) : null}
                        <div className="mt-5 whitespace-pre-wrap text-base leading-8 text-[color:var(--text)]">
                            {postItem.body}
                        </div>
                    </SurfaceCard>

                    <div className="space-y-4">
                        <SurfaceCard className="space-y-4">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('public_posts.meta')}</h2>
                            <MetaRow label={t('public_posts.fields.author')} value={postItem.author ?? t('common.not_available')} />
                            <MetaRow label={t('public_posts.fields.slug')} value={postItem.slug} />
                            <MetaRow label={t('public_posts.fields.published_at')} value={postItem.published_at ? formatDateTime(postItem.published_at) : t('common.not_set')} />
                            {postItem.public_url ? (
                                <Link href={postItem.public_url} className="text-sm font-semibold text-[color:var(--primary)]">
                                    {t('public_posts.open_public')}
                                </Link>
                            ) : null}
                        </SurfaceCard>

                        {can.publish ? (
                            <SurfaceCard className="space-y-4">
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('public_posts.publish_actions')}</h2>
                                <div className="grid gap-4">
                                    <label className="space-y-2">
                                        <span className="text-sm font-medium text-[color:var(--text)]">{t('public_posts.fields.published_at')}</span>
                                        <input
                                            type="datetime-local"
                                            value={statusForm.data.published_at}
                                            onChange={(event) => statusForm.setData('published_at', event.target.value)}
                                            className="input-ui"
                                        />
                                    </label>
                                    <div className="flex flex-wrap gap-3">
                                        {postItem.status !== 'published' ? (
                                            <button
                                                type="button"
                                                className="btn-base btn-primary focus-ring"
                                                onClick={() => statusForm.patch(route('public-posts.publish', postItem.route_key))}
                                            >
                                                {t('public_posts.publish')}
                                            </button>
                                        ) : (
                                            <button
                                                type="button"
                                                className="btn-base btn-secondary focus-ring"
                                                onClick={() => router.patch(route('public-posts.unpublish', postItem.route_key))}
                                            >
                                                {t('public_posts.unpublish')}
                                            </button>
                                        )}

                                        {can.delete ? (
                                            <button type="button" className="btn-base btn-danger focus-ring" onClick={() => setDeleteOpen(true)}>
                                                {t('common.delete')}
                                            </button>
                                        ) : null}
                                    </div>
                                </div>
                            </SurfaceCard>
                        ) : null}
                    </div>
                </div>
            </PageContainer>

            <ConfirmationDialog
                open={deleteOpen}
                title={t('public_posts.delete_title')}
                description={t('public_posts.delete_description')}
                confirmLabel={t('common.delete')}
                onCancel={() => setDeleteOpen(false)}
                onConfirm={() => router.delete(route('public-posts.destroy', postItem.route_key))}
            />
        </AuthenticatedLayout>
    );
}

function MetaRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="surface-muted px-4 py-4">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-sm font-semibold text-[color:var(--text)]">{value}</p>
        </div>
    );
}
