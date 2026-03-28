import PrimaryButton from '@/Components/PrimaryButton';
import FormField from '@/Components/Ui/FormField';
import TextInput from '@/Components/TextInput';
import { useI18n } from '@/lib/i18n';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

export default function UpdatePasswordForm({
    className = '',
}: {
    className?: string;
}) {
    const { t } = useI18n();
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        errors,
        put,
        reset,
        processing,
        recentlySuccessful,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (formErrors) => {
                if (formErrors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (formErrors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-[color:var(--text)]">{t('profile.password_title')}</h2>
                <p className="mt-1 text-sm text-[color:var(--muted-strong)]">{t('profile.password_help')}</p>
            </header>

            <form onSubmit={updatePassword} className="mt-6 space-y-6">
                <FormField label={t('profile.current_password')} required error={errors.current_password}>
                    <TextInput
                        id="current_password"
                        ref={currentPasswordInput}
                        value={data.current_password}
                        onChange={(e) => setData('current_password', e.target.value)}
                        type="password"
                        className="block w-full"
                        autoComplete="current-password"
                    />
                </FormField>
                <FormField label={t('profile.new_password')} required error={errors.password}>
                    <TextInput
                        id="password"
                        ref={passwordInput}
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        type="password"
                        className="block w-full"
                        autoComplete="new-password"
                    />
                </FormField>
                <FormField label={t('auth.confirm_password')} required error={errors.password_confirmation}>
                    <TextInput
                        id="password_confirmation"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        type="password"
                        className="block w-full"
                        autoComplete="new-password"
                    />
                </FormField>

                <div className="flex flex-wrap items-center gap-4">
                    <PrimaryButton disabled={processing}>{t('profile.save')}</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-[color:var(--muted)]">{t('profile.saved')}</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
