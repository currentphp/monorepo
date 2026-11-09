<?php

use App\Models\User;
use App\Notifications\Auth\VerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

test('registration dispatches verification and unverified users can only reach verification and logout', function () {
    Notification::fake();
    $this->post(route('register.store'), ['name' => 'New User', 'email' => 'new@example.test', 'password' => 'safe-password-123', 'password_confirmation' => 'safe-password-123'])->assertRedirectToRoute('dashboard');
    $user = User::sole();
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->get(route('dashboard'))->assertRedirectToRoute('verification.notice');
    $this->get(route('verification.notice'))->assertInertia(fn (Assert $page) => $page->component('auth/verify-email'));
    $this->post(route('logout'))->assertRedirectToRoute('home');
    $this->get(route('verification.notice'))->assertRedirectToRoute('login');
    $this->post(route('verification.send'))->assertRedirectToRoute('login');
});

test('a valid verification link verifies once and opens the shell', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
    $this->actingAs($user)->get($url)->assertRedirect();
    $this->get($url)->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatchedTimes(Verified::class, 1);
    $this->get(route('dashboard'))->assertOk();
    $this->get(route('verification.notice'))->assertRedirectToRoute('dashboard');
});

test('invalid verification links cannot verify an account', function (string $scenario) {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', $scenario === 'expired' ? now()->subMinute() : now()->addHour(), [
        'id' => $user->id, 'hash' => $scenario === 'wrong-email' ? sha1('wrong@example.test') : sha1($user->email),
    ]);
    if ($scenario === 'tampered') {
        $url .= '&extra=tampered';
    }
    $actor = $scenario === 'wrong-user' ? User::factory()->unverified()->create() : $user;
    $this->actingAs($actor)->get($url)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
})->with(['expired', 'tampered', 'wrong-user', 'wrong-email']);

test('resends are throttled and verified users receive no additional mail', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->from(route('verification.notice'))->post(route('verification.send'))->assertRedirect()->assertSessionHas('status', 'verification-link-sent');
    }
    $this->postJson(route('verification.send'))->assertTooManyRequests();
    Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    $this->travel(61)->seconds();
    $user->markEmailAsVerified();
    $this->postJson(route('verification.send'))->assertNoContent();
    Notification::assertSentToTimes($user, VerifyEmail::class, 6);
});

test('verification email uses the configured origin and skips already verified recipients', function () {
    config(['app.url' => 'https://accounts.example.test']);
    $user = User::factory()->unverified()->create();
    $notification = new VerifyEmail;
    $url = $notification->toMail($user)->actionUrl;
    expect($url)->toStartWith('https://accounts.example.test/email/verify/');
    expect(URL::hasValidSignature(Request::create($url)))->toBeTrue();
    expect($notification->shouldSend($user, 'mail'))->toBeTrue();
    $user->markEmailAsVerified();
    expect($notification->shouldSend($user, 'mail'))->toBeFalse();
});

test('verification resends enforce CSRF', function () {
    Notification::fake();
    $this->actingAs(User::factory()->unverified()->create());
    $this->app->instance('env', 'local');
    $this->post(route('verification.send'))->assertStatus(419);
    Notification::assertNothingSent();
});

test('verification jobs are published only after commit and discarded on rollback', function () {
    config(['queue.default' => 'database']);
    $user = User::factory()->unverified()->create();
    DB::beginTransaction();
    $user->sendEmailVerificationNotification();
    $this->assertDatabaseCount('jobs', 0);
    DB::rollBack();
    $this->assertDatabaseCount('jobs', 0);

    DB::beginTransaction();
    $user->sendEmailVerificationNotification();
    $this->assertDatabaseCount('jobs', 0);
    DB::commit();
    $this->assertDatabaseCount('jobs', 1);
});
