import Checkbox from '@/Components/Checkbox';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { finishSuccessfulSubmission } from '@/lib/form-submission';
import { useI18n } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';

type Props = {
    settings: {
        default_response_deadline_days?: number;
        auto_escalation_enabled?: boolean;
        reminder_interval_hours?: number;
        committee_notification_user_ids?: string[];
        allow_client_self_registration?: boolean;
        complaint_code_prefix?: string;
        allowed_attachment_types?: string[];
        max_attachment_size_mb?: number;
    };
    committeeUsers: Array<{ id: string; name: string }>;
    supportedAttachmentTypes: string[];
};

export default function ComplaintSettings({ settings, committeeUsers, supportedAttachmentTypes }: Props) {
    const { t } = useI18n();
    const form = useForm({
        default_response_deadline_days: String(settings.default_response_deadline_days ?? 5),
        auto_escalation_enabled: Boolean(settings.auto_escalation_enabled ?? true),
        reminder_interval_hours: String(settings.reminder_interval_hours ?? 24),
        committee_notification_user_ids: (settings.committee_notification_user_ids ?? []) as string[],
        allow_client_self_registration: Boolean(settings.allow_client_self_registration ?? true),
        complaint_code_prefix: settings.complaint_code_prefix ?? 'CMP',
        allowed_attachment_types: (settings.allowed_attachment_types ?? supportedAttachmentTypes.slice(0, 4)) as string[],
        max_attachment_size_mb: String(settings.max_attachment_size_mb ?? 10),
    });

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.complaints'), href: route('complaints.index') },
                { label: t('navigation.complaint_settings') },
            ]}
        >
            <Head title={t('complaints.settings.title')} />
            <PageContainer>
                <SectionHeader
                    eyebrow={t('complaints.index.eyebrow')}
                    title={t('complaints.settings.title')}
                    description={t('complaints.settings.description')}
                />

                <SurfaceCard>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.put(route('complaints.settings.update'), {
                                onSuccess: () => finishSuccessfulSubmission(form, {
                                    syncDefaults: true,
                                }),
                            });
                        }}
                        className="space-y-6"
                    >
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('complaints.settings.fields.default_response_deadline_days')} required error={form.errors.default_response_deadline_days}>
                                <input
                                    type="number"
                                    min={1}
                                    className="input-ui"
                                    value={form.data.default_response_deadline_days}
                                    onChange={(event) => form.setData('default_response_deadline_days', event.target.value)}
                                />
                            </FormField>
                            <FormField label={t('complaints.settings.fields.reminder_interval_hours')} required error={form.errors.reminder_interval_hours}>
                                <input
                                    type="number"
                                    min={1}
                                    className="input-ui"
                                    value={form.data.reminder_interval_hours}
                                    onChange={(event) => form.setData('reminder_interval_hours', event.target.value)}
                                />
                            </FormField>
                            <FormField label={t('complaints.settings.fields.complaint_code_prefix')} required error={form.errors.complaint_code_prefix}>
                                <input className="input-ui" value={form.data.complaint_code_prefix} onChange={(event) => form.setData('complaint_code_prefix', event.target.value)} />
                            </FormField>
                            <FormField label={t('complaints.settings.fields.max_attachment_size_mb')} required error={form.errors.max_attachment_size_mb}>
                                <input
                                    type="number"
                                    min={1}
                                    className="input-ui"
                                    value={form.data.max_attachment_size_mb}
                                    onChange={(event) => form.setData('max_attachment_size_mb', event.target.value)}
                                />
                            </FormField>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <label className="surface-muted flex items-start gap-3 px-4 py-4">
                                <Checkbox checked={form.data.auto_escalation_enabled} onChange={(event) => form.setData('auto_escalation_enabled', event.target.checked)} />
                                <span className="text-sm font-medium text-[color:var(--text)]">{t('complaints.settings.fields.auto_escalation_enabled')}</span>
                            </label>
                            <label className="surface-muted flex items-start gap-3 px-4 py-4">
                                <Checkbox checked={form.data.allow_client_self_registration} onChange={(event) => form.setData('allow_client_self_registration', event.target.checked)} />
                                <span className="text-sm font-medium text-[color:var(--text)]">{t('complaints.settings.fields.allow_client_self_registration')}</span>
                            </label>
                        </div>

                        <FormField label={t('complaints.settings.fields.committee_notification_user_ids')} optional error={form.errors.committee_notification_user_ids}>
                            <select
                                multiple
                                className="select-ui min-h-40"
                                value={form.data.committee_notification_user_ids}
                                onChange={(event) => {
                                    const values = Array.from(event.target.selectedOptions).map((option) => option.value);
                                    form.setData('committee_notification_user_ids', values);
                                }}
                            >
                                {committeeUsers.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name}
                                    </option>
                                ))}
                            </select>
                        </FormField>

                        <fieldset className="space-y-3">
                            <legend className="text-sm font-medium text-[color:var(--text)]">{t('complaints.settings.fields.allowed_attachment_types')}</legend>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                {supportedAttachmentTypes.map((type) => {
                                    const checked = form.data.allowed_attachment_types.includes(type);

                                    return (
                                        <label key={type} className="surface-muted flex items-center gap-3 px-4 py-3">
                                            <Checkbox
                                                checked={checked}
                                                onChange={(event) => {
                                                    if (event.target.checked) {
                                                        form.setData('allowed_attachment_types', [...form.data.allowed_attachment_types, type]);
                                                        return;
                                                    }

                                                    form.setData(
                                                        'allowed_attachment_types',
                                                        form.data.allowed_attachment_types.filter((item) => item !== type),
                                                    );
                                                }}
                                            />
                                            <span className="text-sm text-[color:var(--text)]">{type}</span>
                                        </label>
                                    );
                                })}
                            </div>
                        </fieldset>

                        <div className="flex justify-end border-t border-[color:var(--border)] pt-5">
                            <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>
                                {t('complaints.settings.actions.save')}
                            </button>
                        </div>
                    </form>
                </SurfaceCard>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
