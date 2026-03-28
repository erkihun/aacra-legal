<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalizeLoopbackHost
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment(['local', 'testing']) || ! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $configuredUrl = config('app.url');
        $configuredHost = parse_url($configuredUrl, PHP_URL_HOST);
        $configuredPort = parse_url($configuredUrl, PHP_URL_PORT);
        $currentHost = $request->getHost();
        $currentPort = $request->getPort();

        if (
            ! is_string($configuredHost)
            || ! $this->isLoopbackHost($configuredHost)
            || ! $this->isLoopbackHost($currentHost)
            || $configuredHost === $currentHost
        ) {
            return $next($request);
        }

        $target = sprintf(
            '%s://%s%s%s',
            $request->getScheme(),
            $configuredHost,
            ($configuredPort ?? $currentPort) ? ':'.($configuredPort ?? $currentPort) : '',
            $request->getRequestUri(),
        );

        return redirect()->to($target, 302);
    }

    private function isLoopbackHost(string $host): bool
    {
        return in_array($host, ['127.0.0.1', 'localhost'], true);
    }
}
