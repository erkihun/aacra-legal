<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SystemSettingsService;
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
        $appMeta['default_dashboard_route'] = $this->settings->defaultDashboardRouteFor($request->user());
        $cookieFlashError = $request->cookie('ldms_flash_error');

        if (is_string($cookieFlashError) && $cookieFlashError !== '') {
            cookie()->queue(cookie()->forget('ldms_flash_error'));
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()?->id,
                    'name' => $request->user()?->name,
                    'email' => $request->user()?->email,
                    'phone' => $request->user()?->phone,
                    'locale' => $request->user()?->locale?->value,
                    'roles' => $request->user()?->getRoleNames()->values(),
                    'permissions' => $request->user()?->getAllPermissions()->pluck('name')->values(),
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
            'notificationSummary' => $request->user() ? [
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ] : [
                'unread_count' => 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error') ?? $cookieFlashError,
            ],
        ];
    }
}
