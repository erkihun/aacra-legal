import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { t } = useI18n();
    const flash = usePage().props.flash as { success?: string } | undefined;
    const confirmation = status ?? flash?.success;

    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title={t('auth.forgot_password_title')} />

            <div className="mb-4 text-sm text-[color:var(--muted-strong)]">{t('auth.forgot_password_help')}</div>

            {confirmation ? (
                <div className="mb-4 rounded-xl bg-[color:var(--success-soft)] p-3 text-sm font-medium text-[color:var(--success)]">
                    {confirmation}
                </div>
            ) : null}

            <form onSubmit={submit}>
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

                <div className="mt-6 flex items-center justify-between">
                    <Link
                        href={route('login')}
                        className="text-sm text-[color:var(--muted)] underline hover:text-[color:var(--text)]"
                    >
                        {t('auth.back_to_login')}
                    </Link>

                    <PrimaryButton disabled={processing}>
                        {t('auth.email_reset_link')}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
