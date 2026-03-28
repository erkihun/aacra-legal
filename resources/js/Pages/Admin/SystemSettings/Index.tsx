import Checkbox from '@/Components/Checkbox';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import Tabs from '@/Components/Ui/Tabs';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';

type SettingsGroupKey =
    | 'general'
    | 'organization'
    | 'localization'
    | 'notifications'
    | 'email'
    | 'sms'
    | 'telegram'
    | 'security'
    | 'appearance'
    | 'public_website';

type SettingsPageProps = {
    settingsGroups: Record<SettingsGroupKey, Record<string, any>>;
    groups: Array<{ key: SettingsGroupKey; label: string; update_route: string }>;
    locales: Array<{ value: string; label: string }>;
    timezones: string[];
    dashboardRoutes: Array<{ value: string; label: string }>;
    themeOptions: Array<{ value: string; label: string }>;
    buttonStyleOptions: Array<{ value: string; label: string }>;
    cardRadiusOptions: Array<{ value: string; label: string }>;
    tableDensityOptions: Array<{ value: string; label: string }>;
};

export default function SystemSettingsIndex({
    settingsGroups,
    groups,
    locales,
    timezones,
    dashboardRoutes,
    themeOptions,
    buttonStyleOptions,
    cardRadiusOptions,
    tableDensityOptions,
}: SettingsPageProps) {
    const { t } = useI18n();

    const generalForm = useForm({
        application_name: settingsGroups.general.application_name ?? '',
        application_short_name: settingsGroups.general.application_short_name ?? '',
        organization_name: settingsGroups.general.organization_name ?? '',
        legal_department_name: settingsGroups.general.legal_department_name ?? '',
        tagline: settingsGroups.general.tagline ?? '',
        support_email: settingsGroups.general.support_email ?? '',
        support_phone: settingsGroups.general.support_phone ?? '',
        default_dashboard_route: settingsGroups.general.default_dashboard_route ?? 'dashboard',
        system_logo: null as File | null,
        favicon: null as File | null,
    });

    const organizationForm = useForm({
        office_name: settingsGroups.organization.office_name ?? '',
        address: settingsGroups.organization.address ?? '',
        contact_phone: settingsGroups.organization.contact_phone ?? '',
        contact_email: settingsGroups.organization.contact_email ?? '',
        working_hours_text: settingsGroups.organization.working_hours_text ?? '',
        organization_description: settingsGroups.organization.organization_description ?? '',
        footer_text: settingsGroups.organization.footer_text ?? '',
    });

    const localizationForm = useForm({
        default_locale: settingsGroups.localization.default_locale ?? 'en',
        supported_locales: (settingsGroups.localization.supported_locales ?? ['en', 'am']) as string[],
        fallback_locale: settingsGroups.localization.fallback_locale ?? 'en',
        timezone: settingsGroups.localization.timezone ?? 'Africa/Addis_Ababa',
        date_format: settingsGroups.localization.date_format ?? 'Y-m-d',
        datetime_format: settingsGroups.localization.datetime_format ?? 'Y-m-d H:i',
    });

    const notificationForm = useForm({
        database_notifications_enabled: Boolean(settingsGroups.notifications.database_notifications_enabled ?? true),
        email_notifications_enabled: Boolean(settingsGroups.notifications.email_notifications_enabled ?? true),
        sms_notifications_enabled: Boolean(settingsGroups.notifications.sms_notifications_enabled ?? true),
        telegram_notifications_enabled: Boolean(settingsGroups.notifications.telegram_notifications_enabled ?? true),
        advisory_due_reminder_days: String(settingsGroups.notifications.advisory_due_reminder_days ?? 2),
        hearing_reminder_days: String(settingsGroups.notifications.hearing_reminder_days ?? 3),
        appeal_deadline_reminder_days: String(settingsGroups.notifications.appeal_deadline_reminder_days ?? 5),
    });

    const emailForm = useForm({
        mail_from_name: settingsGroups.email.mail_from_name ?? '',
        mail_from_address: settingsGroups.email.mail_from_address ?? '',
        mail_driver_label: settingsGroups.email.mail_driver_label ?? '',
        mail_host_label: settingsGroups.email.mail_host_label ?? '',
        mail_port_label: settingsGroups.email.mail_port_label ?? '',
    });

    const smsForm = useForm({
        sms_enabled: Boolean(settingsGroups.sms.sms_enabled ?? true),
        provider_name: settingsGroups.sms.provider_name ?? '',
        sender_name: settingsGroups.sms.sender_name ?? '',
        provider_base_url: settingsGroups.sms.provider_base_url ?? '',
        configuration_notes: settingsGroups.sms.configuration_notes ?? '',
    });

    const telegramForm = useForm({
        telegram_enabled: Boolean(settingsGroups.telegram.telegram_enabled ?? true),
        bot_username: settingsGroups.telegram.bot_username ?? '',
        default_chat_target: settingsGroups.telegram.default_chat_target ?? '',
        configuration_notes: settingsGroups.telegram.configuration_notes ?? '',
    });

    const securityForm = useForm({
        password_min_length: String(settingsGroups.security.password_min_length ?? 8),
        password_complexity_enabled: Boolean(settingsGroups.security.password_complexity_enabled ?? false),
        session_timeout_minutes: String(settingsGroups.security.session_timeout_minutes ?? 120),
        allowed_file_types: ((settingsGroups.security.allowed_file_types ?? []) as string[]).join(', '),
        max_upload_size_mb: String(settingsGroups.security.max_upload_size_mb ?? 10),
        maintenance_banner_enabled: Boolean(settingsGroups.security.maintenance_banner_enabled ?? false),
    });

    const appearanceForm = useForm({
        default_theme: settingsGroups.appearance.default_theme ?? 'light',
        allow_user_theme_switching: Boolean(settingsGroups.appearance.allow_user_theme_switching ?? true),
        sidebar_compact_default: Boolean(settingsGroups.appearance.sidebar_compact_default ?? false),
        table_density: settingsGroups.appearance.table_density ?? 'comfortable',
        primary_color: settingsGroups.appearance.primary_color ?? '#0f766e',
        secondary_color: settingsGroups.appearance.secondary_color ?? '#0ea5e9',
        accent_color: settingsGroups.appearance.accent_color ?? '#f59e0b',
        button_style: settingsGroups.appearance.button_style ?? 'pill',
        card_radius: settingsGroups.appearance.card_radius ?? 'soft',
    });

    const publicWebsiteForm = useForm({
        hero_eyebrow: settingsGroups.public_website.hero_eyebrow ?? '',
        hero_title: settingsGroups.public_website.hero_title ?? '',
        hero_description: settingsGroups.public_website.hero_description ?? '',
        about_title: settingsGroups.public_website.about_title ?? '',
        about_description: settingsGroups.public_website.about_description ?? '',
        services_title: settingsGroups.public_website.services_title ?? '',
        services_description: settingsGroups.public_website.services_description ?? '',
        service_advisory_title: settingsGroups.public_website.service_advisory_title ?? '',
        service_advisory_description: settingsGroups.public_website.service_advisory_description ?? '',
        service_case_support_title: settingsGroups.public_website.service_case_support_title ?? '',
        service_case_support_description: settingsGroups.public_website.service_case_support_description ?? '',
        service_policy_title: settingsGroups.public_website.service_policy_title ?? '',
        service_policy_description: settingsGroups.public_website.service_policy_description ?? '',
        process_title: settingsGroups.public_website.process_title ?? '',
        process_description: settingsGroups.public_website.process_description ?? '',
        process_step_one_title: settingsGroups.public_website.process_step_one_title ?? '',
        process_step_one_description: settingsGroups.public_website.process_step_one_description ?? '',
        process_step_two_title: settingsGroups.public_website.process_step_two_title ?? '',
        process_step_two_description: settingsGroups.public_website.process_step_two_description ?? '',
        process_step_three_title: settingsGroups.public_website.process_step_three_title ?? '',
        process_step_three_description: settingsGroups.public_website.process_step_three_description ?? '',
        process_step_four_title: settingsGroups.public_website.process_step_four_title ?? '',
        process_step_four_description: settingsGroups.public_website.process_step_four_description ?? '',
        posts_title: settingsGroups.public_website.posts_title ?? '',
        posts_description: settingsGroups.public_website.posts_description ?? '',
        cta_title: settingsGroups.public_website.cta_title ?? '',
        cta_description: settingsGroups.public_website.cta_description ?? '',
        cta_primary_label: settingsGroups.public_website.cta_primary_label ?? '',
        cta_secondary_label: settingsGroups.public_website.cta_secondary_label ?? '',
        contact_title: settingsGroups.public_website.contact_title ?? '',
        contact_description: settingsGroups.public_website.contact_description ?? '',
        contact_hours_value: settingsGroups.public_website.contact_hours_value ?? '',
        hero_slides: (settingsGroups.public_website.hero_slides ?? []).map((slide: any, index: number) => ({
            title: slide.title ?? '',
            subtitle: slide.subtitle ?? '',
            button_label: slide.button_label ?? '',
            button_url: slide.button_url ?? '',
            display_order: String(slide.display_order ?? index + 1),
            is_active: Boolean(slide.is_active ?? true),
            image: null as File | null,
            image_path: slide.image_path ?? null,
            image_url: slide.image_url ?? null,
        })),
    });

    const submitWithFiles = (form: any, routeName: string) => {
        form.transform((data: any) => ({
            ...data,
            _method: 'put',
        }));

        form.post(routeName, {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => {
                form.transform((data: any) => data);
            },
        });
    };

    const tabItems = groups.map((group) => ({
        key: group.key,
        label: group.label,
        content: (
            <SettingsGroupPanel
                group={group.key}
                routeName={group.update_route}
                forms={{
                    generalForm,
                    organizationForm,
                    localizationForm,
                    notificationForm,
                    emailForm,
                    smsForm,
                    telegramForm,
                    securityForm,
                    appearanceForm,
                    publicWebsiteForm,
                }}
                locales={locales}
                timezones={timezones}
                dashboardRoutes={dashboardRoutes}
                themeOptions={themeOptions}
                buttonStyleOptions={buttonStyleOptions}
                cardRadiusOptions={cardRadiusOptions}
                tableDensityOptions={tableDensityOptions}
                settingsGroups={settingsGroups}
                submitWithFiles={submitWithFiles}
            />
        ),
    }));

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.system_settings') },
            ]}
        >
            <Head title={t('settings.title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('settings.eyebrow')}
                    title={t('settings.title')}
                    description={t('settings.description')}
                />

                <Tabs items={tabItems} />
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function SettingsGroupPanel({
    group,
    routeName,
    forms,
    locales,
    timezones,
    dashboardRoutes,
    themeOptions,
    buttonStyleOptions,
    cardRadiusOptions,
    tableDensityOptions,
    settingsGroups,
    submitWithFiles,
}: {
    group: SettingsGroupKey;
    routeName: string;
    forms: Record<string, any>;
    locales: Array<{ value: string; label: string }>;
    timezones: string[];
    dashboardRoutes: Array<{ value: string; label: string }>;
    themeOptions: Array<{ value: string; label: string }>;
    buttonStyleOptions: Array<{ value: string; label: string }>;
    cardRadiusOptions: Array<{ value: string; label: string }>;
    tableDensityOptions: Array<{ value: string; label: string }>;
    settingsGroups: Record<SettingsGroupKey, Record<string, any>>;
    submitWithFiles: (form: any, routeName: string) => void;
}) {
    const { t } = useI18n();

    if (group === 'general') {
        const form = forms.generalForm;

        return (
            <SurfaceCard>
                <form onSubmit={(event) => { event.preventDefault(); submitWithFiles(form, routeName); }} className="space-y-6">
                    <div className="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                        <div className="grid gap-4 md:grid-cols-2">
                            <TextField form={form} name="application_name" label={t('settings.fields.application_name')} required />
                            <TextField form={form} name="application_short_name" label={t('settings.fields.application_short_name')} required />
                            <TextField form={form} name="organization_name" label={t('settings.fields.organization_name')} required />
                            <TextField form={form} name="legal_department_name" label={t('settings.fields.legal_department_name')} required />
                            <TextField form={form} name="tagline" label={t('settings.fields.tagline')} />
                            <SelectField
                                form={form}
                                name="default_dashboard_route"
                                label={t('settings.fields.default_dashboard_route')}
                                options={dashboardRoutes}
                                required
                            />
                            <TextField form={form} name="support_email" label={t('settings.fields.support_email')} type="email" />
                            <TextField form={form} name="support_phone" label={t('settings.fields.support_phone')} />
                        </div>

                        <div className="surface-muted flex flex-col justify-between gap-5 px-5 py-5">
                            <div>
                                <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                                    {t('settings.preview_identity')}
                                </p>
                                <h3 className="mt-3 text-2xl font-semibold text-[color:var(--text)]">
                                    {form.data.application_name || t('settings.preview_placeholder')}
                                </h3>
                                <p className="mt-2 text-sm font-medium text-[color:var(--muted-strong)]">
                                    {form.data.legal_department_name || t('settings.preview_legal_department')}
                                </p>
                                <p className="mt-4 text-sm leading-7 text-[color:var(--muted)]">
                                    {form.data.tagline || t('settings.preview_tagline')}
                                </p>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FileField
                                    label={t('settings.fields.system_logo')}
                                    hint={t('settings.hints.system_logo')}
                                    previewUrl={settingsGroups.general.system_logo_url}
                                    onChange={(file) => form.setData('system_logo', file)}
                                    error={form.errors.system_logo}
                                />
                                <FileField
                                    label={t('settings.fields.favicon')}
                                    hint={t('settings.hints.favicon')}
                                    previewUrl={settingsGroups.general.favicon_url}
                                    onChange={(file) => form.setData('favicon', file)}
                                    error={form.errors.favicon}
                                />
                            </div>
                        </div>
                    </div>

                    <SaveBar form={form} />
                </form>
            </SurfaceCard>
        );
    }

    if (group === 'organization') {
        const form = forms.organizationForm;

        return (
            <SurfaceCard>
                <form onSubmit={(event) => { event.preventDefault(); submitWithFiles(form, routeName); }} className="space-y-6">
                    <div className="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                        <div className="space-y-6">
                            <div className="grid gap-4 md:grid-cols-2">
                                <TextField form={form} name="office_name" label={t('settings.fields.office_name')} required />
                                <TextField form={form} name="contact_phone" label={t('settings.fields.contact_phone')} />
                                <TextField form={form} name="contact_email" label={t('settings.fields.contact_email')} type="email" />
                                <TextField form={form} name="working_hours_text" label={t('settings.fields.working_hours_text')} />
                            </div>
                            <TextareaField form={form} name="address" label={t('settings.fields.address')} rows={3} />
                            <TextareaField form={form} name="organization_description" label={t('settings.fields.organization_description')} rows={4} />
                            <TextareaField form={form} name="footer_text" label={t('settings.fields.footer_text')} rows={3} />
                        </div>

                        <div className="surface-muted space-y-4 px-5 py-5">
                            <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                                {t('settings.preview_contact')}
                            </p>
                            <PreviewLine label={t('settings.fields.office_name')} value={form.data.office_name} />
                            <PreviewLine label={t('settings.fields.contact_email')} value={form.data.contact_email} />
                            <PreviewLine label={t('settings.fields.contact_phone')} value={form.data.contact_phone} />
                            <PreviewLine label={t('settings.fields.working_hours_text')} value={form.data.working_hours_text} />
                            <PreviewLine label={t('settings.fields.address')} value={form.data.address} multiline />
                            <PreviewLine label={t('settings.fields.footer_text')} value={form.data.footer_text} multiline />
                        </div>
                    </div>
                    <SaveBar form={form} />
                </form>
            </SurfaceCard>
        );
    }

    if (group === 'localization') {
        const form = forms.localizationForm;

        return (
            <SurfaceCard>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.transform((data: any) => ({
                            ...data,
                            _method: 'put',
                        }));
                        form.post(routeName, {
                            preserveScroll: true,
                            forceFormData: true,
                            onFinish: () => form.transform((data: any) => data),
                        });
                    }}
                    className="space-y-6"
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <SelectField form={form} name="default_locale" label={t('settings.fields.default_locale')} options={locales} required />
                        <SelectField form={form} name="fallback_locale" label={t('settings.fields.fallback_locale')} options={locales} required />
                        <SelectField
                            form={form}
                            name="timezone"
                            label={t('settings.fields.timezone')}
                            options={timezones.map((timezone) => ({ value: timezone, label: timezone }))}
                            required
                        />
                        <TextField form={form} name="date_format" label={t('settings.fields.date_format')} required />
                        <TextField form={form} name="datetime_format" label={t('settings.fields.datetime_format')} required />
                    </div>

                    <FormField label={t('settings.fields.supported_locales')} required error={form.errors.supported_locales as string | undefined}>
                        <div className="grid gap-3 md:grid-cols-2">
                            {locales.map((locale) => (
                                <label key={locale.value} className="surface-muted flex items-center gap-3 px-4 py-3">
                                    <Checkbox
                                        checked={form.data.supported_locales.includes(locale.value)}
                                        onChange={(event) => {
                                            if (event.target.checked) {
                                                form.setData('supported_locales', [...form.data.supported_locales, locale.value]);
                                                return;
                                            }

                                            form.setData(
                                                'supported_locales',
                                                form.data.supported_locales.filter((item: string) => item !== locale.value),
                                            );
                                        }}
                                    />
                                    <span className="text-sm text-[color:var(--text)]">{locale.label}</span>
                                </label>
                            ))}
                        </div>
                    </FormField>

                    <SaveBar form={form} />
                </form>
            </SurfaceCard>
        );
    }

    if (group === 'notifications') {
        const form = forms.notificationForm;

        return (
            <SurfaceCard>
                <form onSubmit={(event) => { event.preventDefault(); submitWithFiles(form, routeName); }} className="space-y-6">
                    <div className="grid gap-3 md:grid-cols-2">
                        <ToggleField form={form} name="database_notifications_enabled" label={t('settings.fields.database_notifications_enabled')} />
                        <ToggleField form={form} name="email_notifications_enabled" label={t('settings.fields.email_notifications_enabled')} />
                        <ToggleField form={form} name="sms_notifications_enabled" label={t('settings.fields.sms_notifications_enabled')} />
                        <ToggleField form={form} name="telegram_notifications_enabled" label={t('settings.fields.telegram_notifications_enabled')} />
                    </div>
                    <div className="grid gap-4 md:grid-cols-3">
                        <TextField form={form} name="advisory_due_reminder_days" label={t('settings.fields.advisory_due_reminder_days')} type="number" required />
                        <TextField form={form} name="hearing_reminder_days" label={t('settings.fields.hearing_reminder_days')} type="number" required />
                        <TextField form={form} name="appeal_deadline_reminder_days" label={t('settings.fields.appeal_deadline_reminder_days')} type="number" required />
                    </div>
                    <SaveBar form={form} />
                </form>
            </SurfaceCard>
        );
    }

    if (group === 'email') {
        const form = forms.emailForm;

        return (
            <SurfaceCard>
                <form onSubmit={(event) => { event.preventDefault(); form.put(routeName, { preserveScroll: true }); }} className="space-y-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <TextField form={form} name="mail_from_name" label={t('settings.fields.mail_from_name')} required />
                        <TextField form={form} name="mail_from_address" label={t('settings.fields.mail_from_address')} type="email" required />
                        <TextField form={form} name="mail_driver_label" label={t('settings.fields.mail_driver_label')} />
                        <TextField form={form} name="mail_host_label" label={t('settings.fields.mail_host_label')} />
                        <TextField form={form} name="mail_port_label" label={t('settings.fields.mail_port_label')} />
                    </div>
                    <SaveBar form={form} />
                </form>
            </SurfaceCard>
        );
    }

    if (group === 'sms') {
        const form = forms.smsForm;

        return (
            <SurfaceCard>
                <form onSubmit={(event) => { event.preventDefault(); form.put(routeName, { preserveScroll: true }); }} className="space-y-6">
                    <ToggleField form={form} name="sms_enabled" label={t('settings.fields.sms_enabled')} />
                    <div className="grid gap-4 md:grid-cols-2">
                        <TextField form={form} name="provider_name" label={t('settings.fields.provider_name')} />
                        <TextField form={form} name="sender_name" label={t('settings.fields.sender_name')} />
                        <TextField form={form} name="provider_base_url" label={t('settings.fields.provider_base_url')} />
                    </div>
                    <TextareaField form={form} name="configuration_notes" label={t('settings.fields.configuration_notes')} rows={4} />
                    <SaveBar form={form} />
                </form>
            </SurfaceCard>
        );
    }

    if (group === 'telegram') {
        const form = forms.telegramForm;

        return (
            <SurfaceCard>
                <form onSubmit={(event) => { event.preventDefault(); form.put(routeName, { preserveScroll: true }); }} className="space-y-6">
                    <ToggleField form={form} name="telegram_enabled" label={t('settings.fields.telegram_enabled')} />
                    <div className="grid gap-4 md:grid-cols-2">
                        <TextField form={form} name="bot_username" label={t('settings.fields.bot_username')} />
                        <TextField form={form} name="default_chat_target" label={t('settings.fields.default_chat_target')} />
                    </div>
                    <TextareaField form={form} name="configuration_notes" label={t('settings.fields.configuration_notes')} rows={4} />
                    <SaveBar form={form} />
                </form>
            </SurfaceCard>
        );
    }

    if (group === 'security') {
        const form = forms.securityForm;

        return (
            <SurfaceCard>
                <form onSubmit={(event) => { event.preventDefault(); form.put(routeName, { preserveScroll: true }); }} className="space-y-6">
                    <div className="grid gap-4 md:grid-cols-3">
                        <TextField form={form} name="password_min_length" label={t('settings.fields.password_min_length')} type="number" required />
                        <TextField form={form} name="session_timeout_minutes" label={t('settings.fields.session_timeout_minutes')} type="number" required />
                        <TextField form={form} name="max_upload_size_mb" label={t('settings.fields.max_upload_size_mb')} type="number" required />
                    </div>
                    <div className="grid gap-3 md:grid-cols-2">
                        <ToggleField form={form} name="password_complexity_enabled" label={t('settings.fields.password_complexity_enabled')} />
                        <ToggleField form={form} name="maintenance_banner_enabled" label={t('settings.fields.maintenance_banner_enabled')} />
                    </div>
                    <TextareaField
                        form={form}
                        name="allowed_file_types"
                        label={t('settings.fields.allowed_file_types')}
                        hint={t('settings.hints.allowed_file_types')}
                        rows={3}
                    />
                    <SaveBar form={form} />
                </form>
            </SurfaceCard>
        );
    }

    const form = forms.appearanceForm;

    if (group === 'public_website') {
        const form = forms.publicWebsiteForm;
        const updateSlide = (index: number, field: string, value: any) => {
            form.setData(
                'hero_slides',
                form.data.hero_slides.map((slide: any, slideIndex: number) =>
                    slideIndex === index ? { ...slide, [field]: value } : slide,
                ),
            );
        };

        return (
            <SurfaceCard>
                <form onSubmit={(event) => { event.preventDefault(); form.put(routeName, { preserveScroll: true }); }} className="space-y-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <TextField form={form} name="hero_eyebrow" label={t('settings.fields.hero_eyebrow')} />
                        <TextField form={form} name="hero_title" label={t('settings.fields.hero_title')} required />
                    </div>
                    <TextareaField form={form} name="hero_description" label={t('settings.fields.hero_description')} rows={4} />

                    <div className="grid gap-4 md:grid-cols-2">
                        <TextField form={form} name="about_title" label={t('settings.fields.about_title')} required />
                        <TextField form={form} name="services_title" label={t('settings.fields.services_title')} required />
                    </div>
                    <TextareaField form={form} name="about_description" label={t('settings.fields.about_description')} rows={4} />
                    <TextareaField form={form} name="services_description" label={t('settings.fields.services_description')} rows={3} />

                    <div className="grid gap-4 md:grid-cols-2">
                        <TextField form={form} name="service_advisory_title" label={t('settings.fields.service_advisory_title')} required />
                        <TextField form={form} name="service_case_support_title" label={t('settings.fields.service_case_support_title')} required />
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <TextareaField form={form} name="service_advisory_description" label={t('settings.fields.service_advisory_description')} rows={3} />
                        <TextareaField form={form} name="service_case_support_description" label={t('settings.fields.service_case_support_description')} rows={3} />
                    </div>
                    <TextField form={form} name="service_policy_title" label={t('settings.fields.service_policy_title')} required />
                    <TextareaField form={form} name="service_policy_description" label={t('settings.fields.service_policy_description')} rows={3} />

                    <TextField form={form} name="process_title" label={t('settings.fields.process_title')} required />
                    <TextareaField form={form} name="process_description" label={t('settings.fields.process_description')} rows={3} />
                    <div className="grid gap-4 md:grid-cols-2">
                        <TextField form={form} name="process_step_one_title" label={t('settings.fields.process_step_one_title')} required />
                        <TextField form={form} name="process_step_two_title" label={t('settings.fields.process_step_two_title')} required />
                        <TextareaField form={form} name="process_step_one_description" label={t('settings.fields.process_step_one_description')} rows={3} />
                        <TextareaField form={form} name="process_step_two_description" label={t('settings.fields.process_step_two_description')} rows={3} />
                        <TextField form={form} name="process_step_three_title" label={t('settings.fields.process_step_three_title')} required />
                        <TextField form={form} name="process_step_four_title" label={t('settings.fields.process_step_four_title')} required />
                        <TextareaField form={form} name="process_step_three_description" label={t('settings.fields.process_step_three_description')} rows={3} />
                        <TextareaField form={form} name="process_step_four_description" label={t('settings.fields.process_step_four_description')} rows={3} />
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <TextField form={form} name="posts_title" label={t('settings.fields.posts_title')} required />
                        <TextField form={form} name="contact_title" label={t('settings.fields.contact_title')} required />
                    </div>
                    <TextareaField form={form} name="posts_description" label={t('settings.fields.posts_description')} rows={3} />
                    <TextField form={form} name="cta_title" label={t('settings.fields.cta_title')} required />
                    <TextareaField form={form} name="cta_description" label={t('settings.fields.cta_description')} rows={3} />
                    <div className="grid gap-4 md:grid-cols-2">
                        <TextField form={form} name="cta_primary_label" label={t('settings.fields.cta_primary_label')} required />
                        <TextField form={form} name="cta_secondary_label" label={t('settings.fields.cta_secondary_label')} required />
                    </div>
                    <TextareaField form={form} name="contact_description" label={t('settings.fields.contact_description')} rows={3} />
                    <TextField form={form} name="contact_hours_value" label={t('settings.fields.contact_hours_value')} required />

                    <div className="space-y-4">
                        <div>
                            <h3 className="text-base font-semibold text-[color:var(--text)]">{t('settings.fields.hero_slides')}</h3>
                            <p className="mt-1 text-sm text-[color:var(--muted)]">{t('settings.hints.hero_slides')}</p>
                        </div>

                        <div className="grid gap-4 xl:grid-cols-3">
                            {form.data.hero_slides.map((slide: any, index: number) => (
                                <div key={index} className="surface-muted space-y-4 px-4 py-4">
                                    <div className="flex items-center justify-between gap-3">
                                        <h4 className="text-sm font-semibold uppercase text-[color:var(--muted-strong)]">
                                            {t('settings.slide_label').replace(':number', String(index + 1))}
                                        </h4>
                                        <label className="flex items-center gap-2 text-xs font-medium text-[color:var(--muted-strong)]">
                                            <Checkbox
                                                checked={Boolean(slide.is_active)}
                                                onChange={(event) => updateSlide(index, 'is_active', event.target.checked)}
                                            />
                                            <span>{t('settings.fields.slide_is_active')}</span>
                                        </label>
                                    </div>

                                    <FormField
                                        label={t('settings.fields.slide_title')}
                                        required
                                        error={form.errors[`hero_slides.${index}.title`]}
                                    >
                                        <input
                                            value={slide.title}
                                            onChange={(event) => updateSlide(index, 'title', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>

                                    <FormField
                                        label={t('settings.fields.slide_subtitle')}
                                        required
                                        error={form.errors[`hero_slides.${index}.subtitle`]}
                                    >
                                        <textarea
                                            value={slide.subtitle}
                                            onChange={(event) => updateSlide(index, 'subtitle', event.target.value)}
                                            rows={4}
                                            className="textarea-ui"
                                        />
                                    </FormField>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <FormField
                                            label={t('settings.fields.slide_button_label')}
                                            required
                                            error={form.errors[`hero_slides.${index}.button_label`]}
                                        >
                                            <input
                                                value={slide.button_label}
                                                onChange={(event) => updateSlide(index, 'button_label', event.target.value)}
                                                className="input-ui"
                                            />
                                        </FormField>

                                        <FormField
                                            label={t('settings.fields.slide_display_order')}
                                            required
                                            error={form.errors[`hero_slides.${index}.display_order`]}
                                        >
                                            <input
                                                type="number"
                                                min={1}
                                                value={slide.display_order}
                                                onChange={(event) => updateSlide(index, 'display_order', event.target.value)}
                                                className="input-ui"
                                            />
                                        </FormField>
                                    </div>

                                    <FormField
                                        label={t('settings.fields.slide_button_url')}
                                        required
                                        error={form.errors[`hero_slides.${index}.button_url`]}
                                    >
                                        <input
                                            value={slide.button_url}
                                            onChange={(event) => updateSlide(index, 'button_url', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>

                                    <FormField
                                        label={t('settings.fields.slide_image')}
                                        optional
                                        error={form.errors[`hero_slides.${index}.image`]}
                                    >
                                        <div className="space-y-3">
                                            {slide.image_url ? (
                                                <div className="overflow-hidden rounded-2xl border border-[color:var(--border)]">
                                                    <img
                                                        src={slide.image_url}
                                                        alt={slide.title || t('settings.slide_label').replace(':number', String(index + 1))}
                                                        className="h-36 w-full object-cover"
                                                    />
                                                </div>
                                            ) : null}
                                            <input
                                                type="file"
                                                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                                onChange={(event) => {
                                                    const file = event.target.files?.[0] ?? null;

                                                    updateSlide(index, 'image', file);

                                                    if (file) {
                                                        updateSlide(index, 'image_url', URL.createObjectURL(file));
                                                    }
                                                }}
                                                className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                            />
                                        </div>
                                    </FormField>
                                </div>
                            ))}
                        </div>
                    </div>

                    <SaveBar form={form} />
                </form>
            </SurfaceCard>
        );
    }

    return (
        <SurfaceCard>
            <form onSubmit={(event) => { event.preventDefault(); form.put(routeName, { preserveScroll: true }); }} className="space-y-6">
                <div className="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                    <div className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <SelectField form={form} name="default_theme" label={t('settings.fields.default_theme')} options={themeOptions} required />
                            <SelectField form={form} name="table_density" label={t('settings.fields.table_density')} options={tableDensityOptions} required />
                            <SelectField form={form} name="button_style" label={t('settings.fields.button_style')} options={buttonStyleOptions} required />
                            <SelectField form={form} name="card_radius" label={t('settings.fields.card_radius')} options={cardRadiusOptions} required />
                        </div>
                        <div className="grid gap-4 md:grid-cols-3">
                            <ColorField form={form} name="primary_color" label={t('settings.fields.primary_color')} />
                            <ColorField form={form} name="secondary_color" label={t('settings.fields.secondary_color')} />
                            <ColorField form={form} name="accent_color" label={t('settings.fields.accent_color')} />
                        </div>
                        <div className="grid gap-3 md:grid-cols-2">
                            <ToggleField form={form} name="allow_user_theme_switching" label={t('settings.fields.allow_user_theme_switching')} />
                            <ToggleField form={form} name="sidebar_compact_default" label={t('settings.fields.sidebar_compact_default')} />
                        </div>
                    </div>

                    <div className="surface-muted space-y-4 px-5 py-5">
                        <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                            {t('settings.preview_branding')}
                        </p>
                        <div className="grid gap-3">
                            <ColorPreview label={t('settings.fields.primary_color')} value={form.data.primary_color} />
                            <ColorPreview label={t('settings.fields.secondary_color')} value={form.data.secondary_color} />
                            <ColorPreview label={t('settings.fields.accent_color')} value={form.data.accent_color} />
                        </div>
                        <div className="rounded-[1.25rem] border border-[color:var(--border)] bg-[color:var(--surface-strong)] p-4">
                            <div className="flex flex-wrap gap-2">
                                <span
                                    className="btn-base"
                                    style={{
                                        backgroundColor: form.data.primary_color,
                                        color: '#ffffff',
                                        borderRadius: buttonRadiusFor(form.data.button_style),
                                    }}
                                >
                                    {t('common.save_changes')}
                                </span>
                                <span
                                    className="btn-base border"
                                    style={{
                                        borderColor: form.data.secondary_color,
                                        color: form.data.secondary_color,
                                        borderRadius: buttonRadiusFor(form.data.button_style),
                                    }}
                                >
                                    {t('common.view')}
                                </span>
                            </div>
                            <div
                                className="mt-4 border bg-[color:var(--surface-muted)] p-4"
                                style={{
                                    borderColor: 'var(--border)',
                                    borderRadius: cardRadiusFor(form.data.card_radius),
                                }}
                            >
                                <p className="text-sm font-semibold text-[color:var(--text)]">{t('settings.preview_surface_title')}</p>
                                <p className="mt-2 text-sm text-[color:var(--muted)]">{t('settings.preview_surface_description')}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <SaveBar form={form} />
            </form>
        </SurfaceCard>
    );
}

function TextField({
    form,
    name,
    label,
    type = 'text',
    required = false,
}: {
    form: any;
    name: string;
    label: string;
    type?: string;
    required?: boolean;
}) {
    return (
        <FormField label={label} required={required} error={form.errors[name]}>
            <input
                type={type}
                value={form.data[name]}
                onChange={(event) => form.setData(name, event.target.value)}
                className="input-ui"
            />
        </FormField>
    );
}

function TextareaField({
    form,
    name,
    label,
    rows,
    hint,
}: {
    form: any;
    name: string;
    label: string;
    rows: number;
    hint?: string;
}) {
    return (
        <FormField label={label} optional error={form.errors[name]} hint={hint}>
            <textarea
                value={form.data[name]}
                onChange={(event) => form.setData(name, event.target.value)}
                rows={rows}
                className="textarea-ui"
            />
        </FormField>
    );
}

function SelectField({
    form,
    name,
    label,
    options,
    required = false,
}: {
    form: any;
    name: string;
    label: string;
    options: Array<{ value: string; label: string }>;
    required?: boolean;
}) {
    return (
        <FormField label={label} required={required} error={form.errors[name]}>
            <select value={form.data[name]} onChange={(event) => form.setData(name, event.target.value)} className="select-ui">
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </FormField>
    );
}

function ToggleField({
    form,
    name,
    label,
}: {
    form: any;
    name: string;
    label: string;
}) {
    return (
        <label className="surface-muted flex items-center gap-3 px-4 py-4">
            <Checkbox checked={Boolean(form.data[name])} onChange={(event) => form.setData(name, event.target.checked)} />
            <span className="text-sm font-medium text-[color:var(--text)]">{label}</span>
        </label>
    );
}

function ColorField({
    form,
    name,
    label,
}: {
    form: any;
    name: string;
    label: string;
}) {
    return (
        <FormField label={label} required error={form.errors[name]}>
            <div className="flex items-center gap-3">
                <input
                    type="color"
                    value={form.data[name]}
                    onChange={(event) => form.setData(name, event.target.value)}
                    className="h-12 w-16 cursor-pointer rounded-xl border border-[color:var(--border)] bg-transparent p-1"
                />
                <input
                    type="text"
                    value={form.data[name]}
                    onChange={(event) => form.setData(name, event.target.value)}
                    className="input-ui"
                />
            </div>
        </FormField>
    );
}

function FileField({
    label,
    hint,
    previewUrl,
    onChange,
    error,
}: {
    label: string;
    hint?: string;
    previewUrl?: string | null;
    onChange: (file: File | null) => void;
    error?: string;
}) {
    return (
        <FormField label={label} optional error={error} hint={hint}>
            <div className="space-y-3">
                {previewUrl ? (
                    <div className="surface-muted flex min-h-24 items-center justify-center overflow-hidden px-4 py-4">
                        <img src={previewUrl} alt={label} className="max-h-20 object-contain" />
                    </div>
                ) : null}
                <input
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/x-icon,image/vnd.microsoft.icon"
                    onChange={(event) => onChange(event.target.files?.[0] ?? null)}
                    className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                />
            </div>
        </FormField>
    );
}

function ColorPreview({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-3 rounded-[1rem] border border-[color:var(--border)] bg-[color:var(--surface-strong)] px-4 py-3">
            <div>
                <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
                <p className="mt-1 text-sm font-semibold text-[color:var(--text)]">{value}</p>
            </div>
            <span className="h-10 w-10 rounded-full border border-white/60 shadow-sm" style={{ backgroundColor: value }} />
        </div>
    );
}

function PreviewLine({
    label,
    value,
    multiline = false,
}: {
    label: string;
    value?: string | null;
    multiline?: boolean;
}) {
    return (
        <div className="rounded-[1rem] border border-[color:var(--border)] bg-[color:var(--surface-strong)] px-4 py-3">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className={`mt-2 text-sm text-[color:var(--text)] ${multiline ? 'whitespace-pre-line leading-7' : 'font-medium'}`}>
                {value || '-'}
            </p>
        </div>
    );
}

function buttonRadiusFor(value: string) {
    return value === 'rounded' ? '0.9rem' : value === 'square' ? '0.35rem' : '999px';
}

function cardRadiusFor(value: string) {
    return value === 'rounded' ? '1.35rem' : value === 'square' ? '0.45rem' : '1.75rem';
}

function SaveBar({ form }: { form: any }) {
    const { t } = useI18n();

    return (
        <div className="flex justify-end">
            <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>
                {t('common.save_changes')}
            </button>
        </div>
    );
}
