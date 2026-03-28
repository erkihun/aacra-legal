import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { translateRoleName } from '@/lib/access';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';

export default function RolesEdit({ roleItem, permissionGroups }: any) {
    const { t } = useI18n();
    const form = useForm({
        permissions: roleItem.permissions as string[],
    });

    const togglePermission = (permissionName: string) => {
        form.setData(
            'permissions',
            form.data.permissions.includes(permissionName)
                ? form.data.permissions.filter((item) => item !== permissionName)
                : [...form.data.permissions, permissionName],
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.roles'), href: route('roles.index') },
                { label: translateRoleName(roleItem.name, t) },
            ]}
        >
            <Head title={translateRoleName(roleItem.name, t)} />

            <PageContainer>
                <SectionHeader eyebrow={t('roles.eyebrow')} title={translateRoleName(roleItem.name, t)} description={t('roles.edit_description')} />

                <div className="grid gap-4 xl:grid-cols-[1.35fr,0.65fr]">
                    <SurfaceCard>
                        {roleItem.is_protected ? (
                            <div className="mb-4 rounded-2xl border border-amber-300/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-200">
                                {t('roles.protected_description')}
                            </div>
                        ) : null}
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.patch(route('roles.update', roleItem.id));
                            }}
                            className="space-y-4"
                        >
                            {permissionGroups.map((group: any) => (
                                <section key={group.key} className="surface-muted px-4 py-4">
                                    <h2 className="text-sm font-semibold uppercase text-[color:var(--primary)]">
                                        {group.label}
                                    </h2>
                                    <div className="mt-4 grid gap-3 md:grid-cols-2">
                                        {group.items.map((item: any) => (
                                            <label key={item.name} className="flex items-start gap-3 rounded-2xl border px-4 py-3" style={{ borderColor: 'var(--border)' }}>
                                                <input
                                                    type="checkbox"
                                                    checked={form.data.permissions.includes(item.name)}
                                                    onChange={() => togglePermission(item.name)}
                                                    disabled={roleItem.is_protected}
                                                    className="mt-1 h-4 w-4 rounded border-[color:var(--border)] text-[var(--primary)] focus:ring-[var(--primary)]"
                                                />
                                                <div>
                                                    <p className="text-sm font-medium text-[color:var(--text)]">{item.label}</p>
                                                    <p className="mt-1 text-xs text-[color:var(--muted)]">{item.name}</p>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                </section>
                            ))}
                            <div className="flex justify-end">
                                <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing || roleItem.is_protected}>
                                    {t('common.save_changes')}
                                </button>
                            </div>
                        </form>
                    </SurfaceCard>

                    <SurfaceCard>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('roles.assigned_users')}</h2>
                        <div className="mt-4 space-y-3">
                            {roleItem.users.length === 0 ? (
                                <p className="text-sm text-[color:var(--muted)]">{t('roles.no_users')}</p>
                            ) : (
                                roleItem.users.map((user: any) => (
                                    <div key={user.id} className="surface-muted px-4 py-4">
                                        <Link href={route('users.show', user.id)} className="font-medium text-[color:var(--text)] hover:text-[color:var(--primary)]">
                                            {user.name}
                                        </Link>
                                        <p className="mt-1 text-sm text-[color:var(--muted)]">{user.email}</p>
                                    </div>
                                ))
                            )}
                        </div>
                    </SurfaceCard>
                </div>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
