<?php

declare(strict_types=1);

use App\Models\RequesterAccount;
use App\Models\User;
use App\Notifications\RequesterResetPasswordNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('requester forgot password screen can be rendered', function () {
    $this->get('/requester/forgot-password')->assertStatus(200);
});

test('a requester account gets a reset link from the shared forgot password form', function () {
    Notification::fake();

    $account = RequesterAccount::factory()->create();

    $this->post('/forgot-password', ['email' => $account->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    Notification::assertSentTo($account, RequesterResetPasswordNotification::class);
});

test('the requester reset link points at the requester portal', function () {
    Notification::fake();

    $account = RequesterAccount::factory()->create();

    $this->post('/requester/forgot-password', ['email' => $account->email])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo(
        $account,
        RequesterResetPasswordNotification::class,
        function (RequesterResetPasswordNotification $notification) use ($account) {
            $url = $notification->toMail($account)->actionUrl;

            expect($url)->toContain('/requester/reset-password/')
                ->and($url)->toContain($notification->token);

            return true;
        }
    );
});

test('an internal user still gets the internal reset link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertNothingSentTo(RequesterAccount::factory()->make());
});

test('an unknown address reports a translated message rather than a raw key', function () {
    $this->post('/forgot-password', ['email' => 'nobody@example.test'])
        ->assertSessionHasErrors('email');

    $message = session('errors')->get('email')[0];

    expect($message)->not->toStartWith('passwords.')
        ->and($message)->toBe(__('passwords.user'));
});

test('a requester can reset their password with a valid token', function () {
    Notification::fake();

    $account = RequesterAccount::factory()->create();

    $this->post('/requester/forgot-password', ['email' => $account->email]);

    Notification::assertSentTo(
        $account,
        RequesterResetPasswordNotification::class,
        function (RequesterResetPasswordNotification $notification) use ($account) {
            $this->get('/requester/reset-password/'.$notification->token.'?email='.urlencode($account->email))
                ->assertStatus(200);

            $this->post('/requester/reset-password', [
                'token' => $notification->token,
                'email' => $account->email,
                'password' => 'NewStrongPassword123!',
                'password_confirmation' => 'NewStrongPassword123!',
            ])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('requester.login'));

            expect(Illuminate\Support\Facades\Hash::check(
                'NewStrongPassword123!',
                $account->fresh()->password
            ))->toBeTrue();

            return true;
        }
    );
});
