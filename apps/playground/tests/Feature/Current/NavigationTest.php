<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

it('derives the primary menu and keeps Projects active on excluded project pages', function () {
    $this->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.menus.primary.1.current', true));

    $this->get(route('projects.edit', 42))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.menus.primary.1.label', 'Projects')
        ->where('current.menus.primary.1.active', true)
        ->where('current.menus.primary.1.current', false)
        ->where('current.menus.primary.1.children', []));
});

it('keeps navigation focused on the demos without account links', function () {
    $this->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('current.menus.primary', 3)
        ->has('current.menus.footer', 1)
        ->where('current.menus.footer.0.kind', 'group')
        ->where('current.menus.footer.0.children.0.label', 'All projects'));
});
