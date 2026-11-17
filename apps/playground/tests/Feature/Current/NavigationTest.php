<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('derives the primary menu and keeps Projects active on excluded project pages', function () {
    $this->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.menus.primary.0.current', true));

    $this->get(route('projects.edit', 42))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.menus.primary.0.label', 'Projects')
        ->where('current.menus.primary.0.active', true)
        ->where('current.menus.primary.0.current', false)
        ->where('current.menus.primary.0.children', []));
});

it('places the project index in the footer with its own label for guests', function () {
    $this->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.menus.footer.0.kind', 'group')
        ->where('current.menus.footer.0.children.1.label', 'All projects')
        ->where('current.menus.footer.1.pageId', 'login'));
});

it('shows the profile placement for a verified user without the sign-in link', function () {
    $this->actingAs(User::factory()->create())->get(route('projects.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('current.menus.footer', 2)
            ->where('current.menus.footer.1.children.0.pageId', 'profile'));

    $this->assertMenuHas('footer', 'profile')->assertMenuMissing('footer', 'login');
});

it('omits verified-account navigation for an unverified user', function () {
    $this->actingAs(User::factory()->unverified()->create())->get(route('projects.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('current.menus.footer', 1));

    $this->assertMenuMissing('footer', 'profile')->assertMenuMissing('footer', 'login');
});
