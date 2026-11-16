<?php

declare(strict_types=1);

use App\WavyKit\SharedProps;
use Inertia\Testing\AssertableInertia as Assert;

it('shares component output with pages outside the projects demo', function () {
    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('welcome')
        ->where('wavykit.pageId', 'home')
        ->where('wavykit.breadcrumbs.0.label', 'Home')
        ->has('wavykit.menus', count(SharedProps::MENUS))
        ->where('wavykit.menus.footer.0.children.0.current', true));
});

it('shares menus without an active page on unregistered routes', function () {
    $this->get(route('password.request'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('wavykit.pageId', null)
        ->where('wavykit.breadcrumbs', [])
        ->where('wavykit.menus.primary.0.active', false));
});
