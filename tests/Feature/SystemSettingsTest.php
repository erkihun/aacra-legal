<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\PriorityLevel;
use App\Enums\WorkflowStage;
use App\Events\AdvisoryAssigned;
use App\Jobs\SendSmsMessageJob;
use App\Jobs\SendTelegramMessageJob;
use App\Listeners\SendAdvisoryAssignedNotifications;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\Department;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemSettingsService;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('public');
    Storage::fake('local');

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('allows authorized admins to access system settings', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/SystemSettings/Index')
            ->has('groups', 10)
            ->where('settingsGroups.general.application_short_name', 'LDMS')
            ->has('settingsGroups.public_website.hero_slides', 3));
});

it('denies unauthorized users from accessing system settings', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();

    $this->actingAs($requester)
        ->get(route('settings.index'))
        ->assertForbidden();
});

it('updates general settings successfully including branding assets', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('settings.update', 'general'), [
            '_method' => 'put',
            'application_name' => 'LDMS RC',
            'application_short_name' => 'LDMS-RC',
            'organization_name' => 'Institution Legal Office',
            'legal_department_name' => 'Institution Legal Department',
            'tagline' => 'Trusted legal operations for institutional decisions.',
            'support_email' => 'support@ldms.test',
            'support_phone' => '+251900000001',
            'default_dashboard_route' => 'dashboard',
            'system_logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            'favicon' => UploadedFile::fake()->image('favicon.png', 48, 48),
            'stamp' => UploadedFile::fake()->image('stamp.png', 160, 160),
        ])
        ->assertRedirect();

    $settings = app(SystemSettingsService::class);
    $general = $settings->group('general');

    expect($general['application_name'])->toBe('LDMS RC')
        ->and($general['application_short_name'])->toBe('LDMS-RC')
        ->and($general['legal_department_name'])->toBe('Institution Legal Department')
        ->and($general['tagline'])->toBe('Trusted legal operations for institutional decisions.')
        ->and($general['support_email'])->toBe('support@ldms.test')
        ->and($general['system_logo_path'])->not()->toBeNull()
        ->and($general['favicon_path'])->not()->toBeNull()
        ->and($general['stamp_path'])->not()->toBeNull();

    Storage::disk('public')->assertExists($general['system_logo_path']);
    Storage::disk('public')->assertExists($general['favicon_path']);
    Storage::disk('public')->assertExists($general['stamp_path']);

    $appMeta = $settings->appMeta();
    $logoPath = parse_url($appMeta['logo_url'], PHP_URL_PATH);
    $faviconPath = parse_url($appMeta['favicon_url'], PHP_URL_PATH);
    $stampPath = parse_url($appMeta['stamp_url'], PHP_URL_PATH);

    expect($logoPath)->toContain('/branding-assets/')
        ->and($faviconPath)->toContain('/branding-assets/')
        ->and($stampPath)->toContain('/branding-assets/');

    $this->get($logoPath)->assertOk();
    $this->get($faviconPath)->assertOk();
    $this->get($stampPath)->assertOk();
});

it('persists localization and notification settings', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('settings.update', 'localization'), [
            'default_locale' => 'am',
            'supported_locales' => ['en', 'am'],
            'fallback_locale' => 'en',
            'timezone' => 'Africa/Addis_Ababa',
            'date_format' => 'd/m/Y',
            'datetime_format' => 'd/m/Y H:i',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->put(route('settings.update', 'notifications'), [
            'database_notifications_enabled' => true,
            'email_notifications_enabled' => false,
            'sms_notifications_enabled' => false,
            'telegram_notifications_enabled' => true,
            'advisory_due_reminder_days' => 4,
            'hearing_reminder_days' => 6,
            'appeal_deadline_reminder_days' => 7,
        ])
        ->assertRedirect();

    $settings = app(SystemSettingsService::class);

    expect($settings->defaultLocale())->toBe('am')
        ->and($settings->supportedLocales())->toBe(['en', 'am'])
        ->and($settings->group('localization')['date_format'])->toBe('d/m/Y')
        ->and($settings->notificationsEnabled('mail'))->toBeFalse()
        ->and($settings->notificationsEnabled('telegram'))->toBeTrue()
        ->and($settings->advisoryDueReminderDays())->toBe(4)
        ->and($settings->hearingReminderDays())->toBe(6)
        ->and($settings->appealDeadlineReminderDays())->toBe(7);
});

