<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

it('shares component output with pages outside the projects demo', function () {
    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('welcome')
        ->where('current.pageId', 'home')
        ->where('current.breadcrumbs.0.label', 'Home')
        ->has('current.menus', 2)
        ->where('current.menus.footer.0.children.0.current', true));
});

it('shares menus without an active page on unregistered routes', function () {
    $this->get(route('password.request'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.pageId', null)
        ->where('current.breadcrumbs', [])
        ->where('current.menus.primary.0.active', false));
});
