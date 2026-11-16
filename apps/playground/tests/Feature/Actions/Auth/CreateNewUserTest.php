<?php

use App\Actions\Auth\CreateNewUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

test('account creation is usable without an HTTP request or authenticated session', function () {
    $user = app(CreateNewUser::class)->create([
        'name' => ' New User ', 'email' => ' NEW@EXAMPLE.TEST ',
        'password' => 'safe-password-123', 'password_confirmation' => 'safe-password-123',
    ]);

    $this->assertModelExists($user);
    expect($user->email)->toBe('new@example.test');
    expect($user->name)->toBe('New User');
    expect(Hash::check('safe-password-123', $user->password))->toBeTrue();
    $this->assertGuest();
});

test('account creation rejects duplicate emails after normalization', function () {
    User::factory()->create(['email' => 'existing@example.test']);

    try {
        app(CreateNewUser::class)->create([
            'name' => 'Another User', 'email' => ' EXISTING@EXAMPLE.TEST ',
            'password' => 'safe-password-123', 'password_confirmation' => 'safe-password-123',
        ]);
        test()->fail('Duplicate account creation was accepted.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('email', [__('validation.unique', ['attribute' => 'email'])]);
    }

    $this->assertDatabaseCount('users', 1);
});

test('account creation rejects invalid input before persisting', function (array $invalid, string $field) {
    $input = array_replace([
        'name' => 'New User', 'email' => 'new@example.test',
        'password' => 'safe-password-123', 'password_confirmation' => 'safe-password-123',
    ], $invalid);

    try {
        app(CreateNewUser::class)->create($input);
        test()->fail('Invalid account creation was accepted.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field);
    }

    $this->assertDatabaseCount('users', 0);
})->with([
    'blank name' => [['name' => '   '], 'name'],
    'long name' => [['name' => str_repeat('x', 256)], 'name'],
    'non-string name' => [['name' => []], 'name'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'long email' => [['email' => str_repeat('a', 245).'@example.test'], 'email'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'unconfirmed password' => [['password_confirmation' => 'different'], 'password'],
    'non-string password' => [['password' => []], 'password'],
    'password contains a null byte' => [['password' => "safe\0password123", 'password_confirmation' => "safe\0password123"], 'password'],
    'password exceeds bcrypt byte limit' => [['password' => str_repeat('é', 37), 'password_confirmation' => str_repeat('é', 37)], 'password'],
]);

test('production registration enforces the configured strong password rules', function () {
    $this->app->instance('env', 'production');
    Http::preventStrayRequests();
    Http::fake(['api.pwnedpasswords.com/range/*' => Http::response('', 200)]);

    try {
        app(CreateNewUser::class)->create([
            'name' => 'New User', 'email' => 'new@example.test',
            'password' => 'onlylowercase', 'password_confirmation' => 'onlylowercase',
        ]);
        test()->fail('Weak production password was accepted.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('password');
    }

    $this->assertDatabaseCount('users', 0);
    Http::assertNothingSent();
});

test('production registration accepts a strong password without contacting a live breach service', function () {
    $this->app->instance('env', 'production');
    Http::preventStrayRequests();
    Http::fake(['api.pwnedpasswords.com/range/*' => Http::response('', 200)]);

    $user = app(CreateNewUser::class)->create([
        'name' => 'New User', 'email' => 'new@example.test',
        'password' => 'Secure-Password-123!', 'password_confirmation' => 'Secure-Password-123!',
    ]);

    $this->assertModelExists($user);
    Http::assertSentCount(1);
});
