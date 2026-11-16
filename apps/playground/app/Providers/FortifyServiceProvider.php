<?php

namespace App\Providers;

use App\Actions\Auth\CreateNewUser;
use App\Actions\Auth\ResetUserPassword;
use App\Http\Responses\PasswordResetLinkResponse;
use App\Http\Responses\PasswordResetResponse;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Responses\FailedPasswordResetResponse as InvalidPasswordResetResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();
        $this->app->bind(\Laravel\Fortify\Contracts\PasswordResetResponse::class, PasswordResetResponse::class);
        $this->app->bind(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
        $this->app->bind(FailedPasswordResetResponse::class, fn (): InvalidPasswordResetResponse => new InvalidPasswordResetResponse(Password::INVALID_TOKEN));
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::verifyEmailView(fn (Request $request): Response => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));
        Fortify::registerView(fn (): Response => Inertia::render('auth/register'));
        Fortify::loginView(fn (Request $request): Response => Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
        ]));
        Fortify::requestPasswordResetLinkView(fn (Request $request): Response => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));
        Fortify::resetPasswordView(function (Request $request): HttpResponse {
            $response = Inertia::render('auth/reset-password', [
                'email' => is_string($request->query('email')) ? $request->query('email') : '',
                'token' => $request->route('token'),
            ])->toResponse($request);
            $response->headers->set('Referrer-Policy', 'no-referrer');
            $response->headers->set('Cache-Control', 'no-store');

            return $response;
        });

        ResetPassword::createUrlUsing(fn (User $user, string $token): string => rtrim(config('app.url'), '/').route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], false));

        foreach (['password-recovery', 'password-reset'] as $limiter) {
            RateLimiter::for($limiter, function (Request $request) use ($limiter): array {
                $email = $request->input('email');
                $key = hash('sha256', (is_string($email) ? Str::lower(trim($email)) : '').'|'.$request->ip());

                return [
                    Limit::perMinute(5)->by($limiter.'-account:'.$key)->response($this->throttleResponse(...)),
                    Limit::perMinute(30)->by($limiter.'-ip:'.$request->ip())->response($this->throttleResponse(...)),
                ];
            });
        }

        RateLimiter::for('login', function (Request $request): array {
            $email = $request->input('email');
            $key = hash('sha256', (is_string($email) ? Str::lower(trim($email)) : '').'|'.$request->ip());

            return [
                Limit::perMinute(5)->by('login-account:'.$key)->response($this->throttleResponse(...)),
                Limit::perMinute(30)->by('login-ip:'.$request->ip())->response($this->throttleResponse(...)),
            ];
        });

        RateLimiter::for('verification', fn (Request $request): Limit => Limit::perMinute(6)
            ->by('verification:'.$request->user()?->getAuthIdentifier())
            ->response($this->throttleResponse(...)));

        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perMinute(5)
            ->by('registration:'.$request->ip())
            ->response($this->throttleResponse(...)));
    }

    /**
     * @param  array<string, int|string>  $headers
     */
    private function throttleResponse(Request $request, array $headers): HttpResponse
    {
        $message = 'Too many attempts. Please try again in a minute.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 429, $headers);
        }

        return back()->withErrors(['email' => $message])->withHeaders($headers);
    }
}
