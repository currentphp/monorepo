<?php

use App\Actions\Auth\ResetUserPassword;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;

test('guests can open recovery and reset screens', function () {
    $this->get(route('password.request'))->assertInertia(fn (Assert $page) => $page->component('auth/forgot-password'));
    $this->get(route('password.reset', ['token' => 'example-token', 'email' => 'member@example.test']))
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertInertia(fn (Assert $page) => $page->component('auth/reset-password')
            ->where('email', 'member@example.test')->where('token', 'example-token'));
});

test('reset requests give the same browser response for known unknown and recently requested accounts', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'member@example.test']);

    foreach ([' MEMBER@EXAMPLE.TEST ', 'member@example.test', 'unknown@example.test'] as $email) {
        $this->from(route('password.request'))->post(route('password.email'), ['email' => $email])
            ->assertRedirectToRoute('password.request')->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'If an account matches that email, a password reset link will be sent. Please check your inbox.');
    }

    Notification::assertSentToTimes($user, ResetPassword::class, 1);
    Notification::assertCount(1);
    $this->get(route('password.request'))->assertInertia(fn (Assert $page) => $page
        ->where('status', 'If an account matches that email, a password reset link will be sent. Please check your inbox.'));
});

test('reset requests give the same JSON response for known and unknown accounts', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'member@example.test']);

    foreach (['member@example.test', 'member@example.test', 'unknown@example.test'] as $email) {
        $this->postJson(route('password.email'), ['email' => $email])->assertOk()
            ->assertExactJson(['message' => 'If an account matches that email, a password reset link will be sent. Please check your inbox.']);
    }

    Notification::assertSentToTimes($user, ResetPassword::class, 1);
    Notification::assertCount(1);
});

test('reset mail uses the configured application URL even with an untrusted request host', function () {
    Notification::fake();
    config(['app.url' => 'https://accounts.example.test']);
    $user = User::factory()->create();

    $this->post('https://untrusted.example.test/forgot-password', ['email' => $user->email])->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        expect($notification->toMail($user)->actionUrl)->toBe('https://accounts.example.test/reset-password/'.$notification->token.'?email='.urlencode($user->email));
        expect(Password::broker()->tokenExists($user, $notification->token))->toBeTrue();

        return true;
    });
});

test('malformed recovery requests are rejected without sending mail', function (mixed $email) {
    Notification::fake();

    $this->postJson(route('password.email'), ['email' => $email])->assertUnprocessable()->assertJsonValidationErrors('email');

    Notification::assertNothingSent();
})->with([null, 'not-an-email', [['malformed']]]);

test('recovery and reset attempts share limits across email case variants and recover after a minute', function (string $route) {
    Notification::fake();
    $this->freezeTime();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson(route($route), ['email' => 'MEMBER@example.test']);
    }
    $this->postJson(route($route), ['email' => 'member@EXAMPLE.TEST'])->assertTooManyRequests()->assertHeader('Retry-After');
    $this->from(route('password.request'))->post(route($route), ['email' => 'member@example.test'])
        ->assertRedirectToRoute('password.request')->assertSessionHasErrors(['email' => 'Too many attempts. Please try again in a minute.']);
    $this->travel(61)->seconds();
    $this->postJson(route($route), ['email' => 'member@example.test'])->assertStatus($route === 'password.email' ? 200 : 422);

    Notification::assertNothingSent();
})->with(['password.email', 'password.update']);

test('recovery and reset IP limits prevent rotating account identifiers', function (string $route) {
    Notification::fake();

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->postJson(route($route), ['email' => "member{$attempt}@example.test"]);
    }
    $this->postJson(route($route), ['email' => 'another@example.test'])->assertTooManyRequests();

    Notification::assertNothingSent();
})->with(['password.email', 'password.update']);

test('a valid reset changes only the password and remember token and requires a fresh login', function () {
    $user = User::factory()->create(['email' => 'member@example.test', 'remember_token' => 'old-remember-token']);
    Event::fake([PasswordResetEvent::class]);
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'email' => ' MEMBER@EXAMPLE.TEST ', 'token' => $token,
        'password' => 'new-safe-password', 'password_confirmation' => 'new-safe-password',
        'name' => 'injected', 'remember_token' => 'injected', 'email_verified_at' => null,
    ])->assertRedirectToRoute('login')->assertSessionHas('status', __('passwords.reset'));

    $this->assertGuest();
    expect($user->fresh()->name)->toBe($user->name);
    expect($user->fresh()->email_verified_at)->toEqual($user->email_verified_at);
    expect($user->fresh()->remember_token)->not->toBeIn(['old-remember-token', 'injected']);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    Event::assertDispatched(PasswordResetEvent::class, fn (PasswordResetEvent $event) => $event->user->is($user));
    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('status', __('passwords.reset')));
    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertUnprocessable();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'new-safe-password'])->assertRedirectToRoute('dashboard');
    $this->assertAuthenticatedAs($user);
});

