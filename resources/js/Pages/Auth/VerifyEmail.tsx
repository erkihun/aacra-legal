import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status: _status }: { status?: string }) {
    const { t } = useI18n();
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title={t('auth.verify_email')} />

            <div className="mb-4 text-sm text-[color:var(--muted-strong)]">{t('auth.verify_email_help')}</div>

            <form onSubmit={submit}>
                <div className="mt-4 flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        {t('auth.resend_verification')}
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="focus-ring rounded-md text-sm font-medium text-[color:var(--primary)] underline"
                    >
                        {t('common.logout')}
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
