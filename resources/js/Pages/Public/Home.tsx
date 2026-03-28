import HeroSlider from '@/Components/Public/HeroSlider';
import EmptyState from '@/Components/Ui/EmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

type HomeProps = {
    content: {
        hero_eyebrow: string;
        hero_title: string;
        hero_description: string;
        about_title: string;
        about_description: string;
        services_title: string;
        services_description: string;
        service_advisory_title: string;
        service_advisory_description: string;
        service_case_support_title: string;
        service_case_support_description: string;
        service_policy_title: string;
        service_policy_description: string;
        process_title: string;
        process_description: string;
        process_step_one_title: string;
        process_step_one_description: string;
        process_step_two_title: string;
        process_step_two_description: string;
        process_step_three_title: string;
        process_step_three_description: string;
        process_step_four_title: string;
        process_step_four_description: string;
        posts_title: string;
        posts_description: string;
        cta_title: string;
        cta_description: string;
        cta_primary_label: string;
        cta_secondary_label: string;
        contact_title: string;
        contact_description: string;
        contact_hours_value: string;
    };
    slides: Array<{
        title: string;
        subtitle: string;
        button_label: string;
        button_url: string;
        image_url?: string | null;
    }>;
    featuredPosts: Array<{
        id: string;
        title: string;
        slug: string;
        summary: string;
        published_at?: string | null;
        author?: string | null;
        cover_image_url?: string | null;
        url: string;
    }>;
    stats: {
        departments: number;
        workflows: number;
        locales: number;
    };
};

