import GuestLayout from '@/Layouts/GuestLayout';
import { useI18n } from '@/lib/i18n';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function RequesterForgotPassword({ status }: { status?: string }) {
    const { t } = useI18n();
    const flash = usePage().props.flash as { success?: string } | undefined;
    const confirmation = status ?? flash?.success;

    const form = useForm({ email: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('requester.password.email'));
    };

    return (
        <GuestLayout>
            <Head title={t('requester.forgot_password_title')} />

            <div className="w-full space-y-6">
                <div className="space-y-1">
                    <h2 className="text-2xl font-bold text-[color:var(--text)]">
                        {t('requester.forgot_password_title')}
                    </h2>
                    <p className="text-sm text-[color:var(--muted)]">
                        {t('requester.forgot_password_description')}
                    </p>
                </div>

                {confirmation ? (
                    <div className="rounded-xl bg-[color:var(--success-soft)] p-3 text-sm font-medium text-[color:var(--success)]">
                        {confirmation}
                    </div>
                ) : null}

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

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="btn-base btn-primary focus-ring w-full"
                    >
                        {t('requester.send_reset_link')}
                    </button>
                </form>

                <p className="text-center text-sm">
                    <a
                        href={route('requester.login')}
                        className="font-medium text-[color:var(--primary)] hover:underline"
                    >
                        {t('requester.back_to_login')}
                    </a>
                </p>
            </div>
        </GuestLayout>
    );
}
