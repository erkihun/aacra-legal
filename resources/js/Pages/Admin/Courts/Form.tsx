import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import BackButton from '@/Components/Ui/BackButton';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function CourtForm({ courtItem, canDelete }: any) {
    const { t } = useI18n();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const form = useForm({
        code: courtItem?.code ?? '',
        name_en: courtItem?.name_en ?? '',
        name_am: courtItem?.name_am ?? '',
        level: courtItem?.level ?? '',
        city: courtItem?.city ?? '',
        is_active: courtItem?.is_active ?? true,
    });
    const isEditing = !!courtItem;

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('navigation.dashboard'), href: route('dashboard') }, { label: t('navigation.courts'), href: route('courts.index') }, { label: isEditing ? t('common.edit') : t('common.create_record') }]}>
            <Head title={isEditing ? t('courts.edit_title') : t('courts.create_title')} />
            <PageContainer>
                <SectionHeader eyebrow={t('courts.eyebrow')} title={isEditing ? t('courts.edit_title') : t('courts.create_title')} description={isEditing ? t('courts.edit_description') : t('courts.create_description')} action={<BackButton fallbackHref={route('courts.index')} />} />
                <form onSubmit={(event) => { event.preventDefault(); if (isEditing) { form.patch(route('courts.update', courtItem.id)); return; } form.post(route('courts.store')); }} className="space-y-4">
                    <SurfaceCard>
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('common.code')} required error={form.errors.code}><input value={form.data.code} onChange={(event) => form.setData('code', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('courts.name_en')} required error={form.errors.name_en}><input value={form.data.name_en} onChange={(event) => form.setData('name_en', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('courts.name_am')} required error={form.errors.name_am}><input value={form.data.name_am} onChange={(event) => form.setData('name_am', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('courts.level')} optional error={form.errors.level}><input value={form.data.level} onChange={(event) => form.setData('level', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('courts.city')} optional error={form.errors.city}><input value={form.data.city} onChange={(event) => form.setData('city', event.target.value)} className="input-ui" /></FormField>
                            <FormField label={t('common.status')} required error={form.errors.is_active as string | undefined}><select value={form.data.is_active ? '1' : '0'} onChange={(event) => form.setData('is_active', event.target.value === '1')} className="select-ui"><option value="1">{t('common.active')}</option><option value="0">{t('common.inactive')}</option></select></FormField>
                        </div>
                    </SurfaceCard>
                    <div className="flex flex-wrap justify-between gap-3">
                        <div>{isEditing && canDelete ? <button type="button" className="btn-base btn-danger focus-ring" onClick={() => setConfirmOpen(true)}>{t('common.delete')}</button> : null}</div>
                        <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>{isEditing ? t('common.save_changes') : t('common.create_record')}</button>
                    </div>
                </form>
            </PageContainer>
            <ConfirmationDialog open={confirmOpen} title={t('courts.delete_title')} description={t('courts.delete_confirm')} confirmLabel={t('common.delete')} onCancel={() => setConfirmOpen(false)} onConfirm={() => router.delete(route('courts.destroy', courtItem.id))} />
        </AuthenticatedLayout>
    );
}
