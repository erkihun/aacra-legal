<?php

declare(strict_types=1);

use App\Http\Controllers\Requester\AdvisoryRequestController;
use App\Http\Controllers\Requester\AuthenticatedSessionController;
use App\Http\Controllers\Requester\DashboardController;
use App\Http\Controllers\Requester\LawsuitRequestController;
use App\Http\Controllers\Requester\NewPasswordController;
use App\Http\Controllers\Requester\PasswordResetLinkController;
use App\Http\Controllers\Requester\RegisteredAccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Requester Portal Routes
|--------------------------------------------------------------------------
| Completely separate from the internal legal department routes.
| Uses the 'requester' guard (requester_accounts table).
*/

Route::prefix('requester')->name('requester.')->group(function () {

    // Guest-only routes (redirect to dashboard if already logged in)
    Route::middleware('guest:requester')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store']);

        Route::get('/register', [RegisteredAccountController::class, 'create'])->name('register');
        Route::post('/register', [RegisteredAccountController::class, 'store']);

        Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
            ->name('password.request');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('password.email');

        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
            ->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])
            ->name('password.store');
    });

    // Authenticated requester routes
    Route::middleware(['auth:requester', 'requester'])->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Legal advice (advisory) requests
        Route::get('/advisory', [AdvisoryRequestController::class, 'index'])->name('advisory.index');
        Route::get('/advisory/create', [AdvisoryRequestController::class, 'create'])->name('advisory.create');
        Route::get('/advisory/{advisoryRequest}', [AdvisoryRequestController::class, 'show'])->name('advisory.show');
        Route::get('/advisory/{advisoryRequest}/edit', [AdvisoryRequestController::class, 'edit'])->name('advisory.edit');

        Route::middleware('throttle:legal-mutations')->group(function () {
            Route::post('/advisory', [AdvisoryRequestController::class, 'store'])->name('advisory.store');
            Route::patch('/advisory/{advisoryRequest}', [AdvisoryRequestController::class, 'update'])->name('advisory.update');
        });

        // Litigation / lawsuit filing requests
        Route::get('/lawsuit-requests', [LawsuitRequestController::class, 'index'])->name('lawsuit-requests.index');
        Route::get('/lawsuit-requests/create', [LawsuitRequestController::class, 'create'])->name('lawsuit-requests.create');
        Route::get('/lawsuit-requests/{lawsuitRequest}', [LawsuitRequestController::class, 'show'])->name('lawsuit-requests.show');
        Route::get('/lawsuit-requests/{lawsuitRequest}/edit', [LawsuitRequestController::class, 'edit'])->name('lawsuit-requests.edit');

        Route::middleware('throttle:legal-mutations')->group(function () {
            Route::post('/lawsuit-requests', [LawsuitRequestController::class, 'store'])->name('lawsuit-requests.store');
            Route::patch('/lawsuit-requests/{lawsuitRequest}', [LawsuitRequestController::class, 'update'])->name('lawsuit-requests.update');
        });
    });
});
