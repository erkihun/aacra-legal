import GuestLayout from '@/Layouts/GuestLayout';
import { useI18n } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type RegisterProps = {
    departments: Array<{ id: string; name_en: string; name_am: string }>;
};

export default function RequesterRegister({ departments }: RegisterProps) {
    const { t, locale } = useI18n();
    const form = useForm({
        full_name: '',
        email: '',
        phone: '',
        job_title: '',
        department_id: departments[0]?.id ?? '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('requester.register'));
    };

    return (
        <GuestLayout>
            <Head title={t('requester.register_title')} />

            <div className="w-full space-y-6">
                <div className="space-y-1">
                    <h2 className="text-2xl font-bold text-[color:var(--text)]">{t('requester.register_title')}</h2>
                    <p className="text-sm text-[color:var(--muted)]">{t('requester.register_description')}</p>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-[color:var(--text)]">{t('requester.full_name')}</label>
                        <input
                            type="text"
                            value={form.data.full_name}
                            onChange={(e) => form.setData('full_name', e.target.value)}
                            className="input-ui mt-1"
                            autoComplete="name"
                        />
                        {form.errors.full_name ? <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.full_name}</p> : null}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-[color:var(--text)]">{t('common.email')}</label>
                        <input
                            type="email"
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            className="input-ui mt-1"
                            autoComplete="email"
                        />
                        {form.errors.email ? <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.email}</p> : null}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-sm font-medium text-[color:var(--text)]">{t('requester.phone_optional')}</label>
                            <input
                                type="tel"
                                value={form.data.phone}
                                onChange={(e) => form.setData('phone', e.target.value)}
                                className="input-ui mt-1"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-[color:var(--text)]">{t('requester.job_title_optional')}</label>
                            <input
                                type="text"
                                value={form.data.job_title}
                                onChange={(e) => form.setData('job_title', e.target.value)}
                                className="input-ui mt-1"
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-[color:var(--text)]">{t('requester.department')}</label>
                        <select
                            value={form.data.department_id}
                            onChange={(e) => form.setData('department_id', e.target.value)}
                            className="select-ui mt-1"
                        >
                            {departments.map((dept) => (
                                <option key={dept.id} value={dept.id}>
                                    {locale === 'am' ? dept.name_am || dept.name_en : dept.name_en}
                                </option>
                            ))}
                        </select>
                        {form.errors.department_id ? <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.department_id}</p> : null}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-[color:var(--text)]">{t('common.password')}</label>
                        <input
                            type="password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            className="input-ui mt-1"
                            autoComplete="new-password"
                        />
                        {form.errors.password ? <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.password}</p> : null}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-[color:var(--text)]">{t('common.confirm_password')}</label>
                        <input
                            type="password"
                            value={form.data.password_confirmation}
                            onChange={(e) => form.setData('password_confirmation', e.target.value)}
                            className="input-ui mt-1"
                            autoComplete="new-password"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="btn-base btn-primary focus-ring w-full"
                    >
                        {form.processing ? `${t('requester.creating_account')}...` : t('requester.create_account')}
                    </button>
                </form>

                <p className="text-center text-sm text-[color:var(--muted)]">
                    {t('requester.have_account')}{' '}
                    <a href={route('requester.login')} className="font-medium text-[color:var(--primary)] hover:underline">
                        {t('common.sign_in')}
                    </a>
                </p>
            </div>
        </GuestLayout>
    );
}
