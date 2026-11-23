<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

it('shares component output without account data', function () {
    $this->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('projects')->missing('auth')->missing('actions')
        ->where('current.pageId', 'projects')->has('current.menus', 2));
});

it('shares menus and no subject without an active page on unregistered routes', function () {
    Route::middleware('web')->get('/unregistered', fn () => Inertia::render('projects', ['project' => null, 'projects' => [], 'tasks' => []]));
    $this->get('/unregistered')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.pageId', null)->where('current.subject', null)
        ->where('current.breadcrumbs', [])->where('current.menus.primary.1.active', false));
});
