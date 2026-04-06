<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdvisoryCategoryController as AdminAdvisoryCategoryController;
use App\Http\Controllers\Admin\CourtController as AdminCourtController;
use App\Http\Controllers\Admin\DepartmentController as AdminDepartmentController;
use App\Http\Controllers\Admin\LegalCaseTypeController as AdminLegalCaseTypeController;
use App\Http\Controllers\Admin\PublicPostController as AdminPublicPostController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\TeamController as AdminTeamController;
use App\Http\Controllers\Admin\TelegramTestMessageController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AdvisoryRequestController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BrandingAssetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalCaseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\PublicPostController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Public entry point.
Route::get('/branding-assets/{path}', [BrandingAssetController::class, 'show'])
    ->where('path', '.*')
    ->name('branding-assets.show');

Route::get('/', PublicHomeController::class)->name('home');
Route::get('/updates', [PublicPostController::class, 'index'])->name('posts.index');
Route::get('/updates/{slug}', [PublicPostController::class, 'show'])->name('posts.show');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('auth')->group(function () {
    // Authenticated read routes.
    Route::get('/attachments/{attachment}/view', [AttachmentController::class, 'show'])->name('attachments.view');
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::get('/advisory', [AdvisoryRequestController::class, 'index'])->name('advisory.index');
    Route::get('/advisory/create', [AdvisoryRequestController::class, 'create'])->name('advisory.create');
    Route::get('/advisory/{advisoryRequest}/edit', [AdvisoryRequestController::class, 'edit'])->name('advisory.edit');
    Route::get('/advisory/{advisoryRequest}/responses/create', [AdvisoryRequestController::class, 'createResponse'])->name('advisory.responses.create');
    Route::get('/advisory/{advisoryRequest}/responses/{advisoryResponse}/edit', [AdvisoryRequestController::class, 'editResponse'])->name('advisory.responses.edit');
    Route::get('/advisory/{advisoryRequest}/responses/{advisoryResponse}', [AdvisoryRequestController::class, 'showResponse'])->name('advisory.responses.show');
    Route::get('/advisory/{advisoryRequest}', [AdvisoryRequestController::class, 'show'])->name('advisory.show');

    Route::get('/cases', [LegalCaseController::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [LegalCaseController::class, 'create'])->name('cases.create');
    Route::get('/cases/{legalCase}/edit', [LegalCaseController::class, 'edit'])->name('cases.edit');
    Route::get('/cases/{legalCase}', [LegalCaseController::class, 'show'])->name('cases.show');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{reportType}', [ReportController::class, 'export'])->name('reports.export');

    Route::resource('users', UserManagementController::class);
    Route::resource('public-posts', AdminPublicPostController::class);
    Route::patch('/public-posts/{publicPost}/publish', [AdminPublicPostController::class, 'publish'])->name('public-posts.publish');
    Route::patch('/public-posts/{publicPost}/unpublish', [AdminPublicPostController::class, 'unpublish'])->name('public-posts.unpublish');
    Route::get('/roles', [RoleManagementController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleManagementController::class, 'create'])->name('roles.create');
    Route::get('/roles/{role}/edit', [RoleManagementController::class, 'edit'])->name('roles.edit');
    Route::patch('/roles/{role}', [RoleManagementController::class, 'update'])->name('roles.update');
    Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings.index');

    Route::resource('departments', AdminDepartmentController::class)->except([]);
    Route::resource('teams', AdminTeamController::class)->except([]);
    Route::resource('advisory-categories', AdminAdvisoryCategoryController::class)
        ->parameters(['advisory-categories' => 'advisoryCategory'])
        ->except([]);
    Route::resource('courts', AdminCourtController::class)->except([]);
    Route::resource('legal-case-types', AdminLegalCaseTypeController::class)
        ->parameters(['legal-case-types' => 'caseType'])
        ->except([]);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Authenticated mutation routes guarded by rate limiting.
    Route::middleware('throttle:legal-mutations')->group(function () {
        Route::post('/advisory', [AdvisoryRequestController::class, 'store'])->name('advisory.store');
        Route::patch('/advisory/{advisoryRequest}', [AdvisoryRequestController::class, 'update'])->name('advisory.update');
        Route::delete('/advisory/{advisoryRequest}', [AdvisoryRequestController::class, 'destroy'])->name('advisory.destroy');

        Route::patch('/advisory/{advisoryRequest}/review', [AdvisoryRequestController::class, 'directorReview'])->name('advisory.review');
        Route::patch('/advisory/{advisoryRequest}/assign', [AdvisoryRequestController::class, 'assign'])->name('advisory.assign');
        Route::post('/advisory/{advisoryRequest}/responses', [AdvisoryRequestController::class, 'respond'])->name('advisory.respond');
        Route::patch('/advisory/{advisoryRequest}/responses/{advisoryResponse}', [AdvisoryRequestController::class, 'updateResponse'])->name('advisory.responses.update');
        Route::delete('/advisory/{advisoryRequest}/responses/{advisoryResponse}', [AdvisoryRequestController::class, 'destroyResponse'])->name('advisory.responses.destroy');
        
        Route::post('/advisory/{advisoryRequest}/comments', [AdvisoryRequestController::class, 'addComment'])->name('advisory.comments.store');
        Route::post('/advisory/{advisoryRequest}/attachments', [AdvisoryRequestController::class, 'addAttachment'])->name('advisory.attachments.store');

        Route::post('/cases', [LegalCaseController::class, 'store'])->name('cases.store');
        Route::patch('/cases/{legalCase}', [LegalCaseController::class, 'update'])->name('cases.update');
        Route::delete('/cases/{legalCase}', [LegalCaseController::class, 'destroy'])->name('cases.destroy');
        Route::patch('/cases/{legalCase}/review', [LegalCaseController::class, 'review'])->name('cases.review');
        Route::patch('/cases/{legalCase}/assign', [LegalCaseController::class, 'assign'])->name('cases.assign');
        Route::post('/cases/{legalCase}/hearings', [LegalCaseController::class, 'recordHearing'])->name('cases.hearings.store');
        Route::patch('/cases/{legalCase}/hearings/{hearing}', [LegalCaseController::class, 'updateHearing'])->name('cases.hearings.update');
        Route::delete('/cases/{legalCase}/hearings/{hearing}', [LegalCaseController::class, 'destroyHearing'])->name('cases.hearings.destroy');
        Route::patch('/cases/{legalCase}/close', [LegalCaseController::class, 'close'])->name('cases.close');
        Route::patch('/cases/{legalCase}/reopen', [LegalCaseController::class, 'reopen'])->name('cases.reopen');
        Route::post('/cases/{legalCase}/comments', [LegalCaseController::class, 'addComment'])->name('cases.comments.store');
        Route::patch('/cases/{legalCase}/comments/{comment}', [LegalCaseController::class, 'updateComment'])->name('cases.comments.update');
        Route::delete('/cases/{legalCase}/comments/{comment}', [LegalCaseController::class, 'destroyComment'])->name('cases.comments.destroy');
        Route::post('/cases/{legalCase}/attachments', [LegalCaseController::class, 'addAttachment'])->name('cases.attachments.store');
        Route::patch('/attachments/{attachment}', [AttachmentController::class, 'update'])->name('attachments.update');
        Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::put('/settings/{group}', [SystemSettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/telegram/test', TelegramTestMessageController::class)->name('settings.telegram.test');
        Route::post('/roles', [RoleManagementController::class, 'store'])->name('roles.store');
        Route::delete('/roles/{role}', [RoleManagementController::class, 'destroy'])->name('roles.destroy');
        Route::patch('/users/{user}/ban', [UserManagementController::class, 'ban'])->name('users.ban');
        Route::patch('/users/{user}/activate', [UserManagementController::class, 'activate'])->name('users.activate');
    });
});

require __DIR__.'/auth.php';