it('applies the configured default locale and supported locales to guest responses', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('settings.update', 'localization'), [
            'default_locale' => 'am',
            'supported_locales' => ['am'],
            'fallback_locale' => 'am',
            'timezone' => 'Africa/Addis_Ababa',
            'date_format' => 'd/m/Y',
            'datetime_format' => 'd/m/Y H:i',
        ])
        ->assertRedirect();

    auth()->logout();

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', 'am')
            ->has('availableLocales', 1)
            ->where('availableLocales.0.value', 'am')
            ->where('appMeta.localization.default_locale', 'am')
            ->where('appMeta.localization.supported_locales.0', 'am'));
});

it('exposes branding values in app responses and uses the configured default dashboard route on login', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('settings.update', 'general'), [
            'application_name' => 'Institution Legal Workspace',
            'application_short_name' => 'ILW',
            'organization_name' => 'Institution Legal Office',
            'legal_department_name' => 'Institution Legal Department',
            'tagline' => 'Institution-wide legal coordination and advisory.',
            'support_email' => 'support@ilw.test',
            'support_phone' => '+251900000001',
            'default_dashboard_route' => 'notifications.index',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->put(route('settings.update', 'organization'), [
            'office_name' => 'Institution Legal Office',
            'address' => 'Addis Ababa',
            'contact_phone' => '+251911000000',
            'contact_email' => 'office@ilw.test',
            'working_hours_text' => 'Mon-Fri 8:00-17:00',
            'organization_description' => 'Institutional legal operations center.',
            'footer_text' => 'Institution Legal Office footer.',
        ])
        ->assertRedirect();

    auth()->logout();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Home')
            ->where('appMeta.application_name', 'Institution Legal Workspace')
            ->where('appMeta.application_short_name', 'ILW')
            ->where('appMeta.organization_name', 'Institution Legal Office')
            ->where('appMeta.legal_department_name', 'Institution Legal Department')
            ->where('appMeta.tagline', 'Institution-wide legal coordination and advisory.')
            ->where('appMeta.support.email', 'support@ilw.test')
            ->where('appMeta.footer_text', 'Institution Legal Office footer.')
            ->where('appMeta.default_dashboard_route', 'notifications.index'));

    $this->post(route('login'), [
        'email' => 'admin@ldms.test',
        'password' => 'password',
    ])->assertRedirect(route('notifications.index', absolute: false));
});

it('exposes branding asset URLs in system settings and layout responses after upload', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('settings.update', 'general'), [
            '_method' => 'put',
            'application_name' => 'Branded Workspace',
            'application_short_name' => 'BW',
            'organization_name' => 'Legal Office',
            'legal_department_name' => 'Legal Affairs Directorate',
            'tagline' => 'Deployment branding verification',
            'support_email' => 'support@brand.test',
            'support_phone' => '+251900000010',
            'default_dashboard_route' => 'dashboard',
            'system_logo' => UploadedFile::fake()->image('brand-logo.png', 120, 120),
            'favicon' => UploadedFile::fake()->image('brand-favicon.png', 48, 48),
            'stamp' => UploadedFile::fake()->image('brand-stamp.png', 160, 160),
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settingsGroups.general.application_name', 'Branded Workspace')
            ->where('settingsGroups.general.application_short_name', 'BW')
            ->where('settingsGroups.general.system_logo_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/'))
            ->where('settingsGroups.general.favicon_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/'))
            ->where('settingsGroups.general.stamp_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/'))
            ->where('appMeta.logo_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/'))
            ->where('appMeta.favicon_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/'))
            ->where('appMeta.stamp_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/')));

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appMeta.logo_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/'))
            ->where('appMeta.favicon_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/'))
            ->where('appMeta.stamp_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/')));
});

it('validates the system settings stamp as a png upload', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('settings.update', 'general'), [
            '_method' => 'put',
            'application_name' => 'LDMS RC',
            'application_short_name' => 'LDMS-RC',
            'organization_name' => 'Institution Legal Office',
            'legal_department_name' => 'Institution Legal Department',
            'tagline' => 'Trusted legal operations for institutional decisions.',
            'support_email' => 'support@ldms.test',
            'support_phone' => '+251900000001',
            'default_dashboard_route' => 'dashboard',
            'stamp' => UploadedFile::fake()->image('stamp.jpg', 160, 160),
        ])
        ->assertSessionHasErrors('stamp');
});

