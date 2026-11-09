<?php

use App\Models\User;

test('guest authentication submissions reject missing CSRF tokens', function (string $route) {
    $this->app->instance('env', 'local');

    $this->post(route($route), [])->assertStatus(419);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
})->with(['login.store', 'register.store', 'password.email', 'password.update']);

test('authentication accepts a matching CSRF token with protection active', function () {
    $user = User::factory()->create();
    $this->app->instance('env', 'local');

    $this->withSession(['_token' => 'test-csrf-token'])->post(route('login.store'), [
        '_token' => 'test-csrf-token', 'email' => $user->email, 'password' => 'password',
    ])->assertRedirectToRoute('dashboard');

    $this->assertAuthenticatedAs($user);
});

test('logout rejects an invalid CSRF token without ending the session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->app->instance('env', 'local');

    $this->withSession(['_token' => 'actual-token'])->post(route('logout'), ['_token' => 'wrong-token'])->assertStatus(419);

    $this->assertAuthenticatedAs($user);
});
