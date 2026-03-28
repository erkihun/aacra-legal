import FormField from '@/Components/Ui/FormField';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import { translateRoleName } from '@/lib/access';
import { useI18n } from '@/lib/i18n';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

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
        locale: userItem?.locale ?? 'en',
        department_id: userItem?.department?.id ?? '',
        team_id: userItem?.team?.id ?? '',
        role_name: userItem?.role_name ?? '',
        is_active: userItem?.is_active ?? true,
        password: '',
        password_confirmation: '',
    });

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();

        if (submit.method === 'post') {
            form.post(submit.url);
            return;
        }

        form.patch(submit.url);
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
