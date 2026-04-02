import FormField from '@/Components/Ui/FormField';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import { translateRoleName } from '@/lib/access';
import { useI18n } from '@/lib/i18n';
import { useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';

type Option = {
    id?: string;
    name?: string;
    name_en?: string;
    name_am?: string;
};

type UserFormProps = {
    userItem: {
        id: string;
        employee_number?: string | null;
        name: string;
        email: string;
        phone?: string | null;
        job_title?: string | null;
        avatar_url?: string | null;
        signature_url?: string | null;
        stamp_url?: string | null;
        national_id?: string | null;
        telegram_username?: string | null;
        locale?: string | null;
        is_active: boolean;
        department?: { id: string } | null;
        team?: { id: string } | null;
        role_name?: string | null;
    } | null;
    options: {
        departments: Option[];
        teams: Option[];
        roles: Array<{ id: string; name: string }>;
    };
    localeOptions: Array<{ value: string; label: string }>;
    canManageRoles: boolean;
    submit: {
        method: 'post' | 'patch';
        url: string;
    };
};

export default function UserForm({
    userItem,
    options,
    localeOptions,
    canManageRoles,
    submit,
}: UserFormProps) {
    const { t, locale } = useI18n();
    const form = useForm({
        employee_number: userItem?.employee_number ?? '',
        name: userItem?.name ?? '',
        email: userItem?.email ?? '',
        phone: userItem?.phone ?? '',
        job_title: userItem?.job_title ?? '',
        national_id: userItem?.national_id ?? '',
        telegram_username: userItem?.telegram_username ?? '',
        locale: userItem?.locale ?? 'en',
        department_id: userItem?.department?.id ?? '',
        team_id: userItem?.team?.id ?? '',
        role_name: userItem?.role_name ?? '',
        is_active: userItem?.is_active ?? true,
        avatar: null as File | null,
        signature: null as File | null,
        stamp: null as File | null,
        password: '',
        password_confirmation: '',
    });

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();

        if (submit.method === 'post') {
            form.post(submit.url, {
                forceFormData: true,
            });
            return;
        }

        form.transform((data) => ({
            ...data,
            _method: 'patch',
        }));

        form.post(submit.url, {
            forceFormData: true,
            onFinish: () => {
                form.transform((data) => data);
            },
        });
    };

    return (
        <form onSubmit={onSubmit} className="space-y-4">
            <SurfaceCard>
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label={t('users.employee_number')} optional error={form.errors.employee_number}>
                        <input
                            value={form.data.employee_number}
                            onChange={(event) => form.setData('employee_number', event.target.value)}
                            className="input-ui"
                        />
                    </FormField>
                    <FormField label={t('auth.name')} required error={form.errors.name}>
                        <input
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            className="input-ui"
                        />
                    </FormField>
                    <FormField label={t('auth.email')} required error={form.errors.email}>
                        <input
                            type="email"
                            value={form.data.email}
                            onChange={(event) => form.setData('email', event.target.value)}
                            className="input-ui"
                        />
                    </FormField>
                    <FormField label={t('profile.phone')} optional error={form.errors.phone}>
                        <input
                            value={form.data.phone}
                            onChange={(event) => form.setData('phone', event.target.value)}
                            className="input-ui"
                        />
                    </FormField>
                    <FormField label={t('users.job_title')} optional error={form.errors.job_title}>
                        <input
                            value={form.data.job_title}
                            onChange={(event) => form.setData('job_title', event.target.value)}
                            className="input-ui"
                        />
                    </FormField>
                    <FormField label={t('users.national_id')} optional error={form.errors.national_id}>
                        <input
                            value={form.data.national_id}
                            onChange={(event) => form.setData('national_id', formatNationalIdInput(event.target.value))}
                            inputMode="numeric"
                            maxLength={19}
                            placeholder="1234 5678 9012 3456"
                            className="input-ui"
                        />
                    </FormField>
                    <FormField
                        label={t('users.telegram_username')}
                        optional
                        error={form.errors.telegram_username}
                        hint={t('users.telegram_username_hint')}
                    >
                        <input
                            value={form.data.telegram_username}
                            onChange={(event) => form.setData('telegram_username', event.target.value.trimStart())}
                            placeholder="@john_doe"
                            className="input-ui"
                        />
                    </FormField>
                    <FormField label={t('common.language')} required error={form.errors.locale}>
                        <select
                            value={form.data.locale}
                            onChange={(event) => form.setData('locale', event.target.value)}
                            className="select-ui"
                        >
                            {localeOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </FormField>
                    <FormField label={t('navigation.departments')} optional error={form.errors.department_id}>
                        <select
                            value={form.data.department_id}
                            onChange={(event) => form.setData('department_id', event.target.value)}
                            className="select-ui"
                        >
                            <option value="">{t('common.unassigned')}</option>
                            {options.departments.map((department) => (
                                <option key={department.id} value={department.id}>
                                    {(locale === 'am' ? department.name_am : department.name_en) ?? department.name_en}
                                </option>
                            ))}
                        </select>
                    </FormField>
                    <FormField label={t('navigation.teams')} optional error={form.errors.team_id}>
                        <select
                            value={form.data.team_id}
                            onChange={(event) => form.setData('team_id', event.target.value)}
                            className="select-ui"
                        >
                            <option value="">{t('common.unassigned')}</option>
                            {options.teams.map((team) => (
                                <option key={team.id} value={team.id}>
                                    {(locale === 'am' ? team.name_am : team.name_en) ?? team.name_en}
                                </option>
                            ))}
                        </select>
                    </FormField>
                    {canManageRoles ? (
                        <FormField label={t('roles.role')} required error={form.errors.role_name}>
                            <select
                                value={form.data.role_name}
                                onChange={(event) => form.setData('role_name', event.target.value)}
                                className="select-ui"
                            >
                                <option value="">{t('common.not_set')}</option>
                                {options.roles.map((role) => (
                                    <option key={role.id} value={role.name}>
                                        {translateRoleName(role.name, t)}
                                    </option>
                                ))}
                            </select>
                        </FormField>
                    ) : null}
                    <FormField label={t('common.status')} required error={form.errors.is_active as string | undefined}>
                        <select
                            value={form.data.is_active ? '1' : '0'}
                            onChange={(event) => form.setData('is_active', event.target.value === '1')}
                            className="select-ui"
                        >
                            <option value="1">{t('common.active')}</option>
                            <option value="0">{t('common.inactive')}</option>
                        </select>
                    </FormField>
                </div>
            </SurfaceCard>

            <SurfaceCard>
                <div className="grid gap-4 lg:grid-cols-3">
                    <ImageUploadField
                        label={t('users.avatar')}
                        hint={t('users.avatar_hint')}
                        error={form.errors.avatar}
                        existingUrl={userItem?.avatar_url}
                        file={form.data.avatar}
                        accept="image/png,image/jpeg,image/webp"
                        onChange={(file) => form.setData('avatar', file)}
                    />
                    <ImageUploadField
                        label={t('users.signature')}
                        hint={t('users.signature_hint')}
                        error={form.errors.signature}
                        existingUrl={userItem?.signature_url}
                        file={form.data.signature}
                        accept="image/png,image/jpeg,image/webp"
                        onChange={(file) => form.setData('signature', file)}
                    />
                    <ImageUploadField
                        label={t('users.stamp')}
                        hint={t('users.stamp_hint')}
                        error={form.errors.stamp}
                        existingUrl={userItem?.stamp_url}
                        file={form.data.stamp}
                        accept="image/png,image/jpeg,image/webp"
                        onChange={(file) => form.setData('stamp', file)}
                    />
                </div>
            </SurfaceCard>

            <SurfaceCard>
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label={t('auth.password')} required={!userItem} optional={!!userItem} error={form.errors.password}>
                        <input
                            type="password"
                            value={form.data.password}
                            onChange={(event) => form.setData('password', event.target.value)}
                            className="input-ui"
                        />
                    </FormField>
                    <FormField
                        label={t('auth.confirm_password')}
                        required={!userItem}
                        optional={!!userItem}
                        error={form.errors.password_confirmation}
                    >
                        <input
                            type="password"
                            value={form.data.password_confirmation}
                            onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            className="input-ui"
                        />
                    </FormField>
                </div>
            </SurfaceCard>

            <div className="flex flex-wrap justify-end gap-3">
                <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>
                    {userItem ? t('common.save_changes') : t('common.create_record')}
                </button>
            </div>
        </form>
    );
}

function ImageUploadField({
    label,
    hint,
    error,
    existingUrl,
    file,
    accept,
    onChange,
}: {
    label: string;
    hint?: string;
    error?: string;
    existingUrl?: string | null;
    file: File | null;
    accept: string;
    onChange: (file: File | null) => void;
}) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(existingUrl ?? null);

    useEffect(() => {
        if (!file) {
            setPreviewUrl(existingUrl ?? null);

            return;
        }

        const nextPreviewUrl = URL.createObjectURL(file);

        setPreviewUrl(nextPreviewUrl);

        return () => {
            URL.revokeObjectURL(nextPreviewUrl);
        };
    }, [existingUrl, file]);

    return (
        <FormField label={label} optional error={error} hint={hint}>
            <div className="space-y-3">
                <div className="surface-muted flex min-h-44 items-center justify-center overflow-hidden px-4 py-4">
                    {previewUrl ? (
                        <img src={previewUrl} alt={label} className="max-h-36 object-contain" />
                    ) : (
                        <span className="text-sm text-[color:var(--muted)]">-</span>
                    )}
                </div>
                <input
                    type="file"
                    accept={accept}
                    onChange={(event) => onChange(event.target.files?.[0] ?? null)}
                    className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                />
            </div>
        </FormField>
    );
}

function formatNationalIdInput(value: string) {
    const digits = value.replace(/\D+/g, '').slice(0, 16);

    return digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
}
