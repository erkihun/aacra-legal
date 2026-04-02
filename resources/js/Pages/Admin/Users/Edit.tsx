import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import BackButton from '@/Components/Ui/BackButton';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import UserForm from './UserForm';

export default function UsersEdit(props: any) {
    const { t } = useI18n();
    const [confirmOpen, setConfirmOpen] = useState(false);

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.users'), href: route('users.index') },
                { label: props.userItem.name },
            ]}
        >
            <Head title={props.userItem.name} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('users.eyebrow')}
                    title={t('users.edit_title')}
                    description={t('users.edit_description')}
                    action={<BackButton fallbackHref={route('users.index')} />}
                />

                <UserForm
                    userItem={props.userItem}
                    options={props.options}
                    localeOptions={props.localeOptions}
                    canManageRoles={props.canManageRoles}
                    submit={{ method: 'patch', url: route('users.update', props.userItem.id) }}
                />

                {props.canDelete ? (
                    <SurfaceCard className="border-rose-300/30 bg-rose-500/5 dark:border-rose-500/20">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('users.delete_title')}</h2>
                                <p className="mt-1 text-sm text-[color:var(--muted-strong)]">{t('users.delete_description')}</p>
                            </div>
                            <button type="button" className="btn-base btn-danger focus-ring" onClick={() => setConfirmOpen(true)}>
                                {t('common.delete')}
                            </button>
                        </div>
                    </SurfaceCard>
                ) : null}
            </PageContainer>

            <ConfirmationDialog
                open={confirmOpen}
                title={t('users.delete_title')}
                description={t('users.delete_confirm')}
                confirmLabel={t('common.delete')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => router.delete(route('users.destroy', props.userItem.id))}
            />
        </AuthenticatedLayout>
    );
}