it('respects notification channel toggles for database, sms, and telegram delivery', function (): void {
    Queue::fake();

    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $assignee = User::factory()->create([
        'department_id' => Department::query()->where('code', 'LEG')->firstOrFail()->id,
        'email' => 'settings-assignee@ldms.test',
        'phone' => '+251911000123',
        'telegram_chat_id' => 'settings-assignee-chat',
    ]);

    $this->actingAs($admin)
        ->put(route('settings.update', 'notifications'), [
            'database_notifications_enabled' => false,
            'email_notifications_enabled' => false,
            'sms_notifications_enabled' => false,
            'telegram_notifications_enabled' => false,
            'advisory_due_reminder_days' => 2,
            'hearing_reminder_days' => 3,
            'appeal_deadline_reminder_days' => 5,
        ])
        ->assertRedirect();

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-SET-0001',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Settings notification toggle test',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::MEDIUM,
        'description' => 'Ensure notification settings suppress outbound channels.',
        'date_submitted' => now()->toDateString(),
    ]);

    app(SendAdvisoryAssignedNotifications::class)->handle(
        new AdvisoryAssigned($advisoryRequest, $assignee, $admin),
    );

    expect($assignee->notifications()->count())->toBe(0);
    Queue::assertNotPushed(SendSmsMessageJob::class);
    Queue::assertNotPushed(SendTelegramMessageJob::class);
});

it('enforces upload restrictions from security settings during attachment upload flows', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('settings.update', 'security'), [
            'password_min_length' => 8,
            'password_complexity_enabled' => false,
            'session_timeout_minutes' => 120,
            'allowed_file_types' => 'pdf',
            'max_upload_size_mb' => 1,
            'maintenance_banner_enabled' => false,
        ])
        ->assertRedirect();

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-SET-0002',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Settings upload restriction test',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'description' => 'Ensure file settings are enforced.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($requester)
        ->post(route('advisory.attachments.store', $advisoryRequest), [
            'attachments' => [
                UploadedFile::fake()->image('diagram.png'),
            ],
        ])
        ->assertSessionHasErrors('attachments.0');

    $this->actingAs($requester)
        ->post(route('advisory.attachments.store', $advisoryRequest), [
            'attachments' => [
                UploadedFile::fake()->create('oversized.pdf', 2049, 'application/pdf'),
            ],
        ])
        ->assertSessionHasErrors('attachments.0');
});

it('applies security and appearance settings to shared app metadata and runtime configuration', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('settings.update', 'security'), [
            'password_min_length' => 12,
            'password_complexity_enabled' => true,
            'session_timeout_minutes' => 45,
            'allowed_file_types' => 'pdf, docx',
            'max_upload_size_mb' => 4,
            'maintenance_banner_enabled' => true,
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->put(route('settings.update', 'appearance'), [
            'default_theme' => 'dark',
            'allow_user_theme_switching' => false,
            'sidebar_compact_default' => true,
            'table_density' => 'compact',
            'primary_color' => '#155e75',
            'secondary_color' => '#1d4ed8',
            'accent_color' => '#c2410c',
            'button_style' => 'rounded',
            'card_radius' => 'rounded',
        ])
        ->assertRedirect();

    app(SystemSettingsService::class)->applyRuntimeConfiguration();

    expect(config('session.lifetime'))->toBe(45);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appMeta.security.maintenance_banner_enabled', true)
            ->where('appMeta.security.session_timeout_minutes', 45)
            ->where('appMeta.appearance.default_theme', 'dark')
            ->where('appMeta.appearance.allow_user_theme_switching', false)
            ->where('appMeta.appearance.sidebar_compact_default', true)
            ->where('appMeta.appearance.table_density', 'compact')
            ->where('appMeta.appearance.primary_color', '#155e75')
            ->where('appMeta.appearance.secondary_color', '#1d4ed8')
            ->where('appMeta.appearance.accent_color', '#c2410c')
            ->where('appMeta.appearance.button_style', 'rounded')
            ->where('appMeta.appearance.card_radius', 'rounded'));
});

it('validates bounded deployment branding values before saving appearance settings', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('settings.update', 'appearance'), [
            'default_theme' => 'dark',
            'allow_user_theme_switching' => true,
            'sidebar_compact_default' => false,
            'table_density' => 'comfortable',
            'primary_color' => 'teal',
            'secondary_color' => '#12345',
            'accent_color' => '#abcdef',
            'button_style' => 'freeform',
            'card_radius' => 'giant',
        ])
        ->assertSessionHasErrors([
            'primary_color',
            'secondary_color',
            'button_style',
            'card_radius',
        ]);
});

