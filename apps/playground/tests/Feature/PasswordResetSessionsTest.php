<?php

use App\Actions\Auth\ResetUserPassword;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;

/** @param array<string, mixed> $input
 * @param  array<string, string>  $cookies
 */
function passwordResetBrowserRequest(string $method, string $uri, array $input = [], array $cookies = []): TestResponse
{
    app('auth')->forgetGuards();
    app()->forgetInstance('auth.driver');
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');
    app()->forgetInstance('cookie');
    app()->forgetScopedInstances();
    app('redirect')->setSession(app('session.store'));
    foreach (app('router')->getRoutes() as $route) {
        $route->flushController();
    }

    return test()->call($method, $uri, $input, $cookies);
}

/** @return array<string, string> */
function passwordResetBrowserLogin(User $user): array
{
    $response = passwordResetBrowserRequest('POST', route('login.store'), [
        'email' => $user->email, 'password' => 'password', 'remember' => true,
    ])->assertRedirectToRoute('dashboard');

    $cookies = [];
    foreach ($response->headers->getCookies() as $cookie) {
        $cookies[$cookie->getName()] = $cookie->getValue();
    }

    passwordResetBrowserRequest('GET', route('dashboard'), cookies: $cookies)->assertOk();

    return $cookies;
}

test('password reset revokes every existing browser session and remember cookie only for its user', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $firstBrowser = passwordResetBrowserLogin($user);
    $secondBrowser = passwordResetBrowserLogin($user);
    $otherBrowser = passwordResetBrowserLogin($otherUser);
    $token = Password::broker()->createToken($user);

    passwordResetBrowserRequest('POST', route('password.update'), [
        'email' => $user->email, 'token' => $token,
        'password' => 'new-safe-password', 'password_confirmation' => 'new-safe-password',
    ])->assertRedirectToRoute('login');

    $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    $this->assertDatabaseHas('sessions', ['user_id' => $otherUser->id]);
    foreach ([$firstBrowser, $secondBrowser] as $cookies) {
        passwordResetBrowserRequest('GET', route('dashboard'), cookies: $cookies)->assertRedirectToRoute('login');
        unset($cookies[config('session.cookie')]);
        passwordResetBrowserRequest('GET', route('dashboard'), cookies: $cookies)->assertRedirectToRoute('login');
    }
    passwordResetBrowserRequest('GET', route('dashboard'), cookies: $otherBrowser)->assertOk();
    passwordResetBrowserRequest('POST', route('login.store'), [
        'email' => $user->email, 'password' => 'new-safe-password',
    ])->assertRedirectToRoute('dashboard');
    $this->assertAuthenticatedAs($user);
});

test('rejected password resets preserve existing browser access and remember tokens', function (string $scenario) {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $cookies = passwordResetBrowserLogin($user);
    $token = Password::broker()->createToken($user);
    if ($scenario === 'expired') {
        $this->travel(61)->minutes();
    } elseif ($scenario === 'reused') {
        passwordResetBrowserRequest('POST', route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirectToRoute('login');
        $cookies = passwordResetBrowserLogin($user);
    } elseif ($scenario === 'invalid token') {
        $token .= 'tampered';
    }
    $before = $user->fresh();

    passwordResetBrowserRequest('POST', route('password.update'), [
        'email' => $user->email, 'token' => $token,
        'password' => 'new-safe-password',
        'password_confirmation' => $scenario === 'invalid input' ? 'different' : 'new-safe-password',
    ])->assertSessionHasErrors($scenario === 'invalid input' ? 'password' : 'email');

    expect($user->fresh()->password)->toBe($before->password);
    expect($user->fresh()->remember_token)->toBe($before->remember_token);
    passwordResetBrowserRequest('GET', route('dashboard'), cookies: $cookies)->assertOk();
})->with(['invalid token', 'expired', 'reused', 'invalid input']);

test('stale session payloads cannot restore access after a password reset', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $cookies = passwordResetBrowserLogin($user);
    $staleSession = DB::table('sessions')->where('user_id', $user->id)->first();
    $token = Password::broker()->createToken($user);

    passwordResetBrowserRequest('POST', route('password.update'), [
        'email' => $user->email, 'token' => $token,
        'password' => 'new-safe-password', 'password_confirmation' => 'new-safe-password',
    ])->assertRedirectToRoute('login');

    DB::table('sessions')->updateOrInsert(['id' => $staleSession->id], (array) $staleSession);
    passwordResetBrowserRequest('GET', route('dashboard'), cookies: $cookies)->assertRedirectToRoute('login');
    $this->assertGuest();
});

test('reset action revokes database sessions and remember tokens without an HTTP caller', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    passwordResetBrowserLogin($user);
    $rememberToken = $user->fresh()->remember_token;

    app(ResetUserPassword::class)->reset($user->fresh(), [
        'password' => 'new-safe-password', 'password_confirmation' => 'new-safe-password',
    ]);

    $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    expect($user->fresh()->remember_token)->not->toBe($rememberToken);
});

test('session deletion failure rolls back reset credentials', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $cookies = passwordResetBrowserLogin($user);
    $before = $user->fresh();
    config(['session.table' => 'missing_sessions_table']);

    expect(fn () => app(ResetUserPassword::class)->reset($user->fresh(), [
        'password' => 'new-safe-password', 'password_confirmation' => 'new-safe-password',
    ]))->toThrow(QueryException::class);

    expect($user->fresh()->password)->toBe($before->password);
    expect($user->fresh()->remember_token)->toBe($before->remember_token);
    config(['session.table' => 'sessions']);
    passwordResetBrowserRequest('GET', route('dashboard'), cookies: $cookies)->assertOk();
});
