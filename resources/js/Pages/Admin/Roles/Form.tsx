import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import FormField from '@/Components/Ui/FormField';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import { translateRoleName } from '@/lib/access';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Link, router, useForm } from '@inertiajs/react';
import { useDeferredValue, useMemo, useState } from 'react';

type RoleFormProps = {
    roleItem: any;
    permissionGroups: Array<{
        key: string;
        label: string;
        items: Array<{
            name: string;
            label: string;
        }>;
    }>;
    submit: {
        method: 'post' | 'patch';
        url: string;
    };
};

export default function RoleForm({ roleItem, permissionGroups, submit }: RoleFormProps) {
    const { t } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [permissionSearch, setPermissionSearch] = useState('');
    const deferredPermissionSearch = useDeferredValue(permissionSearch.trim().toLowerCase());
    const form = useForm({
        name: roleItem?.name ?? '',
        permissions: roleItem?.permissions ?? ([] as string[]),
    });

    const isEditing = !!roleItem;
    const visiblePermissionGroups = useMemo(() => {
        if (deferredPermissionSearch === '') {
            return permissionGroups;
        }

        return permissionGroups
            .map((group) => ({
                ...group,
                items: group.items.filter((item) => {
                    const label = item.label.toLowerCase();
                    const name = item.name.toLowerCase();

                    return label.includes(deferredPermissionSearch) || name.includes(deferredPermissionSearch);
                }),
            }))
            .filter((group) => group.items.length > 0);
    }, [deferredPermissionSearch, permissionGroups]);

    const visiblePermissionNames = useMemo(
        () => visiblePermissionGroups.flatMap((group) => group.items.map((item) => item.name)),
        [visiblePermissionGroups],
    );
    const selectedVisibleCount = visiblePermissionNames.filter((permission) =>
        form.data.permissions.includes(permission),
    ).length;
    const totalPermissionCount = permissionGroups.reduce((count, group) => count + group.items.length, 0);

    const submitForm = () => {
        if (submit.method === 'patch') {
            form.patch(submit.url);
            return;
        }

        form.post(submit.url);
    };

    const togglePermission = (permissionName: string) => {
        form.setData(
            'permissions',
            form.data.permissions.includes(permissionName)
                ? form.data.permissions.filter((item) => item !== permissionName)
                : [...form.data.permissions, permissionName],
        );
    };

    const selectVisiblePermissions = () => {
        form.setData('permissions', Array.from(new Set([...form.data.permissions, ...visiblePermissionNames])));
    };

    const clearVisiblePermissions = () => {
        form.setData(
            'permissions',
            form.data.permissions.filter((permission) => !visiblePermissionNames.includes(permission)),
        );
    };

    return (
        <div className="space-y-4">
            <div className="grid gap-4 xl:grid-cols-[1.35fr,0.65fr]">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        submitForm();
                    }}
                    className="space-y-4"
                >
                    <SurfaceCard className="space-y-4">
                        {roleItem?.is_system ? (
                            <div className="rounded-2xl border border-amber-300/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-200">
                                {t('roles.system_name_description')}
                            </div>
                        ) : null}

                        {roleItem?.permissions_locked ? (
                            <div className="rounded-2xl border border-amber-300/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-200">
                                {t('roles.protected_description')}
                            </div>
                        ) : null}

                        <FormField
                            label={t('common.name')}
                            required
                            error={form.errors.name}
                            hint={t('roles.name_hint')}
                        >
                            <input
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                className="input-ui"
                                disabled={roleItem?.is_system}
                            />
                        </FormField>
                    </SurfaceCard>

                    <SurfaceCard className="space-y-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div className="space-y-1">
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                    {t('roles.permissions')}
                                </h2>
                                <p className="text-sm text-[color:var(--muted-strong)]">
                                    {t('roles.selected_permissions')}: {form.data.permissions.length} /{' '}
                                    {totalPermissionCount}
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    onClick={selectVisiblePermissions}
                                    className="btn-base btn-secondary focus-ring"
                                    disabled={roleItem?.permissions_locked || visiblePermissionNames.length === 0}
                                >
                                    {t('roles.select_visible')}
                                </button>
                                <button
                                    type="button"
                                    onClick={clearVisiblePermissions}
                                    className="btn-base btn-secondary focus-ring"
                                    disabled={roleItem?.permissions_locked || selectedVisibleCount === 0}
                                >
                                    {t('roles.clear_visible')}
                                </button>
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-[minmax(0,1fr),auto] md:items-end">
                            <FormField label={t('roles.permission_search_label')} optional>
                                <input
                                    value={permissionSearch}
                                    onChange={(event) => setPermissionSearch(event.target.value)}
                                    className="input-ui"
                                    placeholder={t('roles.permission_search_placeholder')}
                                />
                            </FormField>
                            <div className="surface-muted px-4 py-3 text-sm text-[color:var(--muted-strong)]">
                                {t('roles.visible_permissions')}: {selectedVisibleCount} /{' '}
                                {visiblePermissionNames.length}
                            </div>
                        </div>

                        {visiblePermissionGroups.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-[color:var(--border)] px-4 py-6 text-sm text-[color:var(--muted-strong)]">
                                {t('roles.permission_search_empty')}
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {visiblePermissionGroups.map((group) => (
                                    <section key={group.key} className="surface-muted px-4 py-4">
                                        <div className="flex items-center justify-between gap-3">
                                            <h3 className="text-sm font-semibold uppercase text-[color:var(--primary)]">
                                                {group.label}
                                            </h3>
                                            <span className="rounded-full bg-[color:var(--surface)] px-3 py-1 text-xs font-semibold text-[color:var(--muted-strong)]">
                                                {group.items.length}
                                            </span>
                                        </div>
                                        <div className="mt-4 grid gap-3 md:grid-cols-2">
                                            {group.items.map((item) => (
                                                <label
                                                    key={item.name}
                                                    className="flex items-start gap-3 rounded-2xl border px-4 py-3"
                                                    style={{ borderColor: 'var(--border)' }}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={form.data.permissions.includes(item.name)}
                                                        onChange={() => togglePermission(item.name)}
                                                        disabled={roleItem?.permissions_locked}
                                                        className="mt-1 h-4 w-4 rounded border-[color:var(--border)] text-[var(--primary)] focus:ring-[var(--primary)]"
                                                    />
                                                    <div>
                                                        <p className="text-sm font-medium text-[color:var(--text)]">
                                                            {item.label}
                                                        </p>
                                                        <p className="mt-1 text-xs text-[color:var(--muted)]">
                                                            {item.name}
                                                        </p>
                                                    </div>
                                                </label>
                                            ))}
                                        </div>
                                    </section>
                                ))}
                            </div>
                        )}

                        {form.errors.permissions ? (
                            <p className="text-sm text-rose-600 dark:text-rose-300">{form.errors.permissions}</p>
                        ) : null}

                        <div className="flex flex-wrap justify-end gap-3">
                            <button
                                type="submit"
                                className="btn-base btn-primary focus-ring"
                                disabled={form.processing}
                            >
                                {isEditing ? t('common.save_changes') : t('roles.create_action')}
                            </button>
                        </div>
                    </SurfaceCard>
                </form>

                <div className="space-y-4">
                    <SurfaceCard className="space-y-4">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('roles.role')}</h2>
                        <div className="space-y-3">
                            <SummaryRow
                                label={t('common.name')}
                                value={
                                    roleItem
                                        ? translateRoleName(roleItem.name, t)
                                        : form.data.name || t('common.not_set')
                                }
                            />
                            <SummaryRow
                                label={t('roles.permissions')}
                                value={String(form.data.permissions.length)}
                            />
                            {roleItem?.created_at ? (
                                <SummaryRow
                                    label={t('roles.created_at')}
                                    value={formatDateTime(roleItem.created_at, t('common.not_set'))}
                                />
                            ) : null}
                        </div>
                    </SurfaceCard>

                    {roleItem ? (
                        <SurfaceCard>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                {t('roles.assigned_users')}
                            </h2>
                            <div className="mt-4 space-y-3">
                                {roleItem.users.length === 0 ? (
                                    <p className="text-sm text-[color:var(--muted)]">{t('roles.no_users')}</p>
                                ) : (
                                    roleItem.users.map((user: any) => (
                                        <div key={user.id} className="surface-muted px-4 py-4">
                                            <Link
                                                href={route('users.show', user.id)}
                                                className="font-medium text-[color:var(--text)] hover:text-[color:var(--primary)]"
                                            >
                                                {user.name}
                                            </Link>
                                            <p className="mt-1 text-sm text-[color:var(--muted)]">
                                                {user.email}
                                            </p>
                                        </div>
                                    ))
                                )}
                            </div>
                        </SurfaceCard>
                    ) : null}

                    {roleItem && !roleItem.is_system ? (
                        <SurfaceCard className="border-rose-300/30 bg-rose-500/5 dark:border-rose-500/20">
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                        {t('roles.delete_title')}
                                    </h2>
                                    <p className="mt-1 text-sm text-[color:var(--muted-strong)]">
                                        {roleItem.users.length > 0
                                            ? t('roles.delete_in_use_description')
                                            : t('roles.delete_description')}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    className="btn-base btn-danger focus-ring"
                                    onClick={() => setConfirmOpen(true)}
                                >
                                    {t('common.delete')}
                                </button>
                            </div>
                        </SurfaceCard>
                    ) : null}
                </div>
            </div>

            <ConfirmationDialog
                open={confirmOpen}
                title={t('roles.delete_title')}
                description={t('roles.delete_confirm')}
                confirmLabel={t('common.delete')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => {
                    if (!roleItem) {
                        return;
                    }

                    router.delete(route('roles.destroy', roleItem.id));
                }}
            />
        </div>
    );
}

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="surface-muted flex items-center justify-between gap-3 px-4 py-3">
            <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">{label}</p>
            <p className="text-right text-sm font-semibold text-[color:var(--text)]">{value}</p>
        </div>
    );
}
