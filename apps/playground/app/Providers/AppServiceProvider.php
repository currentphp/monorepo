<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Auth\GenericUser;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

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
        // Every visitor is the same synthetic actor; this is a local demo, not authentication.
        Auth::viaRequest('demo', fn (): GenericUser => new GenericUser(['id' => 1, 'name' => 'Demo maintainer']));

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

    }
}
