import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import AppearancePreferencesForm from './Partials/AppearancePreferencesForm';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    const { t } = useI18n();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.profile') },
            ]}
        >
            <Head title={t('profile.title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('profile.eyebrow')}
                    title={t('profile.title')}
                    description={t('profile.description')}
                    action={<BackButton fallbackHref={route('dashboard')} />}
                />

                <div className="grid gap-4 xl:grid-cols-[minmax(0,1.35fr),minmax(20rem,0.9fr)]">
                    <div className="space-y-4">
                        <SurfaceCard>
                            <UpdateProfileInformationForm
                                mustVerifyEmail={mustVerifyEmail}
                                status={status}
                                className="max-w-2xl"
                            />
                        </SurfaceCard>

                        <SurfaceCard>
                            <UpdatePasswordForm className="max-w-2xl" />
                        </SurfaceCard>
                    </div>

                    <div className="space-y-4">
                        <SurfaceCard>
                            <AppearancePreferencesForm />
                        </SurfaceCard>

                        <SurfaceCard className="border-rose-300/30 bg-rose-500/5 dark:border-rose-500/20">
                            <DeleteUserForm className="max-w-2xl" />
                        </SurfaceCard>
                    </div>
                </div>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
