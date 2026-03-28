<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\CanonicalizeLoopbackHost;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            CanonicalizeLoopbackHost::class,
            SetLocale::class,
            HandleInertiaRequests::class,
            AddSecurityHeaders::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (AuthorizationException $exception): void {
            $request = request();

            logger()->warning('Authorization denied.', [
                'user_id' => $request?->user()?->getKey(),
                'method' => $request?->method(),
                'url' => $request?->fullUrl(),
                'message' => $exception->getMessage(),
            ]);
        });

        $exceptions->render(function (TokenMismatchException $exception, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('auth.session_expired'),
                ], 419);
            }

            $target = $request->headers->get('referer');

            if (! is_string($target) || $target === '' || $target === $request->fullUrl()) {
                $target = $request->user() ? route('dashboard', absolute: false) : route('login', absolute: false);
            }

            $request->session()->regenerateToken();

            return redirect()->to($target)
                ->withCookie(cookie()->make('ldms_flash_error', __('auth.session_expired'), 1));
        });
    })->create();
