<?php

declare(strict_types=1);

use App\Enums\LocaleCode;
use App\Models\AdvisoryRequest;
use App\Models\User;
use App\Notifications\OverdueRequestNotification;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoWorkflowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();
    Cache::flush();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
        DemoWorkflowSeeder::class,
    ]);
});

it('persists locale changes and reapplies the user locale on login', function (): void {
    $user = User::query()->where('email', 'requester@ldms.test')->firstOrFail();

    $this->actingAs($user)
        ->post(route('locale.update'), ['locale' => LocaleCode::AMHARIC->value])
        ->assertRedirect();

    expect($user->fresh()?->locale)->toBe(LocaleCode::AMHARIC);

    auth()->logout();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user->fresh());
    $this->assertSame(LocaleCode::AMHARIC->value, session('locale'));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', LocaleCode::AMHARIC->value)
            ->where('auth.user.locale', LocaleCode::AMHARIC->value));
});

it('shows only the authenticated users database notifications', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $advisoryRequest = AdvisoryRequest::query()->where('request_number', 'ADV-2026-0001')->firstOrFail();

    $requester->notify(new OverdueRequestNotification($advisoryRequest));
    $director->notify(new OverdueRequestNotification($advisoryRequest));

    $this->actingAs($requester)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Notifications/Index')
            ->has('notifications.data', 1)
            ->where('notifications.data.0.data.request_number', 'ADV-2026-0001')
            ->where('notifications.data.0.type', 'advisory.overdue')
            ->where('notifications.data.0.type_label', __('notifications.feed.types.advisory_overdue')));

    $notificationId = $requester->notifications()->firstOrFail()->id;

    $this->actingAs($requester)
        ->patch(route('notifications.read', $notificationId))
        ->assertRedirect();

    expect($requester->fresh()->unreadNotifications()->count())->toBe(0);
    expect($director->fresh()->unreadNotifications()->count())->toBe(1);
});

it('deduplicates identical database notifications for the same user and event', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $advisoryRequest = AdvisoryRequest::query()->where('request_number', 'ADV-2026-0001')->firstOrFail();

    $requester->notify(new OverdueRequestNotification($advisoryRequest, '2026-04-18'));
    $requester->notify(new OverdueRequestNotification($advisoryRequest, '2026-04-18'));

    expect($requester->fresh()->notifications()->count())->toBe(1);
});

it('keeps distinct reminder events separate when their dedupe context changes', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $advisoryRequest = AdvisoryRequest::query()->where('request_number', 'ADV-2026-0001')->firstOrFail();

    $requester->notify(new OverdueRequestNotification($advisoryRequest, '2026-04-18'));
    $requester->notify(new OverdueRequestNotification($advisoryRequest, '2026-04-19'));

    expect($requester->fresh()->notifications()->count())->toBe(2);
});

it('marks all authenticated user notifications as read without affecting other users', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $advisoryRequest = AdvisoryRequest::query()->where('request_number', 'ADV-2026-0001')->firstOrFail();

    $requester->notify(new OverdueRequestNotification($advisoryRequest, '2026-04-18'));
    $requester->notify(new OverdueRequestNotification($advisoryRequest, '2026-04-18'));
    $director->notify(new OverdueRequestNotification($advisoryRequest, '2026-04-18'));

    expect($requester->fresh()->unreadNotifications()->count())->toBe(1);

    $this->actingAs($requester)
        ->patch(route('notifications.read-all'))
        ->assertRedirect();

    expect($requester->fresh()->unreadNotifications()->count())->toBe(0);
    expect($director->fresh()->unreadNotifications()->count())->toBe(1);
});
