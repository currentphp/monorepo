<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('verified users update only their own display name', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $original = $user->getAttributes();
    $this->actingAs($user)->get(route('profile.edit'))->assertInertia(fn (Assert $page) => $page->component('settings/profile'));
    $this->patch(route('profile.update'), ['name' => ' New Name ', 'id' => $other->id, 'email' => 'injected@example.test', 'password' => 'injected', 'email_verified_at' => null])
        ->assertRedirectToRoute('profile.edit')->assertSessionHas('status', 'Your display name has been updated.');
    expect($user->fresh()->name)->toBe('New Name');
    foreach (['email', 'password', 'email_verified_at', 'remember_token'] as $field) {
        expect($user->fresh()->getRawOriginal($field))->toBe($original[$field]);
    }
    expect($other->fresh()->name)->toBe($other->name);
    $this->get(route('profile.edit'))->assertInertia(fn (Assert $page) => $page->where('auth.user.name', 'New Name'));
});

test('profile rejects invalid transport input', function (mixed $name) {
    $user = User::factory()->create();
    $this->actingAs($user)->patchJson(route('profile.update'), ['name' => $name])->assertUnprocessable()->assertJsonValidationErrors('name');
    expect($user->fresh()->name)->toBe($user->name);
})->with([null, [['array']], 123, '   ', str_repeat('x', 256)]);

test('guests and unverified users cannot read or update profile settings', function () {
    $this->get(route('profile.edit'))->assertRedirectToRoute('login');
    $this->patch(route('profile.update'), ['name' => 'Injected'])->assertRedirectToRoute('login');
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->get(route('profile.edit'))->assertRedirectToRoute('verification.notice');
    $this->patch(route('profile.update'), ['name' => 'Injected'])->assertRedirectToRoute('verification.notice');
    expect($user->fresh()->name)->toBe($user->name);
});

test('profile mutation enforces CSRF', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->app->instance('env', 'local');
    $this->patch(route('profile.update'), ['name' => 'Injected'])->assertStatus(419);
    expect($user->fresh()->name)->toBe($user->name);
});
