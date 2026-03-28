import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function LegalCaseTypeForm({ caseTypeItem, canDelete }: any) {
    const { t } = useI18n();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const form = useForm({
        code: caseTypeItem?.code ?? '',
        name_en: caseTypeItem?.name_en ?? '',
        name_am: caseTypeItem?.name_am ?? '',
        description: caseTypeItem?.description ?? '',
        is_active: caseTypeItem?.is_active ?? true,
    });
    const isEditing = !!caseTypeItem;

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('navigation.dashboard'), href: route('dashboard') }, { label: t('navigation.legal_case_types'), href: route('legal-case-types.index') }, { label: isEditing ? t('common.edit') : t('common.create_record') }]}>
            <Head title={isEditing ? t('legal_case_types.edit_title') : t('legal_case_types.create_title')} />
            <PageContainer>
                <SectionHeader eyebrow={t('legal_case_types.eyebrow')} title={isEditing ? t('legal_case_types.edit_title') : t('legal_case_types.create_title')} description={isEditing ? t('legal_case_types.edit_description') : t('legal_case_types.create_description')} />
                <form onSubmit={(event) => { event.preventDefault(); if (isEditing) { form.patch(route('legal-case-types.update', caseTypeItem.id)); return; } form.post(route('legal-case-types.store')); }} className="space-y-4">
                    <SurfaceCard>
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('common.code')} required error={form.errors.code}><input value={form.data.code} onChange={(event) => form.setData('code', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('legal_case_types.name_en')} required error={form.errors.name_en}><input value={form.data.name_en} onChange={(event) => form.setData('name_en', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('legal_case_types.name_am')} required error={form.errors.name_am}><input value={form.data.name_am} onChange={(event) => form.setData('name_am', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('common.status')} required error={form.errors.is_active as string | undefined}><select value={form.data.is_active ? '1' : '0'} onChange={(event) => form.setData('is_active', event.target.value === '1')} className="select-ui"><option value="1">{t('common.active')}</option><option value="0">{t('common.inactive')}</option></select></FormField>
                        </div>
                        <div className="mt-4">
                            <FormField label={t('common.description')} optional error={form.errors.description}><textarea value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} rows={5} className="textarea-ui" /></FormField>
                        </div>
                    </SurfaceCard>
                    <div className="flex flex-wrap justify-between gap-3">
                        <div>{isEditing && canDelete ? <button type="button" className="btn-base btn-danger focus-ring" onClick={() => setConfirmOpen(true)}>{t('common.delete')}</button> : null}</div>
                        <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>{isEditing ? t('common.save_changes') : t('common.create_record')}</button>
                    </div>
                </form>
            </PageContainer>
            <ConfirmationDialog open={confirmOpen} title={t('legal_case_types.delete_title')} description={t('legal_case_types.delete_confirm')} confirmLabel={t('common.delete')} onCancel={() => setConfirmOpen(false)} onConfirm={() => router.delete(route('legal-case-types.destroy', caseTypeItem.id))} />
        </AuthenticatedLayout>
    );
}
