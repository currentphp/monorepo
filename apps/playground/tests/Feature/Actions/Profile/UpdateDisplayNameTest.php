<?php

use App\Actions\Profile\UpdateDisplayName;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

test('a verified actor can update their display name without HTTP or a session', function () {
    $user = User::factory()->create();
    $result = app(UpdateDisplayName::class)->handle($user, $user, ' New Name ');
    expect($result->name)->toBe('New Name');
    expect($user->fresh()->name)->toBe('New Name');
    $this->assertGuest();
});

test('direct profile actions deny another actor or an unverified actor', function (string $scenario) {
    $target = User::factory()->create();
    $actor = $scenario === 'other-user' ? User::factory()->create() : $target;
    if ($scenario === 'unverified') {
        $actor->markEmailAsUnverified();
    }
    expect(fn () => app(UpdateDisplayName::class)->handle($actor, $target, 'Injected'))->toThrow(AuthorizationException::class);
    expect($target->fresh()->name)->toBe($target->name);
})->with(['other-user', 'unverified']);

test('direct profile actions preserve name invariants', function (string $name) {
    $user = User::factory()->create();
    expect(fn () => app(UpdateDisplayName::class)->handle($user, $user, $name))->toThrow(ValidationException::class);
    expect($user->fresh()->name)->toBe($user->name);
})->with(['   ', str_repeat('x', 256)]);
