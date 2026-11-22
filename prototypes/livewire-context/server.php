<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Current\CurrentServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\LivewireServiceProvider;
use Prototype\Identity;
use Prototype\ProjectPage;
use Prototype\Provider;

$app = Application::configure(basePath: __DIR__)
    ->withExceptions(fn () => null)
    ->withProviders([CurrentServiceProvider::class, LivewireServiceProvider::class, Provider::class])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [Identity::class]);
    })
    ->withRouting(using: function (): void {
        Route::middleware('web')->group(function (): void {
            Route::get('/projects', fn () => 'Projects')->name('projects.index');
            Route::get('/projects/{project}', fn () => 'Project')->name('projects.show');
            Route::get('/projects/{project}/edit', ProjectPage::class)->middleware('can:view,project')->name('projects.edit');
            Route::post('/projects/{project}/rename', fn () => abort(405))->name('projects.rename');
        });
    })
    ->create();
$app->useStoragePath(__DIR__.'/.runtime/storage');
$app->handleRequest(Request::capture());
