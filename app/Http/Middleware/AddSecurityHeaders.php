<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $isLocal = app()->environment(['local', 'testing']);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('Origin-Agent-Cluster', '?1');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($isLocal));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(bool $isLocal): string
    {
        $connectSources = ["'self'"];
        $scriptSources = ["'self'", "'unsafe-inline'"];

        if ($isLocal) {
            $connectSources = [
                ...$connectSources,
                'http://localhost:*',
                'http://127.0.0.1:*',
                'https://localhost:*',
                'https://127.0.0.1:*',
                'ws://localhost:*',
                'ws://127.0.0.1:*',
                'wss://localhost:*',
                'wss://127.0.0.1:*',
                'http://[::1]:5173',
                'http://[::1]:5174',
                'http://[::1]:4173',
                'https://[::1]:5173',
                'https://[::1]:5174',
                'https://[::1]:4173',
                'ws://[::1]:5173',
                'ws://[::1]:5174',
                'ws://[::1]:4173',
                'wss://[::1]:5173',
                'wss://[::1]:5174',
                'wss://[::1]:4173',
            ];
            $scriptSources = [
                ...$scriptSources,
                "'unsafe-eval'",
                'http://localhost:*',
                'http://127.0.0.1:*',
                'https://localhost:*',
                'https://127.0.0.1:*',
                'http://[::1]:5173',
                'http://[::1]:5174',
                'http://[::1]:4173',
                'https://[::1]:5173',
                'https://[::1]:5174',
                'https://[::1]:4173',
            ];
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data: blob:",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' data: https://fonts.gstatic.com",
            'connect-src '.implode(' ', $connectSources),
            'script-src '.implode(' ', $scriptSources),
        ]);
    }
}
