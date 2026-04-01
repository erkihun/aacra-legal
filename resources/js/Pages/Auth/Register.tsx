import PrimaryButton from '@/Components/PrimaryButton';
import FormField from '@/Components/Ui/FormField';
import GuestLayout from '@/Layouts/GuestLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type RegisterProps = {
    departments: Array<{
        id: string;
        name_en: string;
        name_am?: string | null;
    }>;
};

export default function Register({ departments }: RegisterProps) {
    const { t, locale } = useI18n();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        department_id: departments[0]?.id ?? '',
        job_title: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('register'));
    };

    return (
        <GuestLayout>
            <Head title={t('public.actions.create_account')} />

            <div className="mb-6">
                <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                    {t('public.registration.eyebrow')}
                </p>
                <h1 className="mt-2 text-3xl font-semibold text-[color:var(--text)]">
                    {t('public.registration.title')}
                </h1>
                <p className="mt-3 text-sm leading-7 text-[color:var(--muted-strong)]">
                    {t('public.registration.description')}
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label={t('auth.name')} required error={errors.name}>
                        <input
                            id="name"
                            name="name"
                            value={data.name}
                            className="input-ui"
                            autoComplete="name"
                            onChange={(event) => setData('name', event.target.value)}
                            required
                        />
                    </FormField>

                    <FormField label={t('auth.email')} required error={errors.email}>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className="input-ui"
                            autoComplete="username"
                            onChange={(event) => setData('email', event.target.value)}
                            required
                        />
                    </FormField>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label={t('profile.phone')} optional error={errors.phone}>
                        <input
                            id="phone"
                            name="phone"
                            value={data.phone}
                            className="input-ui"
                            autoComplete="tel"
                            onChange={(event) => setData('phone', event.target.value)}
                        />
                    </FormField>

                    <FormField label={t('auth.position_title')} optional error={errors.job_title}>
                        <input
                            id="job_title"
                            name="job_title"
                            value={data.job_title}
                            className="input-ui"
                            autoComplete="organization-title"
                            onChange={(event) => setData('job_title', event.target.value)}
                        />
                    </FormField>
                </div>

                <FormField label={t('auth.department')} required error={errors.department_id}>
                    <select
                        id="department_id"
                        name="department_id"
                        value={data.department_id}
                        className="select-ui"
                        onChange={(event) => setData('department_id', event.target.value)}
                        required
                    >
                        {departments.map((department) => (
                            <option key={department.id} value={department.id}>
                                {(locale === 'am' ? department.name_am : department.name_en) || department.name_en}
                            </option>
                        ))}
                    </select>
                </FormField>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label={t('auth.password')} required error={errors.password}>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="input-ui"
                            autoComplete="new-password"
                            onChange={(event) => setData('password', event.target.value)}
                            required
                        />
                    </FormField>

                    <FormField label={t('auth.confirm_password')} required error={errors.password_confirmation}>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className="input-ui"
                            autoComplete="new-password"
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                            required
                        />
                    </FormField>
                </div>
                <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[color:var(--border)] pt-5">
                    <Link
                        href={route('login')}
                        className="focus-ring rounded-md text-sm font-medium text-[color:var(--primary)] underline"
                    >
                        {t('auth.already_registered')}
                    </Link>

                    <PrimaryButton disabled={processing}>
                        {t('public.actions.create_account')}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
