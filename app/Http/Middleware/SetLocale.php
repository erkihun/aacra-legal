<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SystemSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale?->value
            ?? $request->session()->get('locale')
            ?? $this->settings->defaultLocale();

        if (! in_array($locale, $this->settings->supportedLocales(), true)) {
            $locale = $this->settings->fallbackLocale();
        }

        app()->setLocale($locale);
        config(['app.timezone' => $this->settings->timezone()]);
        date_default_timezone_set($this->settings->timezone());

        return $next($request);
    }
}
