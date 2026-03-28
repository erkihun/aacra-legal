<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\LocaleCode;
use App\Enums\SystemSettingGroup;
use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return match ($this->group()) {
            SystemSettingGroup::GENERAL => [
                'application_name' => ['required', 'string', 'max:255'],
                'application_short_name' => ['required', 'string', 'max:40'],
                'organization_name' => ['required', 'string', 'max:255'],
                'legal_department_name' => ['required', 'string', 'max:255'],
                'tagline' => ['nullable', 'string', 'max:255'],
                'support_email' => ['nullable', 'email', 'max:255'],
                'support_phone' => ['nullable', 'string', 'max:40'],
                'default_dashboard_route' => ['required', Rule::in(['dashboard', 'reports.index', 'notifications.index'])],
                'system_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
                'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:1024'],
            ],
            SystemSettingGroup::ORGANIZATION => [
                'office_name' => ['required', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:1000'],
                'contact_phone' => ['nullable', 'string', 'max:40'],
                'contact_email' => ['nullable', 'email', 'max:255'],
                'working_hours_text' => ['nullable', 'string', 'max:255'],
                'organization_description' => ['nullable', 'string', 'max:2000'],
                'footer_text' => ['nullable', 'string', 'max:500'],
            ],
            SystemSettingGroup::LOCALIZATION => [
                'default_locale' => ['required', Rule::in($this->supportedLocales())],
                'supported_locales' => ['required', 'array', 'min:1'],
                'supported_locales.*' => [Rule::in(array_column(LocaleCode::cases(), 'value'))],
                'fallback_locale' => ['required', Rule::in(array_column(LocaleCode::cases(), 'value'))],
                'timezone' => ['required', Rule::in(timezone_identifiers_list())],
                'date_format' => ['required', 'string', 'max:30'],
                'datetime_format' => ['required', 'string', 'max:30'],
            ],
            SystemSettingGroup::NOTIFICATIONS => [
                'database_notifications_enabled' => ['required', 'boolean'],
                'email_notifications_enabled' => ['required', 'boolean'],
                'sms_notifications_enabled' => ['required', 'boolean'],
                'telegram_notifications_enabled' => ['required', 'boolean'],
                'advisory_due_reminder_days' => ['required', 'integer', 'min:1', 'max:30'],
                'hearing_reminder_days' => ['required', 'integer', 'min:1', 'max:30'],
                'appeal_deadline_reminder_days' => ['required', 'integer', 'min:1', 'max:30'],
            ],
            SystemSettingGroup::EMAIL => [
                'mail_from_name' => ['required', 'string', 'max:255'],
                'mail_from_address' => ['required', 'email', 'max:255'],
                'mail_driver_label' => ['nullable', 'string', 'max:100'],
                'mail_host_label' => ['nullable', 'string', 'max:255'],
                'mail_port_label' => ['nullable', 'string', 'max:20'],
            ],
            SystemSettingGroup::SMS => [
                'sms_enabled' => ['required', 'boolean'],
                'provider_name' => ['nullable', 'string', 'max:100'],
                'sender_name' => ['nullable', 'string', 'max:100'],
                'provider_base_url' => ['nullable', 'url', 'max:255'],
                'configuration_notes' => ['nullable', 'string', 'max:1000'],
            ],
            SystemSettingGroup::TELEGRAM => [
                'telegram_enabled' => ['required', 'boolean'],
                'bot_username' => ['nullable', 'string', 'max:100'],
                'default_chat_target' => ['nullable', 'string', 'max:255'],
                'configuration_notes' => ['nullable', 'string', 'max:1000'],
            ],
            SystemSettingGroup::SECURITY => [
                'password_min_length' => ['required', 'integer', 'min:8', 'max:64'],
                'password_complexity_enabled' => ['required', 'boolean'],
                'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
                'allowed_file_types' => ['required', 'string', 'max:255'],
                'max_upload_size_mb' => ['required', 'integer', 'min:1', 'max:50'],
                'maintenance_banner_enabled' => ['required', 'boolean'],
            ],
            SystemSettingGroup::APPEARANCE => [
                'default_theme' => ['required', Rule::in(['light', 'dark'])],
                'allow_user_theme_switching' => ['required', 'boolean'],
                'sidebar_compact_default' => ['required', 'boolean'],
                'table_density' => ['required', Rule::in(['comfortable', 'compact'])],
                'primary_color' => ['required', $this->hexColorRule()],
                'secondary_color' => ['required', $this->hexColorRule()],
                'accent_color' => ['required', $this->hexColorRule()],
                'button_style' => ['required', Rule::in(['pill', 'rounded', 'square'])],
                'card_radius' => ['required', Rule::in(['soft', 'rounded', 'square'])],
            ],
            SystemSettingGroup::PUBLIC_WEBSITE => [
                'hero_eyebrow' => ['nullable', 'string', 'max:120'],
                'hero_title' => ['required', 'string', 'max:255'],
                'hero_description' => ['required', 'string', 'max:2000'],
                'about_title' => ['required', 'string', 'max:255'],
                'about_description' => ['required', 'string', 'max:3000'],
                'services_title' => ['required', 'string', 'max:255'],
                'services_description' => ['required', 'string', 'max:2000'],
                'service_advisory_title' => ['required', 'string', 'max:255'],
                'service_advisory_description' => ['required', 'string', 'max:1000'],
                'service_case_support_title' => ['required', 'string', 'max:255'],
                'service_case_support_description' => ['required', 'string', 'max:1000'],
                'service_policy_title' => ['required', 'string', 'max:255'],
                'service_policy_description' => ['required', 'string', 'max:1000'],
                'process_title' => ['required', 'string', 'max:255'],
                'process_description' => ['required', 'string', 'max:2000'],
                'process_step_one_title' => ['required', 'string', 'max:255'],
                'process_step_one_description' => ['required', 'string', 'max:1000'],
                'process_step_two_title' => ['required', 'string', 'max:255'],
                'process_step_two_description' => ['required', 'string', 'max:1000'],
                'process_step_three_title' => ['required', 'string', 'max:255'],
                'process_step_three_description' => ['required', 'string', 'max:1000'],
                'process_step_four_title' => ['required', 'string', 'max:255'],
                'process_step_four_description' => ['required', 'string', 'max:1000'],
                'posts_title' => ['required', 'string', 'max:255'],
                'posts_description' => ['required', 'string', 'max:1000'],
                'cta_title' => ['required', 'string', 'max:255'],
                'cta_description' => ['required', 'string', 'max:1000'],
                'cta_primary_label' => ['required', 'string', 'max:80'],
                'cta_secondary_label' => ['required', 'string', 'max:80'],
                'contact_title' => ['required', 'string', 'max:255'],
                'contact_description' => ['required', 'string', 'max:1000'],
                'contact_hours_value' => ['required', 'string', 'max:255'],
                'hero_slides' => ['nullable', 'array', 'max:3'],
                'hero_slides.*.title' => ['required', 'string', 'max:255'],
                'hero_slides.*.subtitle' => ['required', 'string', 'max:1000'],
                'hero_slides.*.button_label' => ['required', 'string', 'max:80'],
                'hero_slides.*.button_url' => ['required', 'string', 'max:255', $this->internalNavigationRule()],
                'hero_slides.*.display_order' => ['required', 'integer', 'min:1', 'max:20'],
                'hero_slides.*.is_active' => ['required', 'boolean'],
                'hero_slides.*.image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            ],
        };
    }

    public function attributes(): array
    {
        return [
            'application_name' => __('settings.fields.application_name'),
            'application_short_name' => __('settings.fields.application_short_name'),
            'organization_name' => __('settings.fields.organization_name'),
            'legal_department_name' => __('settings.fields.legal_department_name'),
            'tagline' => __('settings.fields.tagline'),
            'support_email' => __('settings.fields.support_email'),
            'support_phone' => __('settings.fields.support_phone'),
            'default_dashboard_route' => __('settings.fields.default_dashboard_route'),
            'system_logo' => __('settings.fields.system_logo'),
            'favicon' => __('settings.fields.favicon'),
            'office_name' => __('settings.fields.office_name'),
            'address' => __('settings.fields.address'),
            'contact_phone' => __('settings.fields.contact_phone'),
            'contact_email' => __('settings.fields.contact_email'),
            'working_hours_text' => __('settings.fields.working_hours_text'),
            'organization_description' => __('settings.fields.organization_description'),
            'footer_text' => __('settings.fields.footer_text'),
            'default_locale' => __('settings.fields.default_locale'),
            'supported_locales' => __('settings.fields.supported_locales'),
            'fallback_locale' => __('settings.fields.fallback_locale'),
            'timezone' => __('settings.fields.timezone'),
            'date_format' => __('settings.fields.date_format'),
            'datetime_format' => __('settings.fields.datetime_format'),
            'database_notifications_enabled' => __('settings.fields.database_notifications_enabled'),
            'email_notifications_enabled' => __('settings.fields.email_notifications_enabled'),
            'sms_notifications_enabled' => __('settings.fields.sms_notifications_enabled'),
            'telegram_notifications_enabled' => __('settings.fields.telegram_notifications_enabled'),
            'advisory_due_reminder_days' => __('settings.fields.advisory_due_reminder_days'),
            'hearing_reminder_days' => __('settings.fields.hearing_reminder_days'),
            'appeal_deadline_reminder_days' => __('settings.fields.appeal_deadline_reminder_days'),
            'mail_from_name' => __('settings.fields.mail_from_name'),
            'mail_from_address' => __('settings.fields.mail_from_address'),
            'mail_driver_label' => __('settings.fields.mail_driver_label'),
            'mail_host_label' => __('settings.fields.mail_host_label'),
            'mail_port_label' => __('settings.fields.mail_port_label'),
            'provider_name' => __('settings.fields.provider_name'),
            'sender_name' => __('settings.fields.sender_name'),
            'provider_base_url' => __('settings.fields.provider_base_url'),
            'configuration_notes' => __('settings.fields.configuration_notes'),
            'bot_username' => __('settings.fields.bot_username'),
            'default_chat_target' => __('settings.fields.default_chat_target'),
            'password_min_length' => __('settings.fields.password_min_length'),
            'password_complexity_enabled' => __('settings.fields.password_complexity_enabled'),
            'session_timeout_minutes' => __('settings.fields.session_timeout_minutes'),
            'allowed_file_types' => __('settings.fields.allowed_file_types'),
            'max_upload_size_mb' => __('settings.fields.max_upload_size_mb'),
            'maintenance_banner_enabled' => __('settings.fields.maintenance_banner_enabled'),
            'default_theme' => __('settings.fields.default_theme'),
            'allow_user_theme_switching' => __('settings.fields.allow_user_theme_switching'),
            'sidebar_compact_default' => __('settings.fields.sidebar_compact_default'),
            'table_density' => __('settings.fields.table_density'),
            'primary_color' => __('settings.fields.primary_color'),
            'secondary_color' => __('settings.fields.secondary_color'),
            'accent_color' => __('settings.fields.accent_color'),
            'button_style' => __('settings.fields.button_style'),
            'card_radius' => __('settings.fields.card_radius'),
            'hero_eyebrow' => __('settings.fields.hero_eyebrow'),
            'hero_title' => __('settings.fields.hero_title'),
            'hero_description' => __('settings.fields.hero_description'),
            'about_title' => __('settings.fields.about_title'),
            'about_description' => __('settings.fields.about_description'),
            'services_title' => __('settings.fields.services_title'),
            'services_description' => __('settings.fields.services_description'),
            'service_advisory_title' => __('settings.fields.service_advisory_title'),
            'service_advisory_description' => __('settings.fields.service_advisory_description'),
            'service_case_support_title' => __('settings.fields.service_case_support_title'),
            'service_case_support_description' => __('settings.fields.service_case_support_description'),
            'service_policy_title' => __('settings.fields.service_policy_title'),
            'service_policy_description' => __('settings.fields.service_policy_description'),
            'process_title' => __('settings.fields.process_title'),
            'process_description' => __('settings.fields.process_description'),
            'process_step_one_title' => __('settings.fields.process_step_one_title'),
            'process_step_one_description' => __('settings.fields.process_step_one_description'),
            'process_step_two_title' => __('settings.fields.process_step_two_title'),
            'process_step_two_description' => __('settings.fields.process_step_two_description'),
            'process_step_three_title' => __('settings.fields.process_step_three_title'),
            'process_step_three_description' => __('settings.fields.process_step_three_description'),
            'process_step_four_title' => __('settings.fields.process_step_four_title'),
            'process_step_four_description' => __('settings.fields.process_step_four_description'),
            'posts_title' => __('settings.fields.posts_title'),
            'posts_description' => __('settings.fields.posts_description'),
            'cta_title' => __('settings.fields.cta_title'),
            'cta_description' => __('settings.fields.cta_description'),
            'cta_primary_label' => __('settings.fields.cta_primary_label'),
            'cta_secondary_label' => __('settings.fields.cta_secondary_label'),
            'contact_title' => __('settings.fields.contact_title'),
            'contact_description' => __('settings.fields.contact_description'),
            'contact_hours_value' => __('settings.fields.contact_hours_value'),
            'hero_slides' => __('settings.fields.hero_slides'),
            'hero_slides.*.title' => __('settings.fields.slide_title'),
            'hero_slides.*.subtitle' => __('settings.fields.slide_subtitle'),
            'hero_slides.*.button_label' => __('settings.fields.slide_button_label'),
            'hero_slides.*.button_url' => __('settings.fields.slide_button_url'),
            'hero_slides.*.display_order' => __('settings.fields.slide_display_order'),
            'hero_slides.*.is_active' => __('settings.fields.slide_is_active'),
            'hero_slides.*.image' => __('settings.fields.slide_image'),
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($this->group() !== SystemSettingGroup::LOCALIZATION) {
                    return;
                }

                $supportedLocales = array_values(array_unique($this->array('supported_locales')));
                $defaultLocale = (string) $this->input('default_locale');
                $fallbackLocale = (string) $this->input('fallback_locale');

                if (! in_array($defaultLocale, $supportedLocales, true)) {
                    $validator->errors()->add('default_locale', __('validation.in', ['attribute' => __('settings.fields.default_locale')]));
                }

                if (! in_array($fallbackLocale, $supportedLocales, true)) {
                    $validator->errors()->add('fallback_locale', __('validation.in', ['attribute' => __('settings.fields.fallback_locale')]));
                }

                if ($this->group() !== SystemSettingGroup::SECURITY) {
                    return;
                }
            },
            function ($validator): void {
                if ($this->group() !== SystemSettingGroup::SECURITY) {
                    return;
                }

                $supportedTypes = app(SystemSettingsService::class)->supportedUploadFileTypes();
                $requestedTypes = collect(explode(',', (string) $this->input('allowed_file_types')))
                    ->map(fn (string $item): string => trim(strtolower($item)))
                    ->filter()
                    ->values();

                $invalid = $requestedTypes
                    ->reject(fn (string $item): bool => in_array($item, $supportedTypes, true))
                    ->all();

                if ($invalid !== []) {
                    $validator->errors()->add(
                        'allowed_file_types',
                        __('validation.in', ['attribute' => __('settings.fields.allowed_file_types')]),
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleanFields = [
            'database_notifications_enabled',
            'email_notifications_enabled',
            'sms_notifications_enabled',
            'telegram_notifications_enabled',
            'sms_enabled',
            'telegram_enabled',
            'password_complexity_enabled',
            'maintenance_banner_enabled',
            'allow_user_theme_switching',
            'sidebar_compact_default',
            'hero_slides.0.is_active',
            'hero_slides.1.is_active',
            'hero_slides.2.is_active',
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([$field => $this->boolean($field)]);
            }
        }
    }

    private function group(): SystemSettingGroup
    {
        return SystemSettingGroup::from((string) $this->route('group'));
    }

    /**
     * @return array<int, string>
     */
    private function supportedLocales(): array
    {
        return array_column(LocaleCode::cases(), 'value');
    }

    private function hexColorRule(): string
    {
        return 'regex:/^#[0-9A-Fa-f]{6}$/';
    }

    private function internalNavigationRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value)) {
                $fail(__('validation.url', ['attribute' => $attribute]));

                return;
            }

            $normalized = trim($value);

            if (
                preg_match('/^\/[^\s]*$/', $normalized) !== 1
                && preg_match('/^#[A-Za-z0-9_-]+$/', $normalized) !== 1
            ) {
                $fail(__('validation.url', ['attribute' => $attribute]));
            }
        };
    }
}
