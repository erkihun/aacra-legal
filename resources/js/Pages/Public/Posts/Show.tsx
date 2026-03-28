import PublicLayout from '@/Layouts/PublicLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

type PostsShowProps = {
    post: {
        id: string;
        title: string;
        summary: string;
        body: string;
        published_at?: string | null;
        author?: string | null;
        cover_image_url?: string | null;
    };
    relatedPosts: Array<{
        id: string;
        title: string;
        summary: string;
        published_at?: string | null;
        url: string;
    }>;
};

export default function PostsShow({ post, relatedPosts }: PostsShowProps) {
    const { t } = useI18n();
    const { formatDate } = useDateFormatter();
    const { props } = usePage<PageProps>();
    const isAuthenticated = props.auth.user !== null;

    return (
        <PublicLayout title={post.title} description={post.summary}>
            <Head title={post.title} />

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.4fr),22rem]">
                <article className="surface-card-strong overflow-hidden">
                    {post.cover_image_url ? (
                        <img src={post.cover_image_url} alt={post.title} className="h-64 w-full object-cover sm:h-80" />
                    ) : null}

                    <div className="px-6 py-8 sm:px-8">
                        <Link href={route('posts.index')} className="text-sm font-semibold text-[color:var(--primary)]">
                            {t('public.posts.back_to_updates')}
                        </Link>

                        <p className="mt-5 text-xs font-semibold uppercase text-[color:var(--muted)]">
                            {formatDate(post.published_at)}
                            {post.author ? ` · ${post.author}` : ''}
                        </p>
                        <h1 className="mt-3 text-4xl font-semibold leading-tight text-[color:var(--text)]">
                            {post.title}
                        </h1>
                        <p className="mt-5 text-lg leading-8 text-[color:var(--muted-strong)]">
                            {post.summary}
                        </p>

                        <div className="mt-8 whitespace-pre-wrap text-base leading-8 text-[color:var(--text)]">
                            {post.body}
                        </div>
                    </div>
                </article>

                <aside className="space-y-4">
                    <div className="surface-card px-5 py-5">
                        <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                            {t('public.cta.eyebrow')}
                        </p>
                        <h2 className="mt-3 text-xl font-semibold text-[color:var(--text)]">
                            {t('public.cta.side_title')}
                        </h2>
                        <p className="mt-3 text-sm leading-7 text-[color:var(--muted-strong)]">
                            {t('public.cta.side_description')}
                        </p>
                        <div className="mt-5 grid gap-3">
                            {isAuthenticated ? (
                                <>
                                    <Link href={route('dashboard')} className="btn-base btn-primary focus-ring">
                                        {t('navigation.dashboard')}
                                    </Link>
                                    <Link href={route('advisory.index')} className="btn-base btn-secondary focus-ring">
                                        {t('public.actions.track_requests')}
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <Link href={route('posts.index')} className="btn-base btn-primary focus-ring">
                                        {t('public.actions.read_updates')}
                                    </Link>
                                    <Link href={`${route('home')}#contact`} className="btn-base btn-secondary focus-ring">
                                        {t('public.nav.contact')}
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="surface-card px-5 py-5">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">
                            {t('public.posts.related_title')}
                        </h2>
                        <div className="mt-4 space-y-3">
                            {relatedPosts.map((item) => (
                                <Link key={item.id} href={item.url} className="surface-muted block px-4 py-4 transition hover:-translate-y-0.5">
                                    <p className="text-xs uppercase text-[color:var(--muted)]">
                                        {formatDate(item.published_at)}
                                    </p>
                                    <p className="mt-2 font-semibold text-[color:var(--text)]">{item.title}</p>
                                    <p className="mt-2 text-sm leading-6 text-[color:var(--muted-strong)]">{item.summary}</p>
                                </Link>
                            ))}
                        </div>
                    </div>
                </aside>
            </div>
        </PublicLayout>
    );
}
