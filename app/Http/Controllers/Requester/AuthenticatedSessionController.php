<?php

declare(strict_types=1);

namespace App\Http\Controllers\Requester;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requester\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Requester/Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        Auth::guard('requester')->user()?->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('requester.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('requester')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('requester.login');
    }
}
