<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('derives the primary menu and keeps Projects active on excluded project pages', function () {
    $this->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('wavykit.menus.primary.0.current', true));

    $this->get(route('projects.edit', 42))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('wavykit.menus.primary.0.label', 'Projects')
        ->where('wavykit.menus.primary.0.active', true)
        ->where('wavykit.menus.primary.0.current', false)
        ->where('wavykit.menus.primary.0.children', []));
});

it('places the project index in the footer with its own label for guests', function () {
    $this->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('wavykit.menus.footer.0.kind', 'group')
        ->where('wavykit.menus.footer.0.children.1.label', 'All projects')
        ->where('wavykit.menus.footer.1.pageId', 'login'));
});

it('shows the profile placement for a verified user without the sign-in link', function () {
    $this->actingAs(User::factory()->create())->get(route('projects.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('wavykit.menus.footer', 2)
            ->where('wavykit.menus.footer.1.children.0.pageId', 'profile'));
});

it('omits verified-account navigation for an unverified user', function () {
    $this->actingAs(User::factory()->unverified()->create())->get(route('projects.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('wavykit.menus.footer', 1));
});
