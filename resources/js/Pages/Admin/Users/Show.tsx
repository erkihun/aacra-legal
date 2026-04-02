import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { translatePermissionName, translateRoleName } from '@/lib/access';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';

export default function UsersShow({ userItem, can }: any) {
    const { t, locale } = useI18n();
    const { formatDateTime } = useDateFormatter();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.users'), href: route('users.index') },
                { label: userItem.name },
            ]}
        >
            <Head title={userItem.name} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('users.eyebrow')}
                    title={userItem.name}
                    description={userItem.email}
                    action={
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('users.index')} />
                            {can.update ? (
                                <Link href={route('users.edit', userItem.id)} className="btn-base btn-primary focus-ring">
                                    {t('common.edit')}
                                </Link>
                            ) : null}
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={userItem.is_active ? 'active' : 'inactive'} />
                    {userItem.roles.map((role: string) => (
                        <StatusBadge key={role} value={role} label={translateRoleName(role, t)} />
                    ))}
                </div>

                <div className="grid gap-4 xl:grid-cols-[1.2fr,0.8fr]">
                    <SurfaceCard>
                        <div className="mb-4 flex items-center gap-4 rounded-[1.25rem] border border-[color:var(--border)] bg-[color:var(--surface-muted)] px-4 py-4">
                            <div className="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-[color:var(--primary-soft)] text-lg font-semibold text-[color:var(--primary)]">
                                {userItem.avatar_url ? (
                                    <img src={userItem.avatar_url} alt={userItem.name} className="h-full w-full object-cover" />
                                ) : (
                                    userItem.name.slice(0, 2).toUpperCase()
                                )}
                            </div>
                            <div>
                                <p className="text-lg font-semibold text-[color:var(--text)]">{userItem.name}</p>
                                <p className="mt-1 text-sm text-[color:var(--muted)]">{userItem.email}</p>
                            </div>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Detail label={t('users.employee_number')} value={userItem.employee_number} />
                            <Detail label={t('users.job_title')} value={userItem.job_title} />
                            <Detail label={t('profile.phone')} value={userItem.phone} />
                            <Detail label={t('users.national_id')} value={userItem.national_id} />
                            <Detail label={t('users.telegram_username')} value={userItem.telegram_username} />
                            <Detail
                                label={t('common.language')}
                                value={userItem.locale ? t(`settings.locale_options.${userItem.locale}`) : undefined}
                            />
                            <Detail
                                label={t('navigation.departments')}
                                value={(locale === 'am' ? userItem.department?.name_am : userItem.department?.name_en) ?? userItem.department?.name_en}
                            />
                            <Detail
                                label={t('navigation.teams')}
                                value={(locale === 'am' ? userItem.team?.name_am : userItem.team?.name_en) ?? userItem.team?.name_en}
                            />
                            <Detail label={t('users.last_login')} value={formatDateTime(userItem.last_login_at, t('common.not_available'))} />
                            <Detail label={t('users.created_at')} value={formatDateTime(userItem.created_at, t('common.not_available'))} />
                        </div>
                    </SurfaceCard>

                    <div className="grid gap-4">
                        <SurfaceCard>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('users.media_assets')}</h2>
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <ImageDetail label={t('users.signature')} src={userItem.signature_url} />
                                <ImageDetail label={t('users.stamp')} src={userItem.stamp_url} />
                            </div>
                        </SurfaceCard>

                        <SurfaceCard>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('roles.permissions')}</h2>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {userItem.permissions.length === 0 ? (
                                    <p className="text-sm text-[color:var(--muted)]">{t('roles.no_permissions')}</p>
                                ) : (
                                    userItem.permissions.map((permission: string) => (
                                        <span
                                            key={permission}
                                            className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold text-[color:var(--muted-strong)]"
                                        >
                                            {translatePermissionName(permission, t)}
                                        </span>
                                    ))
                                )}
                            </div>
                        </SurfaceCard>

                        <SurfaceCard>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('users.work_summary')}</h2>
                            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                <Detail label={t('users.requested_advisories')} value={String(userItem.stats.requested_advisories)} />
                                <Detail label={t('users.assigned_advisories')} value={String(userItem.stats.assigned_advisories)} />
                                <Detail label={t('users.registered_cases')} value={String(userItem.stats.registered_cases)} />
                                <Detail label={t('users.assigned_cases')} value={String(userItem.stats.assigned_cases)} />
                            </div>
                        </SurfaceCard>
                    </div>
                </div>
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    const { t } = useI18n();

    return (
        <div className="surface-muted px-4 py-4">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-sm font-semibold text-[color:var(--text)]">{value ?? t('common.not_available')}</p>
        </div>
    );
}

function ImageDetail({ label, src }: { label: string; src?: string | null }) {
    const { t } = useI18n();

    return (
        <div className="surface-muted px-4 py-4">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <div className="mt-3 flex min-h-40 items-center justify-center overflow-hidden rounded-[1rem] border border-[color:var(--border)] bg-[color:var(--surface-strong)] px-4 py-4">
                {src ? (
                    <img src={src} alt={label} className="max-h-28 object-contain" />
                ) : (
                    <p className="text-sm text-[color:var(--muted)]">{t('common.not_available')}</p>
                )}
            </div>
        </div>
    );
}
