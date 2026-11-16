<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use LogicException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Mail::extend('log', fn () => throw new LogicException('Mail logging is disabled. Configure SMTP or an in-memory test transport.'));

        Context::dehydrating(function (Repository $context): void {
            $requestId = $context->get('request_id');
            $context->flush();
            if (is_string($requestId) && preg_match('/^[a-f0-9-]{36}$/D', $requestId)) {
                $context->add('request_id', $requestId);
            }
        });
        Queue::after(fn () => Context::flush());
        Queue::looping(fn () => Context::flush());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
