import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import RoleForm from './Form';

export default function RolesCreate(props: any) {
    const { t } = useI18n();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.roles'), href: route('roles.index') },
                { label: t('roles.new_role') },
            ]}
        >
            <Head title={t('roles.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('roles.eyebrow')}
                    title={t('roles.create_title')}
                    description={t('roles.create_description')}
                    action={<BackButton fallbackHref={route('roles.index')} />}
                />
                <RoleForm
                    roleItem={props.roleItem}
                    permissionGroups={props.permissionGroups}
                    submit={{ method: 'post', url: route('roles.store') }}
                />
            </PageContainer>
        </AuthenticatedLayout>
    );
}
