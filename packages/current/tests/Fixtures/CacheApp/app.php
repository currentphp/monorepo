<?php

declare(strict_types=1);

use Current\CurrentServiceProvider;
use Current\Tests\Fixtures\CacheApp\FixtureController;
use Current\Tests\Fixtures\CacheApp\FixtureProvider;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([CurrentServiceProvider::class, FixtureProvider::class])
    ->withRouting(using: function (): void {
        Route::get('/projects', [FixtureController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [FixtureController::class, 'show'])->name('projects.show');
        Route::get('/projects/{project}/edit/{tab?}', [FixtureController::class, 'show'])
            ->middleware(SubstituteBindings::class)->name('projects.edit');
    })
    ->create();
