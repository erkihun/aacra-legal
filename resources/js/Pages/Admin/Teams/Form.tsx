import Checkbox from '@/Components/Checkbox';
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

export default function TeamForm({ teamItem, leaderOptions, canDelete }: any) {
    const { t } = useI18n();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const form = useForm({
        leader_user_id: teamItem?.leader_user_id ?? '',
        code: teamItem?.code ?? '',
        name_en: teamItem?.name_en ?? '',
        name_am: teamItem?.name_am ?? '',
        supports_advisory: teamItem?.supports_advisory ?? false,
        supports_court_case: teamItem?.supports_court_case ?? false,
        is_active: teamItem?.is_active ?? true,
    });
    const isEditing = !!teamItem;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.teams'), href: route('teams.index') },
                { label: isEditing ? t('common.edit') : t('common.create_record') },
            ]}
        >
            <Head title={isEditing ? t('teams.edit_title') : t('teams.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('teams.eyebrow')}
                    title={isEditing ? t('teams.edit_title') : t('teams.create_title')}
                    description={isEditing ? t('teams.edit_description') : t('teams.create_description')}
                    action={<BackButton fallbackHref={route('teams.index')} />}
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (isEditing) {
                            form.patch(route('teams.update', teamItem.id));
                            return;
                        }

                        form.post(route('teams.store'));
                    }}
                    className="space-y-4"
                >
                    <SurfaceCard>
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('common.code')} required error={form.errors.code}>
                                <input value={form.data.code} onChange={(event) => form.setData('code', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('teams.capabilities')} required error={form.errors.supports_advisory}>
                                <div className="space-y-3 rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface-muted)] px-4 py-4">
                                    <label className="flex items-start gap-3">
                                        <Checkbox
                                            checked={form.data.supports_advisory}
                                            onChange={(event) => form.setData('supports_advisory', event.target.checked)}
                                        />
                                        <div>
                                            <p className="text-sm font-medium text-[color:var(--text)]">{t('teams.supports_advisory')}</p>
                                            <p className="text-xs text-[color:var(--muted)]">{t('teams.supports_advisory_hint')}</p>
                                        </div>
                                    </label>
                                    <label className="flex items-start gap-3">
                                        <Checkbox
                                            checked={form.data.supports_court_case}
                                            onChange={(event) => form.setData('supports_court_case', event.target.checked)}
                                        />
                                        <div>
                                            <p className="text-sm font-medium text-[color:var(--text)]">{t('teams.supports_court_case')}</p>
                                            <p className="text-xs text-[color:var(--muted)]">{t('teams.supports_court_case_hint')}</p>
                                        </div>
                                    </label>
                                </div>
                            </FormField>
                            <FormField label={t('teams.name_en')} required error={form.errors.name_en}>
                                <input value={form.data.name_en} onChange={(event) => form.setData('name_en', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('teams.name_am')} required error={form.errors.name_am}>
                                <input value={form.data.name_am} onChange={(event) => form.setData('name_am', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('teams.leader')} optional error={form.errors.leader_user_id}>
                                <select value={form.data.leader_user_id} onChange={(event) => form.setData('leader_user_id', event.target.value)} className="select-ui">
                                    <option value="">{t('common.unassigned')}</option>
                                    {leaderOptions.map((option: any) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
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
                title={t('teams.delete_title')}
                description={t('teams.delete_confirm')}
                confirmLabel={t('common.delete')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => router.delete(route('teams.destroy', teamItem.id))}
            />
        </AuthenticatedLayout>
    );
}
