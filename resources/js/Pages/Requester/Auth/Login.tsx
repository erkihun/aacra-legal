import GuestLayout from '@/Layouts/GuestLayout';
import { useI18n } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function RequesterLogin({ status }: { status?: string }) {
    const { t } = useI18n();
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('requester.login'));
    };

    return (
        <GuestLayout>
            <Head title={t('requester.login_title')} />

            <div className="w-full space-y-6">
                <div className="space-y-1">
                    <h2 className="text-2xl font-bold text-[color:var(--text)]">{t('requester.login_title')}</h2>
                    <p className="text-sm text-[color:var(--muted)]">{t('requester.login_description')}</p>
                </div>

                {status ? (
                    <div className="rounded-xl bg-[color:var(--success-soft)] p-3 text-sm text-[color:var(--success)]">
                        {status}
                    </div>
                ) : null}

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-[color:var(--text)]" htmlFor="email">
                            {t('common.email')}
                        </label>
                        <input
                            id="email"
                            type="email"
                            autoComplete="email"
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
                            {t('common.password')}
                        </label>
                        <input
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            className="input-ui mt-1"
                        />
                        {form.errors.password ? (
                            <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.password}</p>
                        ) : null}
                    </div>

                    <div className="flex items-center justify-between">
                        <label className="flex items-center gap-2 text-sm text-[color:var(--text)]">
                            <input
                                type="checkbox"
                                checked={form.data.remember}
                                onChange={(e) => form.setData('remember', e.target.checked)}
                                className="rounded border-[color:var(--border)]"
                            />
                            {t('common.remember_me')}
                        </label>

                        <a
                            href={route('requester.password.request')}
                            className="text-sm text-[color:var(--primary)] hover:underline"
                        >
                            {t('auth.forgot_password')}
                        </a>
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="btn-base btn-primary focus-ring w-full"
                    >
                        {form.processing ? `${t('common.signing_in')}...` : t('common.sign_in')}
                    </button>
                </form>

                <p className="text-center text-sm text-[color:var(--muted)]">
                    {t('requester.no_account')}{' '}
                    <a href={route('requester.register')} className="font-medium text-[color:var(--primary)] hover:underline">
                        {t('requester.create_account')}
                    </a>
                </p>
            </div>
        </GuestLayout>
    );
}
