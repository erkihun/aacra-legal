import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { t } = useI18n();
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title={t('auth.login')} />

            <div className="mb-6">
                <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                    {t('public.portal.eyebrow')}
                </p>
                <h1 className="mt-2 text-3xl font-semibold text-[color:var(--text)]">
                    {t('public.portal.title')}
                </h1>
                <p className="mt-3 text-sm leading-7 text-[color:var(--muted-strong)]">
                    {t('public.portal.description')}
                </p>
            </div>

            {status ? (
                <div className="mb-4 rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                    {status}
                </div>
            ) : null}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value={t('auth.email')} />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value={t('auth.password')} />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', (e.target.checked || false) as false)
                            }
                        />
                        <span className="ms-2 text-sm text-[color:var(--muted-strong)]">{t('auth.remember_me')}</span>
                    </label>
                </div>

                <div className="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-col gap-2">
                        {canResetPassword ? (
                            <Link
                                href={route('password.request')}
                                className="focus-ring rounded-md text-sm font-medium text-[color:var(--primary)] underline"
                            >
                                {t('auth.forgot_password')}
                            </Link>
                        ) : null}
                        <Link
                            href={route('register')}
                            className="focus-ring rounded-md text-sm font-medium text-[color:var(--primary)] underline"
                        >
                            {t('public.portal.create_account_hint')}
                        </Link>
                    </div>
                    <PrimaryButton disabled={processing}>
                        {t('auth.login')}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
