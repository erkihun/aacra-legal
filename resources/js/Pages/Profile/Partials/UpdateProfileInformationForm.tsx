import PrimaryButton from '@/Components/PrimaryButton';
import FormField from '@/Components/Ui/FormField';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import TextInput from '@/Components/TextInput';
import { cn } from '@/lib/cn';
import { finishSuccessfulSubmission } from '@/lib/form-submission';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useEffect, useState } from 'react';

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

    const form = useForm({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        locale: user.locale ?? page.props.locale ?? 'en',
        national_id: user.national_id ?? '',
        telegram_username: user.telegram_username ?? '',
        avatar: null as File | null,
        signature: null as File | null,
        stamp: null as File | null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        form.transform((data) => ({
            ...data,
            _method: 'patch',
        }));

        form.post(route('profile.update'), {
            forceFormData: true,
            onSuccess: () => {
                finishSuccessfulSubmission(form, {
                    preserveScroll: true,
                    reset: ['avatar', 'signature', 'stamp'],
                });
            },
            onFinish: () => {
                form.transform((data) => data);
            },
        });
    };

    return (
        <SurfaceCard className={cn('overflow-hidden p-5 sm:p-6', className)}>
            <section>
                <header className="space-y-1">
                    <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('profile.information_title')}</h2>
                    <p className="text-sm text-[color:var(--muted-strong)]">{t('profile.information_help')}</p>
                </header>

                <form onSubmit={submit} className="mt-5 space-y-5">
                    <div className="grid gap-5 xl:grid-cols-[minmax(0,1.2fr),minmax(17rem,0.92fr)] xl:items-start">
                        <div className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField label={t('auth.name')} required error={form.errors.name}>
                                    <TextInput
                                        id="name"
                                        className="block w-full"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        required
                                        isFocused
                                        autoComplete="name"
                                    />
                                </FormField>
                                <FormField label={t('auth.email')} required error={form.errors.email}>
                                    <TextInput
                                        id="email"
                                        type="email"
                                        className="block w-full"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                        required
                                        autoComplete="username"
                                    />
                                </FormField>
                                <FormField label={t('profile.phone')} optional error={form.errors.phone}>
                                    <TextInput
                                        id="phone"
                                        className="block w-full"
                                        value={form.data.phone}
                                        onChange={(e) => form.setData('phone', e.target.value)}
                                    />
                                </FormField>
                                <FormField label={t('common.language')} required error={form.errors.locale}>
                                    <select
                                        id="locale"
                                        className="select-ui"
                                        value={form.data.locale}
                                        onChange={(e) => form.setData('locale', e.target.value)}
                                    >
                                        {locales.map((locale: { value: string; label: string }) => (
                                            <option key={locale.value} value={locale.value}>
                                                {locale.label}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>
                                <FormField label={t('users.national_id')} optional error={form.errors.national_id}>
                                    <TextInput
                                        id="national_id"
                                        className="block w-full"
                                        value={form.data.national_id}
                                        onChange={(e) => form.setData('national_id', formatNationalIdInput(e.target.value))}
                                        inputMode="numeric"
                                        maxLength={19}
                                        placeholder="1234 5678 9012 3456"
                                    />
                                </FormField>
                                <FormField
                                    label={t('users.telegram_username')}
                                    optional
                                    error={form.errors.telegram_username}
                                    hint={t('users.telegram_username_hint')}
                                >
                                    <TextInput
                                        id="telegram_username"
                                        className="block w-full"
                                        value={form.data.telegram_username}
                                        onChange={(e) => form.setData('telegram_username', e.target.value.trimStart())}
                                        placeholder="@john_doe"
                                    />
                                </FormField>
                            </div>

                            {mustVerifyEmail && user.email_verified_at === null ? (
                                <div className="surface-muted rounded-[1.25rem] px-4 py-3">
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
                        </div>

                        <div className="rounded-[1.5rem] border border-[color:var(--border)] bg-[color:var(--surface-muted)] p-4">
                            <div className="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-[color:var(--muted)]">
                                        {t('users.media_assets')}
                                    </h3>
                                </div>
                            </div>
                            <div className="space-y-3">
                                <ImageUploadField
                                    label={t('users.avatar')}
                                    hint={t('users.avatar_hint')}
                                    error={form.errors.avatar}
                                    existingUrl={user.avatar_url}
                                    file={form.data.avatar}
                                    accept="image/png,image/jpeg,image/webp"
                                    onChange={(file) => form.setData('avatar', file)}
                                />
                                <ImageUploadField
                                    label={t('users.signature')}
                                    hint={t('users.signature_hint')}
                                    error={form.errors.signature}
                                    existingUrl={user.signature_url}
                                    file={form.data.signature}
                                    accept="image/png,image/jpeg,image/webp"
                                    onChange={(file) => form.setData('signature', file)}
                                />
                                <ImageUploadField
                                    label={t('users.stamp')}
                                    hint={t('users.stamp_hint')}
                                    error={form.errors.stamp}
                                    existingUrl={user.stamp_url}
                                    file={form.data.stamp}
                                    accept="image/png,image/jpeg,image/webp"
                                    onChange={(file) => form.setData('stamp', file)}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center justify-end border-t border-[color:var(--border)] pt-4">
                        <PrimaryButton disabled={form.processing}>{t('profile.save')}</PrimaryButton>
                    </div>
                </form>
            </section>
        </SurfaceCard>
    );
}

function ImageUploadField({
    label,
    hint,
    error,
    existingUrl,
    file,
    accept,
    onChange,
}: {
    label: string;
    hint?: string;
    error?: string;
    existingUrl?: string | null;
    file: File | null;
    accept: string;
    onChange: (file: File | null) => void;
}) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(existingUrl ?? null);

    useEffect(() => {
        if (!file) {
            setPreviewUrl(existingUrl ?? null);

            return undefined;
        }

        const nextPreviewUrl = URL.createObjectURL(file);

        setPreviewUrl(nextPreviewUrl);

        return () => {
            URL.revokeObjectURL(nextPreviewUrl);
        };
    }, [existingUrl, file]);

    return (
        <FormField label={label} optional error={error} hint={hint}>
            <div className="rounded-[1.25rem] border border-[color:var(--border)] bg-[color:var(--surface)] p-3">
                <div className="surface-muted flex min-h-24 items-center justify-center overflow-hidden rounded-[1rem] px-3 py-3">
                    {previewUrl ? (
                        <img src={previewUrl} alt={label} className="max-h-20 object-contain" />
                    ) : (
                        <span className="text-sm text-[color:var(--muted)]">-</span>
                    )}
                </div>
                <div className="mt-3">
                    <input
                        type="file"
                        accept={accept}
                        onChange={(event) => onChange(event.target.files?.[0] ?? null)}
                        className="input-ui text-sm file:mr-3 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-[color:var(--primary)]"
                    />
                </div>
            </div>
        </FormField>
    );
}

function formatNationalIdInput(value: string) {
    const digits = value.replace(/\D+/g, '').slice(0, 16);

    return digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
}
