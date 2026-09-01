<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RequesterAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = (string) $request->input('email');

        // Accounts live in two separate tables behind two separate brokers, so we
        // pick the broker that actually owns this address. Otherwise a requester
        // asking for a link is told no such user exists.
        $broker = $this->brokerFor($email);

        $status = Password::broker($broker)->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Resolve which password broker owns the given email address.
     */
    private function brokerFor(string $email): string
    {
        if (RequesterAccount::where('email', $email)->exists()) {
            return 'requester_accounts';
        }

        return 'users';
    }
}
