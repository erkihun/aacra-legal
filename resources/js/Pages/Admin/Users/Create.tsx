import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import UserForm from './UserForm';

export default function UsersCreate(props: any) {
    const { t } = useI18n();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.users'), href: route('users.index') },
                { label: t('users.new_user') },
            ]}
        >
            <Head title={t('users.new_user')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('users.eyebrow')}
                    title={t('users.create_title')}
                    description={t('users.create_description')}
                    action={<BackButton fallbackHref={route('users.index')} />}
                />
                <UserForm
                    userItem={props.userItem}
                    options={props.options}
                    localeOptions={props.localeOptions}
                    canManageRoles={props.canManageRoles}
                    submit={{ method: 'post', url: route('users.store') }}
                />
            </PageContainer>
        </AuthenticatedLayout>
    );
}
