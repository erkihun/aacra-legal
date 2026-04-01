import PrimaryButton from '@/Components/PrimaryButton';
import FormField from '@/Components/Ui/FormField';
import TextInput from '@/Components/TextInput';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status: _status,
    className = '',
}: {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
}) {
    const { t } = useI18n();
    const page = usePage<PageProps>();
    const user = page.props.auth.user!;
    const locales = page.props.availableLocales ?? [];

    const { data, setData, patch, errors, processing } = useForm({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        locale: user.locale ?? page.props.locale ?? 'en',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-[color:var(--text)]">{t('profile.information_title')}</h2>
                <p className="mt-1 text-sm text-[color:var(--muted-strong)]">{t('profile.information_help')}</p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <FormField label={t('auth.name')} required error={errors.name}>
                    <TextInput
                        id="name"
                        className="block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />
                </FormField>
                <FormField label={t('auth.email')} required error={errors.email}>
                    <TextInput
                        id="email"
                        type="email"
                        className="block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />
                </FormField>
                <FormField label={t('profile.phone')} optional error={errors.phone}>
                    <TextInput
                        id="phone"
                        className="block w-full"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                    />
                </FormField>
                <FormField label={t('common.language')} required error={errors.locale}>
                    <select
                        id="locale"
                        className="select-ui"
                        value={data.locale}
                        onChange={(e) => setData('locale', e.target.value)}
                    >
                        {locales.map((locale: { value: string; label: string }) => (
                            <option key={locale.value} value={locale.value}>
                                {locale.label}
                            </option>
                        ))}
                    </select>
                </FormField>

                {mustVerifyEmail && user.email_verified_at === null ? (
                    <div className="surface-muted px-4 py-4">
                        <p className="text-sm text-[color:var(--muted-strong)]">
                            {t('profile.email_unverified')}{' '}
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="focus-ring rounded-md text-sm font-medium text-[color:var(--primary)] underline"
                            >
                                {t('profile.resend_verification')}
                            </Link>
                        </p>
                    </div>
                ) : null}

                <div className="flex flex-wrap items-center gap-4">
                    <PrimaryButton disabled={processing}>{t('profile.save')}</PrimaryButton>
                </div>
            </form>
        </section>
    );
}
