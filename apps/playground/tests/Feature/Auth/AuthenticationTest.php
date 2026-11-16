<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests see the login screen and cannot access the account page', function () {
    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->component('auth/login'));
    $this->get(route('dashboard'))->assertRedirectToRoute('login');
});

test('login normalizes email and rotates the session', function () {
    $user = User::factory()->create(['email' => 'member@example.test']);
    $this->withSession(['marker' => 'guest']);
    $sessionId = session()->getId();

    $this->post(route('login.store'), [
        'email' => ' MEMBER@EXAMPLE.TEST ', 'password' => 'password', 'remember' => true,
    ])->assertRedirectToRoute('dashboard')->assertCookie(auth()->guard('web')->getRecallerName());

    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($sessionId);
});

test('invalid credentials give the same error for known and unknown accounts', function (string $email) {
    User::factory()->create(['email' => 'known@example.test']);

    $this->from(route('login'))->post(route('login.store'), [
        'email' => $email, 'password' => 'incorrect-password',
    ])->assertRedirectToRoute('login')
        ->assertSessionHasErrors(['email' => __('auth.failed')])
        ->assertSessionMissingInput('password');

    $this->assertGuest();
})->with(['known@example.test', 'unknown@example.test']);

test('login validates malformed or missing input', function (array $input) {
    $this->postJson(route('login.store'), $input)->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    $this->assertGuest();
})->with(['empty' => [[]], 'malformed' => [['email' => ['invalid'], 'password' => ['invalid']]]]);

test('login attempts share a rate limit across email case variants', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson(route('login.store'), ['email' => 'MEMBER@example.test', 'password' => 'incorrect'])->assertUnprocessable();
    }

    $this->postJson(route('login.store'), ['email' => 'member@EXAMPLE.TEST', 'password' => 'incorrect'])
        ->assertTooManyRequests()->assertHeader('Retry-After');
    $this->assertGuest();
});

test('login IP limit prevents bypass by rotating email addresses', function () {
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->postJson(route('login.store'), ['email' => "member{$attempt}@example.test", 'password' => 'incorrect'])->assertUnprocessable();
    }

    $this->postJson(route('login.store'), ['email' => 'another@example.test', 'password' => 'incorrect'])->assertTooManyRequests();
    $this->assertGuest();
});

test('login rate limiting recovers after its window', function () {
    $this->freezeTime();
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson(route('login.store'), ['email' => 'member@example.test', 'password' => 'incorrect'])->assertUnprocessable();
    }
    $this->travel(61)->seconds();

    $this->postJson(route('login.store'), ['email' => 'member@example.test', 'password' => 'incorrect'])->assertUnprocessable();
});

test('authenticated users are redirected away from guest authentication pages', function (string $route) {
    $this->actingAs(User::factory()->create())->get(route($route))->assertRedirectToRoute('dashboard');
})->with(['login', 'register']);

test('account page shares only the explicit user contract', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('auth.user', 4)
        ->where('auth.user.id', $user->id)
        ->where('auth.user.name', $user->name)
        ->where('auth.user.email', $user->email)
        ->where('auth.user.email_verified_at', $user->email_verified_at->toISOString())
        ->missing('auth.user.password')
        ->missing('auth.user.remember_token'));
});

test('logout clears authentication and session data and rotates the CSRF token', function () {
    $this->actingAs(User::factory()->create())->withSession(['private-state' => 'value']);
    $sessionId = session()->getId();
    $token = session()->token();

    $this->post(route('logout'))->assertRedirectToRoute('home')->assertSessionMissing('private-state');

    $this->assertGuest();
    expect(session()->getId())->not->toBe($sessionId);
    expect(session()->token())->not->toBe($token);
    $this->get(route('dashboard'))->assertRedirectToRoute('login');
});

test('logout is not available through a GET request', function () {
    $this->actingAs(User::factory()->create())->get(route('logout'))->assertMethodNotAllowed();
    $this->assertAuthenticated();
});
