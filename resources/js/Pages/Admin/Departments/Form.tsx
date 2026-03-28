import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function DepartmentForm({ departmentItem, canDelete }: any) {
    const { t } = useI18n();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const form = useForm({
        code: departmentItem?.code ?? '',
        name_en: departmentItem?.name_en ?? '',
        name_am: departmentItem?.name_am ?? '',
        is_active: departmentItem?.is_active ?? true,
    });

    const isEditing = !!departmentItem;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.departments'), href: route('departments.index') },
                { label: isEditing ? t('common.edit') : t('common.create_record') },
            ]}
        >
            <Head title={isEditing ? t('departments.edit_title') : t('departments.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('departments.eyebrow')}
                    title={isEditing ? t('departments.edit_title') : t('departments.create_title')}
                    description={isEditing ? t('departments.edit_description') : t('departments.create_description')}
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (isEditing) {
                            form.patch(route('departments.update', departmentItem.id));
                            return;
                        }

                        form.post(route('departments.store'));
                    }}
                    className="space-y-4"
                >
                    <SurfaceCard>
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('common.code')} required error={form.errors.code}>
                                <input value={form.data.code} onChange={(event) => form.setData('code', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('departments.name_en')} required error={form.errors.name_en}>
                                <input value={form.data.name_en} onChange={(event) => form.setData('name_en', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('departments.name_am')} required error={form.errors.name_am}>
                                <input value={form.data.name_am} onChange={(event) => form.setData('name_am', event.target.value)} className="input-ui" />
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
                title={t('departments.delete_title')}
                description={t('departments.delete_confirm')}
                confirmLabel={t('common.delete')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => router.delete(route('departments.destroy', departmentItem.id))}
            />
        </AuthenticatedLayout>
    );
}
