import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function AdvisoryCategoryForm({ categoryItem, canDelete }: any) {
    const { t } = useI18n();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const form = useForm({
        code: categoryItem?.code ?? '',
        name_en: categoryItem?.name_en ?? '',
        name_am: categoryItem?.name_am ?? '',
        description: categoryItem?.description ?? '',
        is_active: categoryItem?.is_active ?? true,
    });
    const isEditing = !!categoryItem;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.advisory_categories'), href: route('advisory-categories.index') },
                { label: isEditing ? t('common.edit') : t('common.create_record') },
            ]}
        >
            <Head title={isEditing ? t('advisory_categories.edit_title') : t('advisory_categories.create_title')} />
            <PageContainer>
                <SectionHeader eyebrow={t('advisory_categories.eyebrow')} title={isEditing ? t('advisory_categories.edit_title') : t('advisory_categories.create_title')} description={isEditing ? t('advisory_categories.edit_description') : t('advisory_categories.create_description')} />
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (isEditing) {
                            form.patch(route('advisory-categories.update', categoryItem.id));
                            return;
                        }
                        form.post(route('advisory-categories.store'));
                    }}
                    className="space-y-4"
                >
                    <SurfaceCard>
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('common.code')} required error={form.errors.code}><input value={form.data.code} onChange={(event) => form.setData('code', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('advisory_categories.name_en')} required error={form.errors.name_en}><input value={form.data.name_en} onChange={(event) => form.setData('name_en', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('advisory_categories.name_am')} required error={form.errors.name_am}><input value={form.data.name_am} onChange={(event) => form.setData('name_am', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('common.status')} required error={form.errors.is_active as string | undefined}>
                                <select value={form.data.is_active ? '1' : '0'} onChange={(event) => form.setData('is_active', event.target.value === '1')} className="select-ui">
                                    <option value="1">{t('common.active')}</option>
                                    <option value="0">{t('common.inactive')}</option>
                                </select>
                            </FormField>
                        </div>
                        <div className="mt-4">
                            <FormField label={t('common.description')} optional error={form.errors.description}>
                                <textarea value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} rows={5} className="textarea-ui" />
                            </FormField>
                        </div>
                    </SurfaceCard>
                    <div className="flex flex-wrap justify-between gap-3">
                        <div>{isEditing && canDelete ? <button type="button" className="btn-base btn-danger focus-ring" onClick={() => setConfirmOpen(true)}>{t('common.delete')}</button> : null}</div>
                        <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>{isEditing ? t('common.save_changes') : t('common.create_record')}</button>
                    </div>
                </form>
            </PageContainer>
            <ConfirmationDialog open={confirmOpen} title={t('advisory_categories.delete_title')} description={t('advisory_categories.delete_confirm')} confirmLabel={t('common.delete')} onCancel={() => setConfirmOpen(false)} onConfirm={() => router.delete(route('advisory-categories.destroy', categoryItem.id))} />
        </AuthenticatedLayout>
    );
}