it('persists public website content blocks for a single deployment and exposes them publicly', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('settings.update', 'public_website'), [
            '_method' => 'put',
            'hero_eyebrow' => 'Institutional legal support',
            'hero_title' => 'Legal services tailored to one institution',
            'hero_description' => 'This deployment is managed for one organization only.',
            'about_title' => 'About our legal department',
            'about_description' => 'A dedicated in-house legal operations center.',
            'services_title' => 'Legal services',
            'services_description' => 'Structured advisory, litigation follow-up, and policy guidance.',
            'service_advisory_title' => 'Advisory requests',
            'service_advisory_description' => 'Centralized review and response workflow.',
            'service_case_support_title' => 'Court case follow-up',
            'service_case_support_description' => 'Track hearings, filings, and outcomes.',
            'service_policy_title' => 'Policy guidance',
            'service_policy_description' => 'Support policy and compliance interpretation.',
            'process_title' => 'How work moves',
            'process_description' => 'Every request follows a traceable approval and assignment chain.',
            'process_step_one_title' => 'Submit',
            'process_step_one_description' => 'Department requester submits a matter.',
            'process_step_two_title' => 'Review',
            'process_step_two_description' => 'Director reviews and routes the request.',
            'process_step_three_title' => 'Assign',
            'process_step_three_description' => 'Team leader assigns to the right expert.',
            'process_step_four_title' => 'Respond',
            'process_step_four_description' => 'Expert records the legal response.',
            'posts_title' => 'Legal updates',
            'posts_description' => 'Read official legal department updates and notices.',
            'cta_title' => 'Work with the legal department',
            'cta_description' => 'Use the dedicated portal and request workflow for this institution.',
            'cta_primary_label' => 'Open request portal',
            'cta_secondary_label' => 'View request progress',
            'contact_title' => 'Contact the legal department',
            'contact_description' => 'Use the verified communication channels for this deployment.',
            'contact_hours_value' => 'Mon-Fri 08:00-17:00',
            'hero_slides' => [
                [
                    'title' => 'Trusted legal operations',
                    'subtitle' => 'Governed workflow for institutional legal support.',
                    'button_label' => 'Open updates',
                    'button_url' => '/updates',
                    'display_order' => 1,
                    'is_active' => true,
                ],
                [
                    'title' => 'Litigation follow-up',
                    'subtitle' => 'Track court activity with accountability.',
                    'button_label' => 'View services',
                    'button_url' => '/#services',
                    'display_order' => 2,
                    'is_active' => true,
                ],
                [
                    'title' => 'Advisory workflow',
                    'subtitle' => 'Submit and follow requests securely.',
                    'button_label' => 'Contact us',
                    'button_url' => '/#contact',
                    'display_order' => 3,
                    'is_active' => true,
                ],
            ],
        ])
        ->assertRedirect();

    auth()->logout();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Home')
            ->where('content.services_description', 'Structured advisory, litigation follow-up, and policy guidance.')
            ->where('content.process_description', 'Every request follows a traceable approval and assignment chain.')
            ->where('content.cta_primary_label', 'Open request portal')
            ->where('content.contact_description', 'Use the verified communication channels for this deployment.'));
});

