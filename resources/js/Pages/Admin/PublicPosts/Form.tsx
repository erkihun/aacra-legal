import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import BackButton from '@/Components/Ui/BackButton';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function PublicPostForm({ postItem, canDelete, statusOptions, localeOptions }: any) {
    const { t } = useI18n();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const isEditing = !!postItem;
    const form = useForm({
        title: postItem?.title ?? '',
        slug: postItem?.slug ?? '',
        summary: postItem?.summary ?? '',
        body: postItem?.body ?? '',
        status: postItem?.status ?? statusOptions[0]?.value ?? 'draft',
        published_at: postItem?.published_at ? String(postItem.published_at).slice(0, 16) : '',
        locale: postItem?.locale ?? '',
        cover_image: null as File | null,
    });

    const generatedSlug = useMemo(() => {
        return form.data.title
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }, [form.data.title]);

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.public_posts'), href: route('public-posts.index') },
                { label: isEditing ? t('common.edit') : t('common.create_record') },
            ]}
        >
            <Head title={isEditing ? t('public_posts.edit_title') : t('public_posts.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('public_posts.eyebrow')}
                    title={isEditing ? t('public_posts.edit_title') : t('public_posts.create_title')}
                    description={isEditing ? t('public_posts.edit_description') : t('public_posts.create_description')}
                    action={<BackButton fallbackHref={route('public-posts.index')} />}
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();

                        const payload = {
                            ...form.data,
                            slug: form.data.slug || generatedSlug,
                        };

                        if (isEditing) {
                            form.transform(() => ({
                                ...payload,
                                _method: 'patch',
                            }));

                            form.post(route('public-posts.update', postItem.route_key), {
                                forceFormData: true,
                                onFinish: () => form.transform((data) => data),
                            });

                            return;
                        }

                        form.transform(() => payload);
                        form.post(route('public-posts.store'), { forceFormData: true });
                    }}
                    className="space-y-4"
                >
                    <div className="grid gap-4 xl:grid-cols-[1.15fr,0.85fr]">
                        <SurfaceCard className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField label={t('public_posts.fields.title')} required error={form.errors.title}>
                                    <input
                                        value={form.data.title}
                                        onChange={(event) => {
                                            form.setData('title', event.target.value);
                                            if (!isEditing || form.data.slug === '' || form.data.slug === generatedSlug) {
                                                form.setData('slug', event.target.value
                                                    .toLowerCase()
                                                    .trim()
                                                    .replace(/[^a-z0-9\s-]/g, '')
                                                    .replace(/\s+/g, '-')
                                                    .replace(/-+/g, '-'));
                                            }
                                        }}
                                        className="input-ui"
                                    />
                                </FormField>
                                <FormField label={t('public_posts.fields.slug')} required error={form.errors.slug}>
                                    <input value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} className="input-ui" />
                                </FormField>
                            </div>

                            <FormField label={t('public_posts.fields.summary')} required error={form.errors.summary}>
                                <textarea value={form.data.summary} onChange={(event) => form.setData('summary', event.target.value)} rows={4} className="textarea-ui" />
                            </FormField>

                            <FormField label={t('public_posts.fields.body')} required error={form.errors.body}>
                                <textarea value={form.data.body} onChange={(event) => form.setData('body', event.target.value)} rows={16} className="textarea-ui" />
                            </FormField>
                        </SurfaceCard>

                        <div className="space-y-4">
                            <SurfaceCard className="space-y-4">
                                <FormField label={t('common.status')} required error={form.errors.status}>
                                    <select value={form.data.status} onChange={(event) => form.setData('status', event.target.value)} className="select-ui">
                                        {statusOptions.map((option: any) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>

                                <FormField label={t('public_posts.fields.published_at')} optional error={form.errors.published_at}>
                                    <input type="datetime-local" value={form.data.published_at} onChange={(event) => form.setData('published_at', event.target.value)} className="input-ui" />
                                </FormField>

                                <FormField label={t('public_posts.fields.locale')} optional error={form.errors.locale}>
                                    <select value={form.data.locale} onChange={(event) => form.setData('locale', event.target.value)} className="select-ui">
                                        <option value="">{t('common.not_set')}</option>
                                        {localeOptions.map((option: any) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>
                            </SurfaceCard>

                            <SurfaceCard className="space-y-4">
                                <FormField label={t('public_posts.fields.cover_image')} optional error={form.errors.cover_image}>
                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        onChange={(event) => form.setData('cover_image', event.target.files?.[0] ?? null)}
                                        className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                    />
                                </FormField>

                                {postItem?.cover_image_url ? (
                                    <div className="surface-muted overflow-hidden">
                                        <img src={postItem.cover_image_url} alt={postItem.title} className="h-48 w-full object-cover" />
                                    </div>
                                ) : null}

                                {postItem?.public_url ? (
                                    <Link href={postItem.public_url} className="text-sm font-semibold text-[color:var(--primary)]">
                                        {t('public_posts.open_public')}
                                    </Link>
                                ) : null}
                            </SurfaceCard>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            {isEditing && canDelete ? (
                                <button type="button" className="btn-base btn-danger focus-ring" onClick={() => setConfirmOpen(true)}>
                                    {t('common.delete')}
                                </button>
                            ) : null}
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <Link href={route('public-posts.index')} className="btn-base btn-secondary focus-ring">
                                {t('common.cancel')}
                            </Link>
                            <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>
                                {isEditing ? t('common.save_changes') : t('common.create_record')}
                            </button>
                        </div>
                    </div>
                </form>
            </PageContainer>

            <ConfirmationDialog
                open={confirmOpen}
                title={t('public_posts.delete_title')}
                description={t('public_posts.delete_description')}
                confirmLabel={t('common.delete')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => router.delete(route('public-posts.destroy', postItem.route_key))}
            />
        </AuthenticatedLayout>
    );
}
