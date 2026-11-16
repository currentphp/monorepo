<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('guests can see registration and nullable safe auth props', function () {
    $this->get(route('register'))->assertInertia(fn (Assert $page) => $page
        ->component('auth/register')
        ->where('auth.user', null)
        ->where('auth.canRegister', true));
});

test('registration creates an unverified account and signs in without accepting privileged fields', function () {
    Event::fake([Registered::class]);
    $this->withSession(['marker' => 'guest']);
    $sessionId = session()->getId();

    $this->post(route('register.store'), [
        'name' => ' New User ',
        'email' => ' NEW@EXAMPLE.TEST ',
        'password' => 'safe-password-123',
        'password_confirmation' => 'safe-password-123',
        'id' => 999999,
        'email_verified_at' => now()->toISOString(),
        'remember_token' => 'injected',
    ])->assertRedirectToRoute('dashboard');

    $user = User::sole();
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', ['name' => 'New User', 'email' => 'new@example.test', 'email_verified_at' => null]);
    expect($user->id)->not->toBe(999999);
    expect($user->remember_token)->toBeNull();
    expect(Hash::check('safe-password-123', $user->password))->toBeTrue();
    expect(session()->getId())->not->toBe($sessionId);
    Event::assertDispatched(Registered::class, fn (Registered $event) => $event->user->is($user));
});

test('disabled registration denies page and submission and hides registration availability', function () {
    config(['fortify.registration_enabled' => false]);

    $this->get(route('register'))->assertNotFound();
    $this->post(route('register.store'), [
        'name' => 'New User', 'email' => 'new@example.test',
        'password' => 'safe-password-123', 'password_confirmation' => 'safe-password-123',
    ])->assertNotFound();
    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('auth.canRegister', false));
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('auth.canRegister', false));
    $this->assertDatabaseCount('users', 0);
    $this->assertGuest();
});

test('registration returns validation errors and never flashes passwords', function () {
    $this->from(route('register'))->post(route('register.store'), [
        'name' => '', 'email' => 'invalid', 'password' => 'short', 'password_confirmation' => 'different',
    ])->assertRedirectToRoute('register')
        ->assertSessionHasErrors(['name', 'email', 'password'])
        ->assertSessionMissingInput('password')
        ->assertSessionMissingInput('password_confirmation');

    $this->assertDatabaseCount('users', 0);
    $this->assertGuest();
});

test('registration rejects malformed email input without an internal error', function () {
    $this->postJson(route('register.store'), ['email' => ['malformed']])
        ->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->assertDatabaseCount('users', 0);
});

test('registration throttles account creation attempts by IP', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson(route('register.store'), [])->assertUnprocessable();
    }

    $this->postJson(route('register.store'), [])->assertTooManyRequests()->assertHeader('Retry-After');
    $this->assertDatabaseCount('users', 0);
});

test('registration throttling gives browser forms an actionable error', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson(route('register.store'), [])->assertUnprocessable();
    }

    $this->from(route('register'))->post(route('register.store'), [])
        ->assertRedirectToRoute('register')
        ->assertSessionHasErrors(['email' => 'Too many attempts. Please try again in a minute.'])
        ->assertHeader('Retry-After');
});
