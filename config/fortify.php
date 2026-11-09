<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'home' => '/dashboard',
    'views' => true,
    'registration_enabled' => env('AUTH_REGISTRATION_ENABLED', true),
    // Normalize valid string inputs before Fortify, without coercing malformed input.
    'lowercase_usernames' => false,
    'limiters' => ['login' => 'login'],
    'features' => [Features::registration(), Features::resetPasswords(), Features::emailVerification()],
];
