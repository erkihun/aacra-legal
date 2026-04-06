<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LocaleCode;
use App\Enums\SystemSettingGroup;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\SafeUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SystemSettingsService
{
    private const CACHE_KEY = 'system-settings.all';

    /**
     * @var array<string, array<int, string>>
     */
    private const SAFE_UPLOAD_TYPES = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $defaults = $this->defaults();

        if (! $this->tableExists()) {
            return $defaults;
        }

        /** @var array<string, array<string, mixed>> $settings */
        $settings = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $items = SystemSetting::query()->get();
            $grouped = [];

            foreach ($items as $item) {
                $group = $item->setting_group instanceof SystemSettingGroup
                    ? $item->setting_group->value
                    : (string) $item->getAttribute('setting_group');

                $grouped[$group][$item->setting_key] = $this->normalizeStoredValue($item->value);
            }

            return $grouped;
        });

        foreach ($settings as $group => $values) {
            $defaults[$group] = [
                ...($defaults[$group] ?? []),
                ...$values,
            ];
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    public function group(SystemSettingGroup|string $group): array
    {
        $groupKey = $group instanceof SystemSettingGroup ? $group->value : $group;

        return $this->all()[$groupKey] ?? [];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function updateGroup(SystemSettingGroup|string $group, array $values): array
    {
        $groupKey = $group instanceof SystemSettingGroup ? $group->value : $group;
        $currentValues = $this->group($groupKey);
        $normalizedValues = $this->normalizeIncomingValues($groupKey, $values, $currentValues);

        DB::transaction(function () use ($groupKey, $normalizedValues): void {
            foreach ($normalizedValues as $key => $value) {
                SystemSetting::query()->updateOrCreate(
                    [
                        'setting_group' => $groupKey,
                        'setting_key' => $key,
                    ],
                    [
                        'value' => $value,
                    ],
                );
            }
        });

        Cache::forget(self::CACHE_KEY);

        return $this->group($groupKey);
    }

    public function applyRuntimeConfiguration(): void
    {
        $general = $this->group(SystemSettingGroup::GENERAL);
        $localization = $this->group(SystemSettingGroup::LOCALIZATION);
        $email = $this->group(SystemSettingGroup::EMAIL);
        $security = $this->group(SystemSettingGroup::SECURITY);
        $telegram = $this->telegramConfiguration();

        Config::set('app.name', $general['application_name'] ?? config('app.name'));
        Config::set('app.locale', $this->defaultLocale());
        Config::set('app.fallback_locale', $this->fallbackLocale());
        Config::set('app.timezone', $this->timezone());
        Config::set('mail.from.name', $email['mail_from_name'] ?? config('mail.from.name'));
        Config::set('mail.from.address', $email['mail_from_address'] ?? config('mail.from.address'));
        Config::set('session.lifetime', max(5, (int) ($security['session_timeout_minutes'] ?? config('session.lifetime', 120))));
        Config::set('services.telegram.driver', $this->telegramDriver($telegram));
        Config::set('services.telegram.bot_token', $telegram['bot_token']);
        Config::set('services.telegram.bot_username', $telegram['bot_username']);
        Config::set('services.telegram.default_chat_target', $telegram['default_chat_target']);
    }

    /**
     * @return array<string, mixed>
     */
    public function appMeta(): array
    {
        $general = $this->group(SystemSettingGroup::GENERAL);
        $organization = $this->group(SystemSettingGroup::ORGANIZATION);
        $localization = $this->group(SystemSettingGroup::LOCALIZATION);
        $security = $this->group(SystemSettingGroup::SECURITY);
        $appearance = $this->group(SystemSettingGroup::APPEARANCE);
        $organizationName = $general['organization_name'] ?? $organization['office_name'] ?? __('app.name');
        $applicationName = $general['application_name'] ?? __('app.name');
        $legalDepartmentName = $general['legal_department_name'] ?? $organizationName;
        $tagline = $general['tagline'] ?? $organization['organization_description'] ?? __('public.home.description');
        $appearanceConfig = $this->appearanceConfig();

        return [
            'application_name' => $applicationName,
            'application_short_name' => $general['application_short_name'] ?? 'LDMS',
            'organization_name' => $organizationName,
            'legal_department_name' => $legalDepartmentName,
            'tagline' => $tagline,
            'default_dashboard_route' => $general['default_dashboard_route'] ?? 'dashboard',
            'logo_url' => $this->assetUrl($general['system_logo_path'] ?? null),
            'favicon_url' => $this->assetUrl($general['favicon_path'] ?? null),
            'stamp_url' => $this->assetUrl($general['stamp_path'] ?? null),
            'support' => [
                'email' => $general['support_email'] ?? $organization['contact_email'] ?? null,
                'phone' => $general['support_phone'] ?? $organization['contact_phone'] ?? null,
            ],
            'footer_text' => $organization['footer_text'] ?? null,
            'organization_description' => $organization['organization_description'] ?? null,
            'organization' => [
                'name' => $organizationName,
                'office_name' => $organization['office_name'] ?? $organizationName,
                'legal_department_name' => $legalDepartmentName,
                'tagline' => $tagline,
                'address' => $organization['address'] ?? null,
                'contact_email' => $organization['contact_email'] ?? $general['support_email'] ?? null,
                'contact_phone' => $organization['contact_phone'] ?? $general['support_phone'] ?? null,
                'working_hours_text' => $organization['working_hours_text'] ?? null,
                'description' => $organization['organization_description'] ?? null,
                'footer_text' => $organization['footer_text'] ?? null,
            ],
            'localization' => [
                'default_locale' => $this->defaultLocale(),
                'supported_locales' => $this->supportedLocales(),
                'fallback_locale' => $this->fallbackLocale(),
                'timezone' => $this->timezone(),
                'date_format' => $localization['date_format'] ?? 'Y-m-d',
                'datetime_format' => $localization['datetime_format'] ?? 'Y-m-d H:i',
            ],
            'security' => [
                'allowed_file_types' => $this->allowedUploadFileTypes(),
                'max_upload_size_mb' => $this->maxUploadSizeMb(),
                'password_min_length' => max(8, (int) ($security['password_min_length'] ?? 8)),
                'password_complexity_enabled' => (bool) ($security['password_complexity_enabled'] ?? false),
                'session_timeout_minutes' => max(5, (int) ($security['session_timeout_minutes'] ?? 120)),
                'maintenance_banner_enabled' => (bool) ($security['maintenance_banner_enabled'] ?? false),
            ],
            'appearance' => [
                ...$appearanceConfig,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function publicWebsiteContent(): array
    {
        $content = $this->group(SystemSettingGroup::PUBLIC_WEBSITE);
        $defaults = $this->publicWebsiteDefaults();

        return [
            'hero_eyebrow' => (string) ($content['hero_eyebrow'] ?? $defaults['hero_eyebrow']),
            'hero_title' => (string) ($content['hero_title'] ?? $defaults['hero_title']),
            'hero_description' => (string) ($content['hero_description'] ?? $defaults['hero_description']),
            'about_title' => (string) ($content['about_title'] ?? $defaults['about_title']),
            'about_description' => (string) ($content['about_description'] ?? $defaults['about_description']),
            'services_title' => (string) ($content['services_title'] ?? $defaults['services_title']),
            'services_description' => (string) ($content['services_description'] ?? $defaults['services_description']),
            'service_advisory_title' => (string) ($content['service_advisory_title'] ?? $defaults['service_advisory_title']),
            'service_advisory_description' => (string) ($content['service_advisory_description'] ?? $defaults['service_advisory_description']),
            'service_case_support_title' => (string) ($content['service_case_support_title'] ?? $defaults['service_case_support_title']),
            'service_case_support_description' => (string) ($content['service_case_support_description'] ?? $defaults['service_case_support_description']),
            'service_policy_title' => (string) ($content['service_policy_title'] ?? $defaults['service_policy_title']),
            'service_policy_description' => (string) ($content['service_policy_description'] ?? $defaults['service_policy_description']),
            'process_title' => (string) ($content['process_title'] ?? $defaults['process_title']),
            'process_description' => (string) ($content['process_description'] ?? $defaults['process_description']),
            'process_step_one_title' => (string) ($content['process_step_one_title'] ?? $defaults['process_step_one_title']),
            'process_step_one_description' => (string) ($content['process_step_one_description'] ?? $defaults['process_step_one_description']),
            'process_step_two_title' => (string) ($content['process_step_two_title'] ?? $defaults['process_step_two_title']),
            'process_step_two_description' => (string) ($content['process_step_two_description'] ?? $defaults['process_step_two_description']),
            'process_step_three_title' => (string) ($content['process_step_three_title'] ?? $defaults['process_step_three_title']),
            'process_step_three_description' => (string) ($content['process_step_three_description'] ?? $defaults['process_step_three_description']),
            'process_step_four_title' => (string) ($content['process_step_four_title'] ?? $defaults['process_step_four_title']),
            'process_step_four_description' => (string) ($content['process_step_four_description'] ?? $defaults['process_step_four_description']),
            'posts_title' => (string) ($content['posts_title'] ?? $defaults['posts_title']),
            'posts_description' => (string) ($content['posts_description'] ?? $defaults['posts_description']),
            'cta_title' => (string) ($content['cta_title'] ?? $defaults['cta_title']),
            'cta_description' => (string) ($content['cta_description'] ?? $defaults['cta_description']),
            'cta_primary_label' => (string) ($content['cta_primary_label'] ?? $defaults['cta_primary_label']),
            'cta_secondary_label' => (string) ($content['cta_secondary_label'] ?? $defaults['cta_secondary_label']),
            'contact_title' => (string) ($content['contact_title'] ?? $defaults['contact_title']),
            'contact_description' => (string) ($content['contact_description'] ?? $defaults['contact_description']),
            'contact_hours_value' => (string) ($content['contact_hours_value'] ?? $defaults['contact_hours_value']),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publicWebsiteSlides(): array
    {
        $configuredSlides = Arr::wrap($this->group(SystemSettingGroup::PUBLIC_WEBSITE)['hero_slides'] ?? []);
        $fallbackSlides = $this->publicWebsiteDefaultSlides();
        $slides = [];

        foreach ($fallbackSlides as $index => $defaultSlide) {
            $configured = Arr::wrap($configuredSlides[$index] ?? []);
            $imagePath = $this->normalizeAssetPath(
                $configured['image_path'] ?? $defaultSlide['image_path'],
                ['branding/'],
            ) ?? $defaultSlide['image_path'];

            $slides[] = [
                'title' => (string) ($configured['title'] ?? $defaultSlide['title']),
                'subtitle' => (string) ($configured['subtitle'] ?? $defaultSlide['subtitle']),
                'button_label' => (string) ($configured['button_label'] ?? $defaultSlide['button_label']),
                'button_url' => SafeUrl::appRelativePath((string) ($configured['button_url'] ?? $defaultSlide['button_url'])) ?? '',
                'display_order' => (int) ($configured['display_order'] ?? $defaultSlide['display_order']),
                'is_active' => (bool) ($configured['is_active'] ?? $defaultSlide['is_active']),
                'image_path' => $imagePath,
                'image_url' => $this->assetUrl($imagePath),
            ];
        }

        usort($slides, fn (array $left, array $right): int => $left['display_order'] <=> $right['display_order']);

        $activeSlides = array_values(array_filter($slides, fn (array $slide): bool => (bool) $slide['is_active']));

        return $activeSlides === [] ? [$slides[0]] : $activeSlides;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publicWebsiteSlidesForSettings(): array
    {
        $configuredSlides = Arr::wrap($this->group(SystemSettingGroup::PUBLIC_WEBSITE)['hero_slides'] ?? []);
        $fallbackSlides = $this->publicWebsiteDefaultSlides();
        $slides = [];

        foreach ($fallbackSlides as $index => $defaultSlide) {
            $configured = Arr::wrap($configuredSlides[$index] ?? []);
            $imagePath = $this->normalizeAssetPath(
                $configured['image_path'] ?? $defaultSlide['image_path'],
                ['branding/'],
            ) ?? $defaultSlide['image_path'];

            $slides[] = [
                'title' => (string) ($configured['title'] ?? $defaultSlide['title']),
                'subtitle' => (string) ($configured['subtitle'] ?? $defaultSlide['subtitle']),
                'button_label' => (string) ($configured['button_label'] ?? $defaultSlide['button_label']),
                'button_url' => SafeUrl::appRelativePath((string) ($configured['button_url'] ?? $defaultSlide['button_url'])) ?? '',
                'display_order' => (int) ($configured['display_order'] ?? $defaultSlide['display_order']),
                'is_active' => (bool) ($configured['is_active'] ?? $defaultSlide['is_active']),
                'image_path' => $imagePath,
                'image_url' => $this->assetUrl($imagePath),
            ];
        }

        return $slides;
    }

    /**
     * @return array<int, string>
     */
    public function supportedLocales(): array
    {
        $validLocales = array_column(LocaleCode::cases(), 'value');
        $configured = Arr::wrap($this->group(SystemSettingGroup::LOCALIZATION)['supported_locales'] ?? ['en', 'am']);
        $supported = array_values(array_intersect($configured, $validLocales));

        return $supported === [] ? ['en', 'am'] : $supported;
    }

    public function defaultLocale(): string
    {
        $configured = (string) ($this->group(SystemSettingGroup::LOCALIZATION)['default_locale'] ?? config('app.locale'));

        return in_array($configured, $this->supportedLocales(), true) ? $configured : $this->supportedLocales()[0];
    }

    public function fallbackLocale(): string
    {
        $configured = (string) ($this->group(SystemSettingGroup::LOCALIZATION)['fallback_locale'] ?? config('app.fallback_locale'));

        return in_array($configured, array_column(LocaleCode::cases(), 'value'), true) ? $configured : 'en';
    }

    public function timezone(): string
    {
        $configured = (string) ($this->group(SystemSettingGroup::LOCALIZATION)['timezone'] ?? config('app.timezone'));

        return in_array($configured, timezone_identifiers_list(), true) ? $configured : config('app.timezone');
    }

    public function notificationsEnabled(string $channel): bool
    {
        $notifications = $this->group(SystemSettingGroup::NOTIFICATIONS);

        return match ($channel) {
            'database' => (bool) ($notifications['database_notifications_enabled'] ?? true),
            'mail' => (bool) ($notifications['email_notifications_enabled'] ?? true),
            'sms' => (bool) ($notifications['sms_notifications_enabled'] ?? true)
                && (bool) ($this->group(SystemSettingGroup::SMS)['sms_enabled'] ?? true),
            'telegram' => (bool) ($notifications['telegram_notifications_enabled'] ?? true)
                && (bool) ($this->group(SystemSettingGroup::TELEGRAM)['telegram_enabled'] ?? true),
            default => false,
        };
    }

    public function defaultDashboardRouteFor(?User $user = null): string
    {
        $configured = (string) ($this->group(SystemSettingGroup::GENERAL)['default_dashboard_route'] ?? 'dashboard');

        if (! in_array($configured, ['dashboard', 'reports.index', 'notifications.index'], true)) {
            return 'dashboard';
        }

        if ($configured === 'reports.index' && ! ($user?->can('reports.view') ?? false)) {
            return 'dashboard';
        }

        return $configured;
    }

    public function hearingReminderDays(): int
    {
        return max(1, (int) ($this->group(SystemSettingGroup::NOTIFICATIONS)['hearing_reminder_days'] ?? 3));
    }

    public function advisoryDueReminderDays(): int
    {
        return max(1, (int) ($this->group(SystemSettingGroup::NOTIFICATIONS)['advisory_due_reminder_days'] ?? 2));
    }

    public function appealDeadlineReminderDays(): int
    {
        return max(1, (int) ($this->group(SystemSettingGroup::NOTIFICATIONS)['appeal_deadline_reminder_days'] ?? 5));
    }

    /**
     * @return array<int, string>
     */
    public function allowedUploadFileTypes(): array
    {
        $configured = $this->group(SystemSettingGroup::SECURITY)['allowed_file_types'] ?? ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
        $sanitized = collect(Arr::wrap($configured))
            ->map(fn (mixed $item): string => Str::of((string) $item)->trim()->lower()->toString())
            ->filter(fn (string $item): bool => array_key_exists($item, self::SAFE_UPLOAD_TYPES))
            ->unique()
            ->values()
            ->all();

        return $sanitized === [] ? ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'] : $sanitized;
    }

    /**
     * @return array<int, string>
     */
    public function allowedUploadMimeTypes(): array
    {
        return collect($this->allowedUploadFileTypes())
            ->flatMap(fn (string $extension): array => self::SAFE_UPLOAD_TYPES[$extension] ?? [])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function supportedUploadFileTypes(): array
    {
        return array_keys(self::SAFE_UPLOAD_TYPES);
    }

    public function maxUploadSizeMb(): int
    {
        return max(1, (int) ($this->group(SystemSettingGroup::SECURITY)['max_upload_size_mb'] ?? 10));
    }

    /**
     * @return array{enabled: bool, bot_token: ?string, bot_username: ?string, default_chat_target: ?string, has_bot_token: bool}
     */
    public function telegramConfiguration(): array
    {
        $telegram = $this->group(SystemSettingGroup::TELEGRAM);
        $configuredToken = trim((string) ($telegram['bot_token'] ?? ''));
        $fallbackToken = trim((string) config('services.telegram.bot_token'));
        $botToken = $configuredToken !== '' ? $configuredToken : ($fallbackToken !== '' ? $fallbackToken : null);
        $botUsername = trim((string) ($telegram['bot_username'] ?? config('services.telegram.bot_username', '')));
        $defaultChatTarget = trim((string) ($telegram['default_chat_target'] ?? config('services.telegram.default_chat_target', '')));

        return [
            'enabled' => (bool) ($telegram['telegram_enabled'] ?? true),
            'bot_token' => $botToken,
            'bot_username' => $botUsername !== '' ? $botUsername : null,
            'default_chat_target' => $defaultChatTarget !== '' ? $defaultChatTarget : null,
            'has_bot_token' => $botToken !== null,
        ];
    }

    public function telegramBotToken(): ?string
    {
        return $this->telegramConfiguration()['bot_token'];
    }

    public function hasTelegramBotToken(): bool
    {
        return $this->telegramConfiguration()['has_bot_token'];
    }

    public function maskedTelegramBotToken(): ?string
    {
        $token = $this->telegramBotToken();

        if ($token === null || $token === '') {
            return null;
        }

        return Str::mask($token, '*', 4, max(strlen($token) - 8, 0));
    }

    /**
     * @return array<string, mixed>
     */
    public function telegramSettingsForSettings(): array
    {
        $telegram = $this->group(SystemSettingGroup::TELEGRAM);

        return [
            'telegram_enabled' => (bool) ($telegram['telegram_enabled'] ?? true),
            'bot_username' => (string) ($telegram['bot_username'] ?? ''),
            'bot_token' => '',
            'bot_token_masked' => $this->maskedTelegramBotToken(),
            'bot_token_configured' => $this->hasTelegramBotToken(),
            'default_chat_target' => (string) ($telegram['default_chat_target'] ?? ''),
            'configuration_notes' => (string) ($telegram['configuration_notes'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $telegram
     */
    private function telegramDriver(array $telegram): string
    {
        $configuredDriver = trim((string) config('services.telegram.driver', ''));

        if ($configuredDriver === 'null') {
            return 'null';
        }

        if (($telegram['has_bot_token'] ?? false) === true) {
            return 'api';
        }

        return $configuredDriver !== '' ? $configuredDriver : 'log';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function defaults(): array
    {
        return [
            SystemSettingGroup::GENERAL->value => [
                'application_name' => __('app.name'),
                'application_short_name' => 'LDMS',
                'organization_name' => __('app.name'),
                'legal_department_name' => __('app.name'),
                'tagline' => __('public.home.description'),
                'support_email' => 'support@example.test',
                'support_phone' => '',
                'default_dashboard_route' => 'dashboard',
                'system_logo_path' => null,
                'favicon_path' => null,
                'stamp_path' => null,
            ],
            SystemSettingGroup::ORGANIZATION->value => [
                'office_name' => __('app.name'),
                'address' => '',
                'contact_phone' => '',
                'contact_email' => '',
                'working_hours_text' => '',
                'organization_description' => '',
                'footer_text' => '',
            ],
            SystemSettingGroup::LOCALIZATION->value => [
                'default_locale' => config('app.locale', 'en'),
                'supported_locales' => ['en', 'am'],
                'fallback_locale' => config('app.fallback_locale', 'en'),
                'timezone' => config('app.timezone', 'Africa/Addis_Ababa'),
                'date_format' => 'Y-m-d',
                'datetime_format' => 'Y-m-d H:i',
            ],
            SystemSettingGroup::NOTIFICATIONS->value => [
                'database_notifications_enabled' => true,
                'email_notifications_enabled' => true,
                'sms_notifications_enabled' => true,
                'telegram_notifications_enabled' => true,
                'advisory_due_reminder_days' => 2,
                'hearing_reminder_days' => 3,
                'appeal_deadline_reminder_days' => 5,
            ],
            SystemSettingGroup::EMAIL->value => [
                'mail_from_name' => config('mail.from.name', config('app.name')),
                'mail_from_address' => config('mail.from.address', 'hello@example.com'),
                'mail_driver_label' => config('mail.default', 'log'),
                'mail_host_label' => '',
                'mail_port_label' => '',
            ],
            SystemSettingGroup::SMS->value => [
                'sms_enabled' => true,
                'provider_name' => '',
                'sender_name' => '',
                'provider_base_url' => '',
                'configuration_notes' => '',
            ],
            SystemSettingGroup::TELEGRAM->value => [
                'telegram_enabled' => true,
                'bot_username' => '',
                'bot_token' => null,
                'default_chat_target' => '',
                'configuration_notes' => '',
            ],
            SystemSettingGroup::SECURITY->value => [
                'password_min_length' => 8,
                'password_complexity_enabled' => false,
                'session_timeout_minutes' => 120,
                'allowed_file_types' => ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'],
                'max_upload_size_mb' => 10,
                'maintenance_banner_enabled' => false,
            ],
            SystemSettingGroup::APPEARANCE->value => [
                'default_theme' => 'light',
                'allow_user_theme_switching' => true,
                'sidebar_compact_default' => false,
                'table_density' => 'comfortable',
                'primary_color' => '#0f766e',
                'secondary_color' => '#0ea5e9',
                'accent_color' => '#f59e0b',
                'button_style' => 'pill',
                'card_radius' => 'soft',
            ],
            SystemSettingGroup::PUBLIC_WEBSITE->value => [
                ...$this->publicWebsiteDefaults(),
                'hero_slides' => $this->publicWebsiteDefaultSlides(),
            ],
        ];
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('system_settings');
        } catch (Throwable) {
            return false;
        }
    }

    private function normalizeStoredValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists('value', $value) && count($value) === 1) {
            return $value['value'];
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $currentValues
     * @return array<string, mixed>
     */
    private function normalizeIncomingValues(string $group, array $values, array $currentValues): array
    {
        if (isset($values['system_logo']) && $values['system_logo'] instanceof UploadedFile) {
            $values['system_logo_path'] = $this->storeAsset(
                $values['system_logo'],
                'logo',
                $currentValues['system_logo_path'] ?? null,
            );
        }

        if (isset($values['favicon']) && $values['favicon'] instanceof UploadedFile) {
            $values['favicon_path'] = $this->storeAsset(
                $values['favicon'],
                'favicon',
                $currentValues['favicon_path'] ?? null,
            );
        }

        if (isset($values['stamp']) && $values['stamp'] instanceof UploadedFile) {
            $values['stamp_path'] = $this->storeAsset(
                $values['stamp'],
                'stamp',
                $currentValues['stamp_path'] ?? null,
            );
        }

        unset($values['system_logo'], $values['favicon'], $values['stamp']);

        if ($group === SystemSettingGroup::SECURITY->value && isset($values['allowed_file_types']) && is_string($values['allowed_file_types'])) {
            $values['allowed_file_types'] = collect(explode(',', $values['allowed_file_types']))
                ->map(fn (string $item): string => Str::of($item)->trim()->lower()->toString())
                ->filter(fn (string $item): bool => array_key_exists($item, self::SAFE_UPLOAD_TYPES))
                ->unique()
                ->values()
                ->all();
        }

        if ($group === SystemSettingGroup::LOCALIZATION->value && isset($values['supported_locales'])) {
            $values['supported_locales'] = array_values(array_unique(Arr::wrap($values['supported_locales'])));
        }

        if ($group === SystemSettingGroup::TELEGRAM->value) {
            $incomingBotToken = trim((string) ($values['bot_token'] ?? ''));

            $values['bot_username'] = trim((string) ($values['bot_username'] ?? ''));
            $values['default_chat_target'] = trim((string) ($values['default_chat_target'] ?? ''));
            $values['configuration_notes'] = trim((string) ($values['configuration_notes'] ?? ''));

            if ($incomingBotToken !== '') {
                $values['bot_token'] = $incomingBotToken;
            } else {
                $values['bot_token'] = $currentValues['bot_token'] ?? null;
            }
        }

        if ($group === SystemSettingGroup::APPEARANCE->value) {
            foreach (['primary_color', 'secondary_color', 'accent_color'] as $colorField) {
                if (isset($values[$colorField])) {
                    $values[$colorField] = $this->normalizeHexColor((string) $values[$colorField], (string) ($currentValues[$colorField] ?? $this->defaults()[SystemSettingGroup::APPEARANCE->value][$colorField]));
                }
            }
        }

        if ($group === SystemSettingGroup::PUBLIC_WEBSITE->value && isset($values['hero_slides']) && is_array($values['hero_slides'])) {
            $currentSlides = Arr::wrap($currentValues['hero_slides'] ?? []);

            $values['hero_slides'] = collect($values['hero_slides'])
                ->map(function (mixed $slide, int $index) use ($currentSlides): array {
                    $slideData = Arr::wrap($slide);
                    $currentSlide = Arr::wrap($currentSlides[$index] ?? []);

                    if (($slideData['image'] ?? null) instanceof UploadedFile) {
                        $slideData['image_path'] = $this->storeAsset(
                            $slideData['image'],
                            "hero-slide-{$index}",
                            is_string($currentSlide['image_path'] ?? null)
                                ? $this->normalizeAssetPath($currentSlide['image_path'], ['branding/'])
                                : null,
                        );
                    } else {
                        $slideData['image_path'] = $this->normalizeAssetPath(
                            $slideData['image_path'] ?? $currentSlide['image_path'] ?? null,
                            ['branding/'],
                        );
                    }

                    unset($slideData['image'], $slideData['image_url']);

                    return [
                        'title' => (string) ($slideData['title'] ?? ''),
                        'subtitle' => (string) ($slideData['subtitle'] ?? ''),
                        'button_label' => (string) ($slideData['button_label'] ?? ''),
                        'button_url' => SafeUrl::appRelativePath((string) ($slideData['button_url'] ?? '')) ?? '',
                        'display_order' => max(1, (int) ($slideData['display_order'] ?? ($index + 1))),
                        'is_active' => (bool) ($slideData['is_active'] ?? true),
                        'image_path' => $slideData['image_path'],
                    ];
                })
                ->values()
                ->all();
        }

        return $values;
    }

    private function storeAsset(UploadedFile $file, string $prefix, ?string $oldPath): string
    {
        if ($oldPath !== null) {
            Storage::disk('public')->delete($oldPath);
        }

        $extension = $file->extension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $sanitizedName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $prefix;
        $storedName = "{$prefix}-{$sanitizedName}-".Str::lower((string) Str::uuid()).".{$extension}";

        return $file->storePubliclyAs('branding', $storedName, 'public');
    }

    private function assetUrl(?string $path): ?string
    {
        $normalizedPath = $this->normalizeAssetPath($path, ['branding/', 'public-posts/']);

        if ($normalizedPath === null || $normalizedPath === '') {
            return null;
        }

        if (Str::startsWith($normalizedPath, '/')) {
            return url($normalizedPath);
        }

        return route('branding-assets.show', ['path' => $normalizedPath]);
    }

    /**
     * @param  array<int, string>  $allowedStoragePrefixes
     */
    private function normalizeAssetPath(mixed $path, array $allowedStoragePrefixes): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $normalized = trim($path);

        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, '/images/')) {
            return $normalized;
        }

        $pathOnly = parse_url($normalized, PHP_URL_PATH);
        $candidate = is_string($pathOnly) && $pathOnly !== '' ? trim($pathOnly) : $normalized;

        foreach (['/branding-assets/', 'branding-assets/'] as $prefix) {
            if (str_starts_with($candidate, $prefix)) {
                $candidate = Str::after($candidate, $prefix);
                break;
            }
        }

        foreach (['/storage/', 'storage/'] as $prefix) {
            if (str_starts_with($candidate, $prefix)) {
                $candidate = Str::after($candidate, $prefix);
                break;
            }
        }

        $storagePath = SafeUrl::storageAssetPath($candidate, $allowedStoragePrefixes);

        if ($storagePath !== null) {
            return $storagePath;
        }

        return SafeUrl::appRelativePath($candidate);
    }

    /**
     * @return array<string, string>
     */
    private function publicWebsiteDefaults(): array
    {
        return [
            'hero_eyebrow' => __('public.home.eyebrow'),
            'hero_title' => __('public.home.heading'),
            'hero_description' => __('public.home.description'),
            'about_title' => __('public.about.title'),
            'about_description' => __('public.about.description'),
            'services_title' => __('public.services.title'),
            'services_description' => __('public.services.description'),
            'service_advisory_title' => __('public.services.advisory_title'),
            'service_advisory_description' => __('public.services.advisory_description'),
            'service_case_support_title' => __('public.services.case_support_title'),
            'service_case_support_description' => __('public.services.case_support_description'),
            'service_policy_title' => __('public.services.policy_title'),
            'service_policy_description' => __('public.services.policy_description'),
            'process_title' => __('public.process.title'),
            'process_description' => __('public.process.description'),
            'process_step_one_title' => __('public.process.step_one_title'),
            'process_step_one_description' => __('public.process.step_one_description'),
            'process_step_two_title' => __('public.process.step_two_title'),
            'process_step_two_description' => __('public.process.step_two_description'),
            'process_step_three_title' => __('public.process.step_three_title'),
            'process_step_three_description' => __('public.process.step_three_description'),
            'process_step_four_title' => __('public.process.step_four_title'),
            'process_step_four_description' => __('public.process.step_four_description'),
            'posts_title' => __('public.posts.title'),
            'posts_description' => __('public.posts.description'),
            'cta_title' => __('public.cta.title'),
            'cta_description' => __('public.cta.description'),
            'cta_primary_label' => __('public.actions.submit_request'),
            'cta_secondary_label' => __('public.actions.track_requests'),
            'contact_title' => __('public.contact.title'),
            'contact_description' => __('public.contact.description'),
            'contact_hours_value' => __('public.contact.hours_value'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appearanceConfig(): array
    {
        $appearance = $this->group(SystemSettingGroup::APPEARANCE);
        $defaults = $this->defaults()[SystemSettingGroup::APPEARANCE->value];

        return [
            'default_theme' => $appearance['default_theme'] ?? 'light',
            'allow_user_theme_switching' => (bool) ($appearance['allow_user_theme_switching'] ?? true),
            'sidebar_compact_default' => (bool) ($appearance['sidebar_compact_default'] ?? false),
            'table_density' => $appearance['table_density'] ?? 'comfortable',
            'primary_color' => $this->normalizeHexColor((string) ($appearance['primary_color'] ?? $defaults['primary_color']), (string) $defaults['primary_color']),
            'secondary_color' => $this->normalizeHexColor((string) ($appearance['secondary_color'] ?? $defaults['secondary_color']), (string) $defaults['secondary_color']),
            'accent_color' => $this->normalizeHexColor((string) ($appearance['accent_color'] ?? $defaults['accent_color']), (string) $defaults['accent_color']),
            'button_style' => in_array($appearance['button_style'] ?? null, ['pill', 'rounded', 'square'], true)
                ? $appearance['button_style']
                : $defaults['button_style'],
            'card_radius' => in_array($appearance['card_radius'] ?? null, ['soft', 'rounded', 'square'], true)
                ? $appearance['card_radius']
                : $defaults['card_radius'],
        ];
    }

    private function normalizeHexColor(string $value, string $fallback): string
    {
        $normalized = Str::of($value)->trim()->lower()->toString();

        return preg_match('/^#[0-9a-f]{6}$/', $normalized) === 1 ? $normalized : $fallback;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function publicWebsiteDefaultSlides(): array
    {
        return [
            [
                'title' => __('public.slider.slide_one_title'),
                'subtitle' => __('public.slider.slide_one_subtitle'),
                'button_label' => __('public.slider.slide_one_button'),
                'button_url' => '/register',
                'display_order' => 1,
                'is_active' => true,
                'image_path' => '/images/home/hero-slide-1.svg',
            ],
            [
                'title' => __('public.slider.slide_two_title'),
                'subtitle' => __('public.slider.slide_two_subtitle'),
                'button_label' => __('public.slider.slide_two_button'),
                'button_url' => '/login',
                'display_order' => 2,
                'is_active' => true,
                'image_path' => '/images/home/hero-slide-2.svg',
            ],
            [
                'title' => __('public.slider.slide_three_title'),
                'subtitle' => __('public.slider.slide_three_subtitle'),
                'button_label' => __('public.slider.slide_three_button'),
                'button_url' => '/updates',
                'display_order' => 3,
                'is_active' => true,
                'image_path' => '/images/home/hero-slide-3.svg',
            ],
        ];
    }
}