export default function Home({ content, slides, featuredPosts, stats }: HomeProps) {
    const { t } = useI18n();
    const { props } = usePage<PageProps>();
    const { formatDate } = useDateFormatter();
    const { auth, appMeta } = props;
    const permissions = auth.user?.permissions ?? [];
    const canCreateAdvisory = permissions.includes('advisory.create') || permissions.includes('advisory-requests.create');
    const canViewAdvisory = permissions.includes('advisory.view_any') || permissions.includes('advisory.view_own') || permissions.includes('advisory-requests.view');
    const isAuthenticated = auth.user !== null;

    const sanitizedSlides = isAuthenticated
        ? slides
        : slides.map((slide) => {
            if (isAuthEntryLink(slide.button_url)) {
                return {
                    ...slide,
                    button_label: '',
                    button_url: '',
                };
            }

            return slide;
        });

    const primaryCtaHref = isAuthenticated
        ? canCreateAdvisory
            ? route('advisory.create')
            : canViewAdvisory
                ? route('advisory.index')
                : route(appMeta.default_dashboard_route)
        : route('posts.index');
    const primaryCtaLabel = isAuthenticated
        ? canCreateAdvisory
            ? t('public.actions.submit_request')
            : t('public.actions.open_portal')
        : t('public.actions.read_updates');
    const secondaryCtaHref = isAuthenticated
        ? canViewAdvisory
            ? route('advisory.index')
            : route(appMeta.default_dashboard_route)
        : `${route('home')}#services`;
    const secondaryCtaLabel = isAuthenticated
        ? canViewAdvisory
            ? t('public.actions.track_requests')
            : t('navigation.dashboard')
        : t('public.nav.services');
    const tertiaryCtaHref = isAuthenticated ? route('posts.index') : `${route('home')}#contact`;
    const tertiaryCtaLabel = isAuthenticated ? t('public.actions.read_updates') : t('public.nav.contact');

    return (
        <PublicLayout title={t('public.home.title')} description={content.hero_description}>
            <Head title={t('public.home.title')} />

            <div className="space-y-10 lg:space-y-14">
                <HeroSlider
                    slides={sanitizedSlides}
                    eyebrow={content.hero_eyebrow}
                    primaryCtaHref={primaryCtaHref}
                    primaryCtaLabel={primaryCtaLabel}
                    secondaryCtaHref={secondaryCtaHref}
                    secondaryCtaLabel={secondaryCtaLabel}
                    tertiaryCtaHref={tertiaryCtaHref}
                    tertiaryCtaLabel={tertiaryCtaLabel}
                    previousLabel={t('public.slider.previous')}
                    nextLabel={t('public.slider.next')}
                    metrics={[
                        { label: t('public.metrics.departments'), value: String(stats.departments) },
                        { label: t('public.metrics.workflows'), value: String(stats.workflows) },
                        { label: t('public.metrics.locales'), value: `${t('settings.locale_options.en')} / ${t('settings.locale_options.am')}` },
                    ]}
                />

                <section id="about" className="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                    <div className="surface-card px-6 py-7 sm:px-8 sm:py-8 lg:px-10">
                        <SectionEyebrow>{t('public.about.eyebrow')}</SectionEyebrow>
                        <h2 className="mt-4 max-w-2xl text-3xl font-semibold tracking-tight text-[color:var(--text)]">
                            {appMeta.legal_department_name || content.about_title}
                        </h2>
                        <p className="mt-5 max-w-2xl text-base leading-8 text-[color:var(--muted-strong)]">
                            {appMeta.tagline || appMeta.organization_description || content.about_description}
                        </p>

                        <div className="mt-8 grid gap-4 sm:grid-cols-3">
                            <StatPanel label={t('public.metrics.departments')} value={String(stats.departments)} />
                            <StatPanel label={t('public.metrics.workflows')} value={String(stats.workflows)} />
                            <StatPanel label={t('public.metrics.locales')} value={`${stats.locales}`} />
                        </div>
                    </div>

                    <div className="grid gap-4">
                        <InsightPanel
                            title={t('public.highlights.secure_title')}
                            description={t('public.highlights.secure_description')}
                        />
                        <InsightPanel
                            title={t('public.highlights.accountable_title')}
                            description={t('public.highlights.accountable_description')}
                        />
                        <InsightPanel
                            title={t('public.highlights.bilingual_title')}
                            description={t('public.highlights.bilingual_description')}
                        />
                    </div>
                </section>

                <section id="services" className="rounded-[2rem] border border-[color:var(--border)] bg-[linear-gradient(180deg,color-mix(in_srgb,var(--surface-strong)_90%,transparent),color-mix(in_srgb,var(--surface)_96%,transparent))] px-6 py-8 shadow-[var(--shadow-soft)] sm:px-8 lg:px-10 lg:py-10">
                    <div className="flex flex-wrap items-end justify-between gap-5">
                        <div className="max-w-3xl">
                            <SectionEyebrow>{t('public.services.eyebrow')}</SectionEyebrow>
                            <h2 className="mt-4 text-3xl font-semibold tracking-tight text-[color:var(--text)]">
                                {content.services_title}
                            </h2>
                            <p className="mt-4 text-base leading-8 text-[color:var(--muted-strong)]">
                                {content.services_description}
                            </p>
                        </div>
                        <Link href={isAuthenticated ? primaryCtaHref : `${route('home')}#contact`} className="btn-base btn-primary focus-ring">
                            {isAuthenticated ? primaryCtaLabel : t('public.nav.contact')}
                        </Link>
                    </div>

                    <div className="mt-8 grid gap-4 xl:grid-cols-3">
                        <ServiceCard
                            accent="from-emerald-400/30 to-teal-500/10"
                            index="01"
                            title={content.service_advisory_title}
                            description={content.service_advisory_description}
                        />
                        <ServiceCard
                            accent="from-sky-400/30 to-cyan-500/10"
                            index="02"
                            title={content.service_case_support_title}
                            description={content.service_case_support_description}
                        />
                        <ServiceCard
                            accent="from-amber-400/30 to-orange-500/10"
                            index="03"
                            title={content.service_policy_title}
                            description={content.service_policy_description}
                        />
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-[0.95fr,1.05fr]">
                    <div className="surface-card px-6 py-7 sm:px-8 lg:px-10">
                        <SectionEyebrow>{t('public.process.eyebrow')}</SectionEyebrow>
                        <h2 className="mt-4 text-3xl font-semibold tracking-tight text-[color:var(--text)]">
                            {content.process_title}
                        </h2>
                        <p className="mt-4 text-sm leading-7 text-[color:var(--muted-strong)]">
                            {content.process_description}
                        </p>
                        <div className="mt-6 grid gap-3">
                            <ProcessStep number="01" title={content.process_step_one_title} description={content.process_step_one_description} />
                            <ProcessStep number="02" title={content.process_step_two_title} description={content.process_step_two_description} />
                            <ProcessStep number="03" title={content.process_step_three_title} description={content.process_step_three_description} />
                            <ProcessStep number="04" title={content.process_step_four_title} description={content.process_step_four_description} />
                        </div>
                    </div>

                    <div className="surface-card-strong overflow-hidden">
                        <div className="grid h-full gap-0 lg:grid-cols-[0.9fr,1.1fr]">
                            <div className="bg-[linear-gradient(145deg,rgba(15,118,110,0.22),rgba(14,165,233,0.12))] px-6 py-7 sm:px-8 lg:px-10">
                                <SectionEyebrow>{t('public.portal.eyebrow')}</SectionEyebrow>
                                <h2 className="mt-4 text-2xl font-semibold tracking-tight text-[color:var(--text)]">
                                    {t('public.portal.quick_access_title')}
                                </h2>
                                <p className="mt-4 text-sm leading-7 text-[color:var(--muted-strong)]">
                                    {t('public.portal.description')}
                                </p>
                                <div className="mt-6 flex flex-wrap gap-3">
                                    {isAuthenticated ? (
                                        <>
                                            <Link href={primaryCtaHref} className="btn-base btn-primary focus-ring">
                                                {primaryCtaLabel}
                                            </Link>
                                            <Link href={secondaryCtaHref} className="btn-base btn-secondary focus-ring">
                                                {secondaryCtaLabel}
                                            </Link>
                                        </>
                                    ) : (
                                        <>
                                            <Link href={route('posts.index')} className="btn-base btn-primary focus-ring">
                                                {t('public.actions.read_updates')}
                                            </Link>
                                            <Link href={`${route('home')}#services`} className="btn-base btn-secondary focus-ring">
                                                {t('public.nav.services')}
                                            </Link>
                                        </>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-4 px-6 py-7 sm:px-8 lg:px-10">
                                <PortalCard title={t('public.portal.submit_card_title')} description={t('public.portal.submit_request_hint')} />
                                <PortalCard title={t('public.portal.track_card_title')} description={t('public.portal.track_request_hint')} />
                                <PortalCard title={t('public.portal.notify_card_title')} description={t('public.portal.notifications_hint')} />
                            </div>
                        </div>
                    </div>
                </section>

                <section className="rounded-[2rem] border border-[color:var(--border)] bg-[color:var(--surface-strong)] px-6 py-8 shadow-[var(--shadow-soft)] sm:px-8 lg:px-10 lg:py-10">
                    <div className="flex flex-wrap items-end justify-between gap-5">
                        <div className="max-w-3xl">
                            <SectionEyebrow>{t('public.posts.eyebrow')}</SectionEyebrow>
                            <h2 className="mt-4 text-3xl font-semibold tracking-tight text-[color:var(--text)]">
                                {content.posts_title}
                            </h2>
                            <p className="mt-4 text-base leading-8 text-[color:var(--muted-strong)]">
                                {content.posts_description}
                            </p>
                        </div>
                        <Link href={route('posts.index')} className="btn-base btn-secondary focus-ring">
                            {t('public.posts.view_all')}
                        </Link>
                    </div>

                    <div className="mt-8 grid gap-4 xl:grid-cols-3">
                        {featuredPosts.length === 0 ? (
                            <div className="xl:col-span-3">
                                <EmptyState title={t('public.posts.title')} description={t('public.posts.empty')} />
                            </div>
                        ) : (
                            featuredPosts.map((post) => (
                                <Link
                                    key={post.id}
                                    href={post.url}
                                    className="group overflow-hidden rounded-[1.6rem] border border-[color:var(--border)] bg-[color:var(--surface)] transition hover:-translate-y-1 hover:shadow-[var(--shadow-soft)]"
                                >
                                    {post.cover_image_url ? (
                                        <img src={post.cover_image_url} alt={post.title} className="h-56 w-full object-cover" />
                                    ) : (
                                        <div className="flex h-56 items-end bg-[linear-gradient(145deg,rgba(15,118,110,0.18),rgba(14,165,233,0.12))] p-6">
                                            <span className="rounded-full bg-white/80 px-3 py-1 text-xs font-semibold uppercase text-slate-900">
                                                {t('public.posts.update_label')}
                                            </span>
                                        </div>
                                    )}

                                    <div className="px-5 py-5">
                                        <p className="text-xs uppercase text-[color:var(--muted)]">
                                            {formatDate(post.published_at)}
                                            {post.author ? ` · ${post.author}` : ''}
                                        </p>
                                        <h3 className="mt-3 text-xl font-semibold leading-8 text-[color:var(--text)] transition group-hover:text-[color:var(--primary)]">
                                            {post.title}
                                        </h3>
                                        <p className="mt-3 text-sm leading-7 text-[color:var(--muted-strong)]">
                                            {post.summary}
                                        </p>
                                    </div>
                                </Link>
                            ))
                        )}
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.05fr,0.95fr]">
                    <div className="rounded-[2rem] border border-[color:var(--border)] bg-[linear-gradient(135deg,color-mix(in_srgb,var(--primary-soft)_70%,transparent),color-mix(in_srgb,var(--surface-strong)_92%,transparent))] px-6 py-8 shadow-[var(--shadow-soft)] sm:px-8 lg:px-10">
                        <SectionEyebrow>{t('public.cta.eyebrow')}</SectionEyebrow>
                        <h2 className="mt-4 max-w-2xl text-3xl font-semibold tracking-tight text-[color:var(--text)]">
                            {content.cta_title}
                        </h2>
                        <p className="mt-4 max-w-2xl text-base leading-8 text-[color:var(--muted-strong)]">
                            {content.cta_description}
                        </p>
                        <div className="mt-7 flex flex-wrap gap-3">
                            {isAuthenticated ? (
                                <>
                                    <Link href={primaryCtaHref} className="btn-base btn-primary focus-ring">
                                        {content.cta_primary_label || primaryCtaLabel}
                                    </Link>
                                    <Link href={secondaryCtaHref} className="btn-base btn-secondary focus-ring">
                                        {content.cta_secondary_label || secondaryCtaLabel}
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <Link href={route('posts.index')} className="btn-base btn-primary focus-ring">
                                        {content.cta_primary_label || t('public.actions.read_updates')}
                                    </Link>
                                    <Link href={`${route('home')}#contact`} className="btn-base btn-secondary focus-ring">
                                        {content.cta_secondary_label || t('public.nav.contact')}
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>

                    <div id="contact" className="surface-card px-6 py-8 sm:px-8 lg:px-10">
                        <SectionEyebrow>{t('public.contact.eyebrow')}</SectionEyebrow>
                        <h2 className="mt-4 text-3xl font-semibold tracking-tight text-[color:var(--text)]">
                            {content.contact_title}
                        </h2>
                        <p className="mt-4 text-sm leading-7 text-[color:var(--muted-strong)]">
                            {content.contact_description}
                        </p>
                        <div className="mt-6 grid gap-4 sm:grid-cols-2">
                            <ContactTile label={t('public.contact.organization')} value={appMeta.organization.name || appMeta.organization_name || '-'} />
                            <ContactTile label={t('public.contact.email')} value={appMeta.support.email || '-'} />
                            <ContactTile label={t('public.contact.phone')} value={appMeta.support.phone || '-'} />
                            <ContactTile label={t('public.contact.hours')} value={content.contact_hours_value} />
                            <ContactTile label={t('settings.fields.address')} value={appMeta.organization.address || '-'} />
                            <ContactTile label={t('settings.fields.legal_department_name')} value={appMeta.legal_department_name || '-'} />
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

function isAuthEntryLink(url: string | null | undefined): boolean {
    if (!url) {
        return false;
    }

    return ['/login', '/register'].some((path) => url === path || url.endsWith(path));
}

function SectionEyebrow({ children }: { children: string }) {
    return (
        <p className="inline-flex rounded-full border border-[color:var(--border)] bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold uppercase text-[color:var(--primary)]">
            {children}
        </p>
    );
}

function StatPanel({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-[1.4rem] border border-[color:var(--border)] bg-[color:var(--surface-muted)] px-4 py-4">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-[color:var(--text)]">{value}</p>
        </div>
    );
}

function InsightPanel({ title, description }: { title: string; description: string }) {
    return (
        <div className="surface-card px-6 py-6 sm:px-7">
            <h3 className="text-lg font-semibold text-[color:var(--text)]">{title}</h3>
            <p className="mt-3 text-sm leading-7 text-[color:var(--muted-strong)]">{description}</p>
        </div>
    );
}

function ServiceCard({
    accent,
    index,
    title,
    description,
}: {
    accent: string;
    index: string;
    title: string;
    description: string;
}) {
    return (
        <article className="relative overflow-hidden rounded-[1.8rem] border border-[color:var(--border)] bg-[color:var(--surface)] px-6 py-6 shadow-[var(--shadow-soft)]">
            <div className={`absolute inset-x-0 top-0 h-24 bg-gradient-to-br ${accent}`} />
            <div className="relative">
                <div className="inline-flex rounded-full border border-[color:var(--border)] bg-[color:var(--surface-strong)] px-3 py-1 text-xs font-semibold uppercase text-[color:var(--primary)]">
                    {index}
                </div>
                <h3 className="mt-5 text-xl font-semibold text-[color:var(--text)]">{title}</h3>
                <p className="mt-4 text-sm leading-7 text-[color:var(--muted-strong)]">{description}</p>
            </div>
        </article>
    );
}

function ProcessStep({
    number,
    title,
    description,
}: {
    number: string;
    title: string;
    description: string;
}) {
    return (
        <div className="flex gap-4 rounded-[1.5rem] border border-[color:var(--border)] bg-[color:var(--surface-muted)] px-4 py-4">
            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[color:var(--primary-soft)] text-sm font-bold text-[color:var(--primary)]">
                {number}
            </div>
            <div>
                <h3 className="text-base font-semibold text-[color:var(--text)]">{title}</h3>
                <p className="mt-2 text-sm leading-7 text-[color:var(--muted-strong)]">{description}</p>
            </div>
        </div>
    );
}

function PortalCard({ title, description }: { title: string; description: string }) {
    return (
        <div className="rounded-[1.5rem] border border-[color:var(--border)] bg-[color:var(--surface-muted)] px-5 py-5">
            <h3 className="text-base font-semibold text-[color:var(--text)]">{title}</h3>
            <p className="mt-2 text-sm leading-7 text-[color:var(--muted-strong)]">{description}</p>
        </div>
    );
}

function ContactTile({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-[1.5rem] border border-[color:var(--border)] bg-[color:var(--surface-muted)] px-4 py-4">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-base font-semibold text-[color:var(--text)]">{value}</p>
        </div>
    );
}
