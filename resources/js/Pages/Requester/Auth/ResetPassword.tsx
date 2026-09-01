import GuestLayout from '@/Layouts/GuestLayout';
import { useI18n } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function RequesterResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const { t } = useI18n();
    const form = useForm({
        token,
        email: email ?? '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('requester.password.store'));
    };

    return (
        <GuestLayout>
            <Head title={t('requester.reset_password_title')} />

            <div className="w-full space-y-6">
                <div className="space-y-1">
                    <h2 className="text-2xl font-bold text-[color:var(--text)]">
                        {t('requester.reset_password_title')}
                    </h2>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-[color:var(--text)]" htmlFor="email">
                            {t('auth.email')}
                        </label>
                        <input
                            id="email"
                            type="email"
                            autoComplete="username"
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            className="input-ui mt-1"
                        />
                        {form.errors.email ? (
                            <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.email}</p>
                        ) : null}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-[color:var(--text)]" htmlFor="password">
                            {t('auth.password')}
                        </label>
                        <input
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            autoFocus
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            className="input-ui mt-1"
                        />
                        {form.errors.password ? (
                            <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.password}</p>
                        ) : null}
                    </div>

                    <div>
                        <label
                            className="block text-sm font-medium text-[color:var(--text)]"
                            htmlFor="password_confirmation"
                        >
                            {t('auth.confirm_password')}
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password_confirmation}
                            onChange={(e) => form.setData('password_confirmation', e.target.value)}
                            className="input-ui mt-1"
                        />
                        {form.errors.password_confirmation ? (
                            <p className="mt-1 text-xs text-[color:var(--danger)]">
                                {form.errors.password_confirmation}
                            </p>
                        ) : null}
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="btn-base btn-primary focus-ring w-full"
                    >
                        {t('auth.reset_password')}
                    </button>
                </form>
            </div>
        </GuestLayout>
    );
}
