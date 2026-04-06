<?php

declare(strict_types=1);

use App\Services\SystemSettingsService;
use App\Services\Telegram\ApiTelegramGateway;
use App\Services\Telegram\TelegramGateway;
use App\Services\Telegram\TelegramSendResult;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('persists telegram settings and masks the bot token in admin responses', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $botToken = '123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456';

    $this->actingAs($admin)
        ->put(route('settings.update', 'telegram'), [
            'telegram_enabled' => true,
            'bot_username' => '@legal_alerts_bot',
            'bot_token' => $botToken,
            'default_chat_target' => '-1001234567890',
            'configuration_notes' => 'Used for operational alerting.',
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'telegram'], absolute: false));

    $settings = app(SystemSettingsService::class);
    $telegram = $settings->group('telegram');

    expect($telegram['telegram_enabled'])->toBeTrue()
        ->and($telegram['bot_username'])->toBe('@legal_alerts_bot')
        ->and($telegram['bot_token'])->toBe($botToken)
        ->and($telegram['default_chat_target'])->toBe('-1001234567890');

    $settings->applyRuntimeConfiguration();

    expect(config('services.telegram.bot_token'))->toBe($botToken)
        ->and(config('services.telegram.bot_username'))->toBe('@legal_alerts_bot')
        ->and(config('services.telegram.default_chat_target'))->toBe('-1001234567890');

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('activeTab', 'general')
            ->where('settingsGroups.telegram.bot_token', '')
            ->where('settingsGroups.telegram.bot_token_configured', true)
            ->where('settingsGroups.telegram.bot_token_masked', fn (?string $value) => is_string($value) && $value !== '' && ! str_contains($value, $botToken)));

    $this->actingAs($admin)
        ->get(route('settings.index', ['tab' => 'telegram']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('activeTab', 'telegram')
            ->where('settingsGroups.telegram.bot_token_configured', true));
});

it('denies unauthorized users from updating telegram settings', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();

    $this->actingAs($requester)
        ->put(route('settings.update', 'telegram'), [
            'telegram_enabled' => true,
            'bot_username' => '@blocked_bot',
            'bot_token' => '123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456',
            'default_chat_target' => '-100100100',
            'configuration_notes' => '',
        ])
        ->assertForbidden();
});

it('sends a telegram test message using the saved settings', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    app(SystemSettingsService::class)->updateGroup('telegram', [
        'telegram_enabled' => true,
        'bot_username' => '@legal_alerts_bot',
        'bot_token' => '123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456',
        'default_chat_target' => '-1001234567890',
        'configuration_notes' => 'Test destination',
    ]);
    app(SystemSettingsService::class)->applyRuntimeConfiguration();

    $gateway = \Mockery::mock(TelegramGateway::class);
    $gateway->shouldReceive('send')
        ->once()
        ->with(
            '-1001234567890',
            \Mockery::on(fn (string $message): bool => str_contains($message, 'Telegram test message')),
        )
        ->andReturn(TelegramSendResult::sent());

    $this->app->instance(TelegramGateway::class, $gateway);

    $this->actingAs($admin)
        ->post(route('settings.telegram.test'))
        ->assertRedirect(route('settings.index', ['tab' => 'telegram'], absolute: false))
        ->assertSessionHas('success', 'Telegram test message sent successfully.');
});

it('rejects a telegram test message when the saved configuration is incomplete', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    app(SystemSettingsService::class)->updateGroup('telegram', [
        'telegram_enabled' => true,
        'bot_username' => '@legal_alerts_bot',
        'bot_token' => null,
        'default_chat_target' => '',
        'configuration_notes' => '',
    ]);
    app(SystemSettingsService::class)->applyRuntimeConfiguration();

    $this->actingAs($admin)
        ->post(route('settings.telegram.test'))
        ->assertRedirect(route('settings.index', ['tab' => 'telegram'], absolute: false))
        ->assertSessionHasErrors('telegram');
});

it('sends telegram api requests with the configured bot token', function (): void {
    Http::fake([
        'https://api.telegram.org/*' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 1001],
        ], 200),
    ]);

    config()->set('services.telegram.bot_token', '123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456');

    $result = app(ApiTelegramGateway::class)->send('-1001234567890', 'Telegram API smoke test');

    expect($result->sent)->toBeTrue();

    Http::assertSent(function (HttpRequest $request): bool {
        return $request->url() === 'https://api.telegram.org/bot123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456/sendMessage'
            && $request['chat_id'] === '-1001234567890'
            && $request['text'] === 'Telegram API smoke test';
    });
});