it('persists uploaded hero slide images for the public website settings', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $publicWebsite = app(SystemSettingsService::class)->group('public_website');

    $this->actingAs($admin)
        ->post(route('settings.update', 'public_website'), [
            '_method' => 'put',
            'hero_eyebrow' => $publicWebsite['hero_eyebrow'],
            'hero_title' => $publicWebsite['hero_title'],
            'hero_description' => $publicWebsite['hero_description'],
            'about_title' => $publicWebsite['about_title'],
            'about_description' => $publicWebsite['about_description'],
            'services_title' => $publicWebsite['services_title'],
            'services_description' => $publicWebsite['services_description'],
            'service_advisory_title' => $publicWebsite['service_advisory_title'],
            'service_advisory_description' => $publicWebsite['service_advisory_description'],
            'service_case_support_title' => $publicWebsite['service_case_support_title'],
            'service_case_support_description' => $publicWebsite['service_case_support_description'],
            'service_policy_title' => $publicWebsite['service_policy_title'],
            'service_policy_description' => $publicWebsite['service_policy_description'],
            'process_title' => $publicWebsite['process_title'],
            'process_description' => $publicWebsite['process_description'],
            'process_step_one_title' => $publicWebsite['process_step_one_title'],
            'process_step_one_description' => $publicWebsite['process_step_one_description'],
            'process_step_two_title' => $publicWebsite['process_step_two_title'],
            'process_step_two_description' => $publicWebsite['process_step_two_description'],
            'process_step_three_title' => $publicWebsite['process_step_three_title'],
            'process_step_three_description' => $publicWebsite['process_step_three_description'],
            'process_step_four_title' => $publicWebsite['process_step_four_title'],
            'process_step_four_description' => $publicWebsite['process_step_four_description'],
            'posts_title' => $publicWebsite['posts_title'],
            'posts_description' => $publicWebsite['posts_description'],
            'cta_title' => $publicWebsite['cta_title'],
            'cta_description' => $publicWebsite['cta_description'],
            'cta_primary_label' => $publicWebsite['cta_primary_label'],
            'cta_secondary_label' => $publicWebsite['cta_secondary_label'],
            'contact_title' => $publicWebsite['contact_title'],
            'contact_description' => $publicWebsite['contact_description'],
            'contact_hours_value' => $publicWebsite['contact_hours_value'],
            'hero_slides' => [
                [
                    'title' => 'Updated hero slide',
                    'subtitle' => 'Uploaded image should persist.',
                    'button_label' => 'Open updates',
                    'button_url' => '/updates',
                    'display_order' => 1,
                    'is_active' => true,
                    'image' => UploadedFile::fake()->image('hero-slide-one.png', 1440, 900),
                ],
                [
                    'title' => 'Second slide',
                    'subtitle' => 'Existing image should remain available.',
                    'button_label' => 'Open login',
                    'button_url' => '/login',
                    'display_order' => 2,
                    'is_active' => true,
                ],
                [
                    'title' => 'Third slide',
                    'subtitle' => 'Contact section.',
                    'button_label' => 'Contact',
                    'button_url' => '/#contact',
                    'display_order' => 3,
                    'is_active' => true,
                ],
            ],
        ])
        ->assertRedirect();

    $slides = app(SystemSettingsService::class)->group('public_website')['hero_slides'];
    $firstSlide = $slides[0];
    $persistedSlides = SystemSetting::query()
        ->where('setting_group', 'public_website')
        ->where('setting_key', 'hero_slides')
        ->firstOrFail()
        ->value;

    expect($firstSlide['image_path'])->toBeString()
        ->and($firstSlide['image_path'])->toStartWith('branding/')
        ->and($persistedSlides[0]['image_path'] ?? null)->toBe($firstSlide['image_path']);

    Storage::disk('public')->assertExists($firstSlide['image_path']);

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settingsGroups.public_website.hero_slides.0.image_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/')));

    auth()->logout();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('slides.0.image_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/')));
});

