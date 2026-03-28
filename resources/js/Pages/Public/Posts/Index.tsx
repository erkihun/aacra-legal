import EmptyState from '@/Components/Ui/EmptyState';
import Pagination from '@/Components/Ui/Pagination';
import PublicLayout from '@/Layouts/PublicLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';

type PostsIndexProps = {
    posts: {
        data: Array<{
            id: string;
            title: string;
            summary: string;
            published_at?: string | null;
            author?: string | null;
            cover_image_url?: string | null;
            url: string;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    featuredPost?: {
        id: string;
        title: string;
        slug: string;
        summary: string;
        body: string;
    } | null;
};

export default function PostsIndex({ posts }: PostsIndexProps) {
    const { t } = useI18n();
    const { formatDate } = useDateFormatter();

    return (
        <PublicLayout title={t('public.posts.index_title')} description={t('public.posts.description')}>
            <Head title={t('public.posts.index_title')} />

            <div className="space-y-6">
                <section className="surface-card-strong px-6 py-8 sm:px-8">
                    <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                        {t('public.posts.eyebrow')}
                    </p>
                    <h1 className="mt-3 text-4xl font-semibold text-[color:var(--text)]">
                        {t('public.posts.index_title')}
                    </h1>
                    <p className="mt-4 max-w-3xl text-base leading-8 text-[color:var(--muted-strong)]">
                        {t('public.posts.description')}
                    </p>
                </section>

                {posts.data.length === 0 ? (
                    <EmptyState title={t('public.posts.title')} description={t('public.posts.empty')} />
                ) : (
                    <section className="grid gap-4 lg:grid-cols-3">
                        {posts.data.map((post) => (
                            <Link
                                key={post.id}
                                href={post.url}
                                className="surface-card group block overflow-hidden px-5 py-5 transition hover:-translate-y-1"
                            >
                                {post.cover_image_url ? (
                                    <img
                                        src={post.cover_image_url}
                                        alt={post.title}
                                        className="h-48 w-full rounded-2xl object-cover"
                                    />
                                ) : (
                                    <div className="h-48 rounded-2xl bg-[linear-gradient(140deg,var(--primary-soft),transparent)]" />
                                )}
                                <p className="mt-5 text-xs uppercase text-[color:var(--muted)]">
                                    {formatDate(post.published_at)}
                                    {post.author ? ` · ${post.author}` : ''}
                                </p>
                                <h2 className="mt-3 text-xl font-semibold text-[color:var(--text)] transition group-hover:text-[color:var(--primary)]">
                                    {post.title}
                                </h2>
                                <p className="mt-3 text-sm leading-7 text-[color:var(--muted-strong)]">
                                    {post.summary}
                                </p>
                            </Link>
                        ))}
                    </section>
                )}

                <Pagination links={posts.links} />
            </div>
        </PublicLayout>
    );
}
