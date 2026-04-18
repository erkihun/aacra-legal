<?php

declare(strict_types=1);

use App\Listeners\NotificationDeliverySubscriber;
use App\Jobs\SendTelegramMessageJob;
use App\Models\AdvisoryRequest;
use App\Models\User;
use App\Notifications\OverdueRequestNotification;
use App\Services\NotificationDeliveryDeduplicator;
use App\Services\Telegram\TelegramGateway;
use App\Services\Telegram\TelegramSendResult;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoWorkflowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
        DemoWorkflowSeeder::class,
    ]);
});

it('blocks a second notification delivery for the same recipient and channel after the first one succeeds', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $advisoryRequest = AdvisoryRequest::query()->where('request_number', 'ADV-2026-0001')->firstOrFail();
    $notification = new OverdueRequestNotification($advisoryRequest, '2026-04-18');
    $subscriber = app(NotificationDeliverySubscriber::class);

    expect($subscriber->whenSending(new NotificationSending($requester, $notification, 'mail')))->toBeNull();

    $subscriber->whenSent(new NotificationSent($requester, $notification, 'mail'));

    expect($subscriber->whenSending(new NotificationSending($requester, $notification, 'mail')))->toBeFalse()
        ->and($subscriber->whenSending(new NotificationSending($requester, $notification, 'database')))->toBeNull();
});

it('skips duplicate telegram sends when the same dedupe key is dispatched twice', function (): void {
    $gateway = \Mockery::mock(TelegramGateway::class);
    $gateway->shouldReceive('send')
        ->once()
        ->with('chat-1', 'Duplicate-safe message')
        ->andReturn(TelegramSendResult::sent());

    $this->app->instance(TelegramGateway::class, $gateway);

    $job = new SendTelegramMessageJob('chat-1', 'Duplicate-safe message', 'event|entity-1|recipient-1|telegram');

    $job->handle(
        $this->app->make(TelegramGateway::class),
        $this->app->make(NotificationDeliveryDeduplicator::class),
    );

    $job->handle(
        $this->app->make(TelegramGateway::class),
        $this->app->make(NotificationDeliveryDeduplicator::class),
    );

    expect(true)->toBeTrue();
});
