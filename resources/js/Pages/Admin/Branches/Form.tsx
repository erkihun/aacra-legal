import BackButton from '@/Components/Ui/BackButton';
import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function BranchForm({ branchItem, canDelete }: any) {
    const { t } = useI18n();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const form = useForm({
        code: branchItem?.code ?? '',
        name_en: branchItem?.name_en ?? '',
        name_am: branchItem?.name_am ?? '',
        region: branchItem?.region ?? '',
        city: branchItem?.city ?? '',
        address: branchItem?.address ?? '',
        phone: branchItem?.phone ?? '',
        email: branchItem?.email ?? '',
        manager_name: branchItem?.manager_name ?? '',
        notes: branchItem?.notes ?? '',
        is_head_office: branchItem?.is_head_office ?? false,
        is_active: branchItem?.is_active ?? true,
    });

    const isEditing = !!branchItem;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.branches'), href: route('branches.index') },
                { label: isEditing ? t('common.edit') : t('common.create_record') },
            ]}
        >
            <Head title={isEditing ? t('branches.edit_title') : t('branches.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('branches.eyebrow')}
                    title={isEditing ? t('branches.edit_title') : t('branches.create_title')}
                    description={isEditing ? t('branches.edit_description') : t('branches.create_description')}
                    action={<BackButton fallbackHref={route('branches.index')} />}
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (isEditing) {
                            form.patch(route('branches.update', branchItem.id));
                            return;
                        }

                        form.post(route('branches.store'));
                    }}
                    className="space-y-4"
                >
                    <SurfaceCard>
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('common.code')} required error={form.errors.code}>
                                <input value={form.data.code} onChange={(event) => form.setData('code', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('branches.branch_name_en')} required error={form.errors.name_en}>
                                <input value={form.data.name_en} onChange={(event) => form.setData('name_en', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('branches.branch_name_am')} optional error={form.errors.name_am}>
                                <input value={form.data.name_am} onChange={(event) => form.setData('name_am', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('branches.region')} optional error={form.errors.region}>
                                <input value={form.data.region} onChange={(event) => form.setData('region', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('branches.city')} optional error={form.errors.city}>
                                <input value={form.data.city} onChange={(event) => form.setData('city', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('branches.manager_name')} optional error={form.errors.manager_name}>
                                <input value={form.data.manager_name} onChange={(event) => form.setData('manager_name', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('branches.phone')} optional error={form.errors.phone}>
                                <input value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('branches.email')} optional error={form.errors.email}>
                                <input value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('branches.address')} optional error={form.errors.address} className="md:col-span-2">
                                <textarea value={form.data.address} onChange={(event) => form.setData('address', event.target.value)} className="textarea-ui min-h-24" />
                            </FormField>
                            <FormField label={t('branches.notes')} optional error={form.errors.notes} className="md:col-span-2">
                                <textarea value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} className="textarea-ui min-h-28" />
                            </FormField>
                            <FormField label={t('branches.branch_type')} required error={form.errors.is_head_office as string | undefined}>
                                <select value={form.data.is_head_office ? '1' : '0'} onChange={(event) => form.setData('is_head_office', event.target.value === '1')} className="select-ui">
                                    <option value="0">{t('branches.regular_branch')}</option>
                                    <option value="1">{t('branches.head_office')}</option>
                                </select>
                            </FormField>
                            <FormField label={t('common.status')} required error={form.errors.is_active as string | undefined}>
                                <select value={form.data.is_active ? '1' : '0'} onChange={(event) => form.setData('is_active', event.target.value === '1')} className="select-ui">
                                    <option value="1">{t('common.active')}</option>
                                    <option value="0">{t('common.inactive')}</option>
                                </select>
                            </FormField>
                        </div>
                    </SurfaceCard>

                    <div className="flex flex-wrap justify-between gap-3">
                        <div>
                            {isEditing && canDelete ? (
                                <button type="button" className="btn-base btn-danger focus-ring" onClick={() => setConfirmOpen(true)}>
                                    {t('common.delete')}
                                </button>
                            ) : null}
                        </div>
                        <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>
                            {isEditing ? t('common.save_changes') : t('common.create_record')}
                        </button>
                    </div>
                </form>
            </PageContainer>

            <ConfirmationDialog
                open={confirmOpen}
                title={t('branches.delete_title')}
                description={t('branches.delete_confirm')}
                confirmLabel={t('common.delete')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => router.delete(route('branches.destroy', branchItem.id))}
            />
        </AuthenticatedLayout>
    );
}
