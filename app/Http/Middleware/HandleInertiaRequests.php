<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\RequesterAccount;
use App\Models\User;
use App\Services\SystemSettingsService;
use App\Support\NotificationFingerprint;
use App\Support\Translations;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $appMeta = $this->settings->appMeta();
        $cookieFlashError = $request->cookie('ldms_flash_error');
        /** @var User|null $user */
        $user = $request->user('web');
        /** @var RequesterAccount|null $requesterUser */
        $requesterUser = $request->user('requester');
        $appMeta['default_dashboard_route'] = $this->settings->defaultDashboardRouteFor($user);

        if (is_string($cookieFlashError) && $cookieFlashError !== '') {
            cookie()->queue(cookie()->forget('ldms_flash_error'));
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'job_title' => $user->job_title,
                    'locale' => $user->locale?->value,
                    'avatar_url' => $user->avatarUrl(),
                    'signature_url' => $user->signatureUrl(),
                    'stamp_url' => $user->stampUrl(),
                    'national_id' => User::formatNationalId($user->national_id),
                    'telegram_username' => $user->telegram_username,
                    'roles' => $user->getRoleNames()->values(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                ] : null,
            ],
            'locale' => app()->getLocale(),
            'availableLocales' => collect($this->settings->supportedLocales())->map(fn (string $locale) => [
                'value' => $locale,
                'label' => __("settings.locale_options.{$locale}"),
            ])->values(),
            'csrf_token' => csrf_token(),
            'translations' => Translations::forLocale(app()->getLocale()),
            'appMeta' => $appMeta,
            'notificationSummary' => $user ? [
                'unread_count' => NotificationFingerprint::deduplicate($user->unreadNotifications()->get())->count(),
            ] : [
                'unread_count' => 0,
            ],
            'requesterAuth' => [
                'user' => $requesterUser ? [
                    'id' => $requesterUser->id,
                    'full_name' => $requesterUser->full_name,
                    'email' => $requesterUser->email,
                    'department' => $requesterUser->department ? [
                        'name_en' => $requesterUser->department->name_en,
                        'name_am' => $requesterUser->department->name_am,
                    ] : null,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error') ?? $cookieFlashError,
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
