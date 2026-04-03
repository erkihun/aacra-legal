<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PersistUserAction;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request, PersistUserAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $originalEmail = $user->email;

        $action->execute($user, $request->validated(), $user);

        if ($user->fresh()?->email !== $originalEmail) {
            $user->forceFill(['email_verified_at' => null])->save();
        }

        $request->session()->put('locale', $user->fresh()?->locale?->value ?? $request->input('locale'));

        return Redirect::route('profile.edit')->with('success', __('profile.saved'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        return Redirect::route('profile.edit')->with('error', __('profile.delete_disabled'));
    }
}