it('updates an existing hero slide image and keeps the new image visible after reload', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $publicWebsite = app(SystemSettingsService::class)->group('public_website');

    $basePayload = [
        '_method' => 'put',
        'hero_eyebrow' => $publicWebsite['hero_eyebrow'],
        'hero_title' => $publicWebsite['hero_title'],
        'hero_description' => $publicWebsite['hero_description'],
        'about_title' => $publicWebsite['about_title'],
        'about_description' => $publicWebsite['about_description'],
        'services_title' => $publicWebsite['services_title'],
        'services_description' => $publicWebsite['services_description'],
        'service_advisory_title' => $publicWebsite['service_advisory_title'],
        'service_advisory_description' => $publicWebsite['service_advisory_description'],
        'service_case_support_title' => $publicWebsite['service_case_support_title'],
        'service_case_support_description' => $publicWebsite['service_case_support_description'],
        'service_policy_title' => $publicWebsite['service_policy_title'],
        'service_policy_description' => $publicWebsite['service_policy_description'],
        'process_title' => $publicWebsite['process_title'],
        'process_description' => $publicWebsite['process_description'],
        'process_step_one_title' => $publicWebsite['process_step_one_title'],
        'process_step_one_description' => $publicWebsite['process_step_one_description'],
        'process_step_two_title' => $publicWebsite['process_step_two_title'],
        'process_step_two_description' => $publicWebsite['process_step_two_description'],
        'process_step_three_title' => $publicWebsite['process_step_three_title'],
        'process_step_three_description' => $publicWebsite['process_step_three_description'],
        'process_step_four_title' => $publicWebsite['process_step_four_title'],
        'process_step_four_description' => $publicWebsite['process_step_four_description'],
        'posts_title' => $publicWebsite['posts_title'],
        'posts_description' => $publicWebsite['posts_description'],
        'cta_title' => $publicWebsite['cta_title'],
        'cta_description' => $publicWebsite['cta_description'],
        'cta_primary_label' => $publicWebsite['cta_primary_label'],
        'cta_secondary_label' => $publicWebsite['cta_secondary_label'],
        'contact_title' => $publicWebsite['contact_title'],
        'contact_description' => $publicWebsite['contact_description'],
        'contact_hours_value' => $publicWebsite['contact_hours_value'],
    ];

    $this->actingAs($admin)
        ->post(route('settings.update', 'public_website'), [
            ...$basePayload,
            'hero_slides' => [
                [
                    'title' => 'First upload',
                    'subtitle' => 'Original image',
                    'button_label' => 'Open updates',
                    'button_url' => '/updates',
                    'display_order' => 1,
                    'is_active' => true,
                    'image' => UploadedFile::fake()->image('hero-original.png', 1200, 800),
                ],
                [
                    'title' => 'Second slide',
                    'subtitle' => 'Second slide subtitle',
                    'button_label' => 'Open login',
                    'button_url' => '/login',
                    'display_order' => 2,
                    'is_active' => true,
                ],
                [
                    'title' => 'Third slide',
                    'subtitle' => 'Third slide subtitle',
                    'button_label' => 'Contact',
                    'button_url' => '/#contact',
                    'display_order' => 3,
                    'is_active' => true,
                ],
            ],
        ])
        ->assertRedirect();

    $originalPath = app(SystemSettingsService::class)->group('public_website')['hero_slides'][0]['image_path'];

    $this->actingAs($admin)
        ->post(route('settings.update', 'public_website'), [
            ...$basePayload,
            'hero_slides' => [
                [
                    'title' => 'Updated upload',
                    'subtitle' => 'Replacement image',
                    'button_label' => 'Open updates',
                    'button_url' => '/updates',
                    'display_order' => 1,
                    'is_active' => true,
                    'image' => UploadedFile::fake()->image('hero-replacement.png', 1600, 900),
                ],
                [
                    'title' => 'Second slide',
                    'subtitle' => 'Second slide subtitle',
                    'button_label' => 'Open login',
                    'button_url' => '/login',
                    'display_order' => 2,
                    'is_active' => true,
                ],
                [
                    'title' => 'Third slide',
                    'subtitle' => 'Third slide subtitle',
                    'button_label' => 'Contact',
                    'button_url' => '/#contact',
                    'display_order' => 3,
                    'is_active' => true,
                ],
            ],
        ])
        ->assertRedirect();

    $updatedSlides = app(SystemSettingsService::class)->group('public_website')['hero_slides'];
    $updatedPath = $updatedSlides[0]['image_path'];

    expect($updatedPath)->toBeString()
        ->and($updatedPath)->toStartWith('branding/')
        ->and($updatedPath)->not->toBe($originalPath);

    Storage::disk('public')->assertMissing($originalPath);
    Storage::disk('public')->assertExists($updatedPath);

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settingsGroups.public_website.hero_slides.0.image_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/')));

    auth()->logout();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('slides.0.image_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/')));
});

it('normalizes legacy hero slide image paths for admin preview and public hero display', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    Storage::disk('public')->put('branding/legacy-hero.png', 'legacy');

    app(SystemSettingsService::class)->updateGroup('public_website', [
        ...app(SystemSettingsService::class)->group('public_website'),
        'hero_slides' => [
            [
                'title' => 'Legacy upload',
                'subtitle' => 'Stored with an old storage URL format.',
                'button_label' => 'Open updates',
                'button_url' => '/updates',
                'display_order' => 1,
                'is_active' => true,
                'image_path' => '/storage/branding/legacy-hero.png',
            ],
            [
                'title' => 'Second slide',
                'subtitle' => 'Second slide subtitle',
                'button_label' => 'Open login',
                'button_url' => '/login',
                'display_order' => 2,
                'is_active' => true,
                'image_path' => '/images/home/hero-slide-2.svg',
            ],
            [
                'title' => 'Third slide',
                'subtitle' => 'Third slide subtitle',
                'button_label' => 'Contact',
                'button_url' => '/#contact',
                'display_order' => 3,
                'is_active' => true,
                'image_path' => '/images/home/hero-slide-3.svg',
            ],
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settingsGroups.public_website.hero_slides.0.image_path', 'branding/legacy-hero.png')
            ->where('settingsGroups.public_website.hero_slides.0.image_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/branding/legacy-hero.png')));

    auth()->logout();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('slides.0.image_path', 'branding/legacy-hero.png')
            ->where('slides.0.image_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/branding/legacy-hero.png')));
});
