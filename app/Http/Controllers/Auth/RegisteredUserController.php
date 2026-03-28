<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CreateRequesterAccountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequesterRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\SystemSettingsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'departments' => Department::query()
                ->active()
                ->where('code', '!=', 'LEG')
                ->orderBy('name_en')
                ->get(['id', 'name_en', 'name_am']),
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequesterRequest $request, CreateRequesterAccountAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $action->execute([
            ...$request->validated(),
            'locale' => $request->input('locale', $request->session()->get('locale', $this->settings->defaultLocale())),
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->put('locale', $user->locale?->value ?? $this->settings->defaultLocale());

        return redirect(route($this->settings->defaultDashboardRouteFor($user), absolute: false));
    }
}
