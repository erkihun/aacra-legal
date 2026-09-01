<?php

declare(strict_types=1);

namespace App\Http\Controllers\Requester;

use App\Http\Controllers\Controller;
use App\Support\PasswordRules;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordRules $passwordRules,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render('Requester/Auth/ResetPassword', [
            'email' => $request->query('email'),
            'token' => $request->route('token'),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', $this->passwordRules->rule()],
        ]);

        $status = Password::broker('requester_accounts')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($account) use ($request) {
                $account->forceFill([
                    'password' => Hash::make($request->string('password')->value()),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($account));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('requester.login')->with('success', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
