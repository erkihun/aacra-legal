import BackButton from '@/Components/Ui/BackButton';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useI18n } from '@/lib/i18n';

type PermissionItem = {
    id: string;
    name: string;
    group: string;
    group_label: string;
    label: string;
    description_en: string;
    description_am: string;
};

export default function PermissionsEdit({ permissionItem }: { permissionItem: PermissionItem }) {
    const { t } = useI18n();
    const form = useForm({
        description_en: permissionItem.description_en,
        description_am: permissionItem.description_am,
    });

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.roles'), href: route('roles.index') },
                { label: t('permissions.index_title'), href: route('permissions.index') },
                { label: permissionItem.name },
            ]}
        >
            <Head title={permissionItem.name} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('roles.eyebrow')}
                    title={permissionItem.name}
                    description={t('permissions.edit_description')}
                    action={<BackButton fallbackHref={route('permissions.index')} />}
                />

                <div className="grid gap-4 xl:grid-cols-[1.1fr,0.9fr]">
                    <form
                        className="space-y-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.patch(route('permissions.update', permissionItem.id));
                        }}
                    >
                        <SurfaceCard className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField label={t('permissions.permission_key')}>
                                    <input value={permissionItem.name} className="input-ui" readOnly />
                                </FormField>
                                <FormField label={t('permissions.permission_label')}>
                                    <input value={permissionItem.label} className="input-ui" readOnly />
                                </FormField>
                            </div>

                            <FormField label={t('permissions.permission_group')}>
                                <input value={permissionItem.group_label} className="input-ui" readOnly />
                            </FormField>
                        </SurfaceCard>

                        <SurfaceCard className="space-y-4">
                            <FormField
                                label={t('permissions.description_en')}
                                required
                                error={form.errors.description_en}
                                hint={t('permissions.description_en_hint')}
                            >
                                <textarea
                                    value={form.data.description_en}
                                    onChange={(event) => form.setData('description_en', event.target.value)}
                                    className="textarea-ui min-h-32"
                                />
                            </FormField>

                            <FormField
                                label={t('permissions.description_am')}
                                required
                                error={form.errors.description_am}
                                hint={t('permissions.description_am_hint')}
                            >
                                <textarea
                                    value={form.data.description_am}
                                    onChange={(event) => form.setData('description_am', event.target.value)}
                                    className="textarea-ui min-h-32"
                                />
                            </FormField>

                            <div className="flex justify-end">
                                <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>
                                    {t('common.save_changes')}
                                </button>
                            </div>
                        </SurfaceCard>
                    </form>

                    <SurfaceCard className="space-y-4">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('permissions.preview_title')}</h2>
                        <div className="surface-muted space-y-2 px-4 py-4">
                            <p className="text-xs uppercase text-[color:var(--muted)]">{t('permissions.description_en')}</p>
                            <p className="text-sm leading-6 text-[color:var(--text)]">{form.data.description_en}</p>
                        </div>
                        <div className="surface-muted space-y-2 px-4 py-4">
                            <p className="text-xs uppercase text-[color:var(--muted)]">{t('permissions.description_am')}</p>
                            <p className="text-sm leading-6 text-[color:var(--text)]">{form.data.description_am}</p>
                        </div>
                    </SurfaceCard>
                </div>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
