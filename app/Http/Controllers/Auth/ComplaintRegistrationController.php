<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterComplaintClientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Complaints\RegisterComplaintClientRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\SystemSettingsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintRegistrationController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function create(): Response
    {
        abort_unless($this->settings->complaintClientSelfRegistrationEnabled(), 404);

        return Inertia::render('Complaints/Auth/Register', [
            'branches' => Branch::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
        ]);
    }

    public function store(RegisterComplaintClientRequest $request, RegisterComplaintClientAction $action): RedirectResponse
    {
        abort_unless($this->settings->complaintClientSelfRegistrationEnabled(), 404);

        /** @var User $user */
        $user = $action->execute($request->validated());

        event(new Registered($user));

        Auth::login($user);
        $request->session()->put('locale', $user->locale?->value ?? $this->settings->defaultLocale());

        return redirect()->route('complaints.create');
    }
}