test('invalid expired reused and wrong-account tokens never change a password', function (string $scenario) {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);
    $email = $user->email;
    if ($scenario === 'expired') {
        $this->travel(61)->minutes();
    } elseif ($scenario === 'reused') {
        $this->postJson(route('password.update'), ['email' => $email, 'token' => $token, 'password' => 'first-new-password', 'password_confirmation' => 'first-new-password'])->assertOk();
    } elseif ($scenario === 'wrong account') {
        $email = User::factory()->create()->email;
    } elseif ($scenario === 'unknown account') {
        $email = 'unknown@example.test';
    } else {
        $token .= 'tampered';
    }
    $password = $user->fresh()->password;
    Event::fake([PasswordResetEvent::class]);

    $this->postJson(route('password.update'), [
        'email' => $email, 'token' => $token, 'password' => 'another-new-password', 'password_confirmation' => 'another-new-password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email' => __('passwords.token')]);

    expect($user->fresh()->password)->toBe($password);
    $this->assertGuest();
    Event::assertNotDispatched(PasswordResetEvent::class);
})->with(['tampered', 'expired', 'reused', 'wrong account', 'unknown account']);

test('invalid reset input does not change credentials or flash secrets', function (array $invalid, string $field) {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);
    $input = array_replace([
        'email' => $user->email, 'token' => $token,
        'password' => 'new-safe-password', 'password_confirmation' => 'new-safe-password',
    ], $invalid);

    $this->from(route('password.reset', ['token' => $token]))->post(route('password.update'), $input)
        ->assertSessionHasErrors($field)->assertSessionMissingInput('password')
        ->assertSessionMissingInput('password_confirmation')->assertSessionMissingInput('token');

    expect($user->fresh()->password)->toBe($user->password);
    expect(Password::broker()->tokenExists($user, $token))->toBeTrue();
})->with([
    'array token' => [['token' => ['invalid']], 'token'],
    'missing token' => [['token' => null], 'token'],
    'array email' => [['email' => ['invalid']], 'email'],
    'array password' => [['password' => ['invalid']], 'password'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'confirmation mismatch' => [['password_confirmation' => 'different'], 'password'],
    'null byte' => [['password' => "new\0password123", 'password_confirmation' => "new\0password123"], 'password'],
    'bcrypt byte limit' => [['password' => str_repeat('é', 37), 'password_confirmation' => str_repeat('é', 37)], 'password'],
]);

test('reset action uses configured password rules without HTTP or session dependencies', function () {
    $user = User::factory()->create();
    $this->app->instance('env', 'production');
    Http::preventStrayRequests();
    Http::fake(['api.pwnedpasswords.com/range/*' => Http::response('', 200)]);

    app(ResetUserPassword::class)->reset($user, ['password' => 'Secure-New-Password-123!', 'password_confirmation' => 'Secure-New-Password-123!']);

    expect(Hash::check('Secure-New-Password-123!', $user->fresh()->password))->toBeTrue();
    $this->assertGuest();
    Http::assertSentCount(1);
});

test('production resets reject weak passwords without changing credentials', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);
    // Production rules without changing the test-environment CSRF bypass.
    Illuminate\Validation\Rules\Password::defaults(fn () => Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised());
    Http::preventStrayRequests();
    Http::fake(['api.pwnedpasswords.com/range/*' => Http::response('', 200)]);

    $this->postJson(route('password.update'), ['email' => $user->email, 'token' => $token, 'password' => 'onlylowercase', 'password_confirmation' => 'onlylowercase'])
        ->assertUnprocessable()->assertJsonValidationErrors('password');

    expect($user->fresh()->password)->toBe($user->password);
    Http::assertNothingSent();
});

test('recovery remains available when public registration is disabled', function () {
    config(['fortify.registration_enabled' => false]);
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson(route('password.email'), ['email' => $user->email])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('authenticated users cannot submit guest password recovery forms', function (string $route) {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route($route), [])->assertRedirectToRoute('dashboard');

    Notification::assertNothingSent();
    expect($user->fresh()->password)->toBe($user->password);
})->with(['password.email', 'password.update']);

test('Inertia resets leave the sensitive document through a full login navigation', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->withHeaders(['X-Inertia' => 'true'])->post(route('password.update'), [
        'email' => $user->email, 'token' => $token,
        'password' => 'new-safe-password', 'password_confirmation' => 'new-safe-password',
    ])->assertConflict()->assertHeader('X-Inertia-Location', route('login'))
        ->assertSessionHas('status', __('passwords.reset'));

    expect(Hash::check('new-safe-password', $user->fresh()->password))->toBeTrue();
    $this->assertGuest();
});
