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
    const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
    const [banConfirmOpen, setBanConfirmOpen] = useState(false);

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

                {props.canBan ? (
                    <SurfaceCard className="border-amber-300/30 bg-amber-500/5 dark:border-amber-500/20">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                    {props.userItem.is_active ? t('users.ban_title') : t('users.activate_title')}
                                </h2>
                                <p className="mt-1 text-sm text-[color:var(--muted-strong)]">
                                    {props.userItem.is_active ? t('users.ban_description') : t('users.activate_description')}
                                </p>
                            </div>
                            <button
                                type="button"
                                className={props.userItem.is_active ? 'btn-base btn-secondary focus-ring' : 'btn-base btn-primary focus-ring'}
                                onClick={() => setBanConfirmOpen(true)}
                            >
                                {props.userItem.is_active ? t('common.ban') : t('common.activate')}
                            </button>
                        </div>
                    </SurfaceCard>
                ) : null}

                {props.canDelete ? (
                    <SurfaceCard className="border-rose-300/30 bg-rose-500/5 dark:border-rose-500/20">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('users.delete_title')}</h2>
                                <p className="mt-1 text-sm text-[color:var(--muted-strong)]">{t('users.delete_description')}</p>
                            </div>
                            <button type="button" className="btn-base btn-danger focus-ring" onClick={() => setDeleteConfirmOpen(true)}>
                                {t('common.delete')}
                            </button>
                        </div>
                    </SurfaceCard>
                ) : null}
            </PageContainer>

            <ConfirmationDialog
                open={banConfirmOpen}
                title={props.userItem.is_active ? t('users.ban_title') : t('users.activate_title')}
                description={props.userItem.is_active ? t('users.ban_confirm') : t('users.activate_confirm')}
                confirmLabel={props.userItem.is_active ? t('common.ban') : t('common.activate')}
                onCancel={() => setBanConfirmOpen(false)}
                onConfirm={() =>
                    router.patch(
                        route(props.userItem.is_active ? 'users.ban' : 'users.activate', props.userItem.id),
                        {
                            redirect_to:
                                typeof window === 'undefined'
                                    ? route('users.edit', props.userItem.id)
                                    : `${window.location.pathname}${window.location.search}`,
                        },
                    )
                }
            />

            <ConfirmationDialog
                open={deleteConfirmOpen}
                title={t('users.delete_title')}
                description={t('users.delete_confirm')}
                confirmLabel={t('common.delete')}
                onCancel={() => setDeleteConfirmOpen(false)}
                onConfirm={() => router.delete(route('users.destroy', props.userItem.id))}
            />
        </AuthenticatedLayout>
    );
}
