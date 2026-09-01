<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequesterIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $requester = Auth::guard('requester')->user();

        if ($requester !== null && ! $requester->is_active) {
            Auth::guard('requester')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('requester.login')
                ->withErrors(['email' => __('Your account has been deactivated.')]);
        }

        return $next($request);
    }
}
