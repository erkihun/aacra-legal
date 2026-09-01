<?php

declare(strict_types=1);

namespace App\Http\Controllers\Requester;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requester\RegisterRequest;
use App\Models\Department;
use App\Models\RequesterAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredAccountController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Requester/Auth/Register', [
            'departments' => Department::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $account = RequesterAccount::query()->create([
            'department_id' => $validated['department_id'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        Auth::guard('requester')->login($account);

        return redirect()->route('requester.dashboard');
    }
}
