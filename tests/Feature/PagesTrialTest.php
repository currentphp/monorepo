<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the public project index using the installed Pages package', function () {
    $this->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('pages-trial')
        ->where('currentPageId', 'projects')
        ->where('breadcrumbs.0.label', 'Projects')
        ->where('primary.0.current', true)
        ->where('footer.0.children.1.label', 'All projects')
        ->where('footer.1.pageId', 'login')
        ->has('projects', 2));
});

it('resolves project labels and URLs from the active bound resource', function (int $id, string $name) {
    $this->get(route('projects.edit', $id))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('pages-trial')
        ->where('breadcrumbs.1.label', $name)
        ->where('breadcrumbs.1.url', route('projects.show', $id))
        ->where('breadcrumbs.2.label', 'Edit')
        ->where('primary.0.active', true)
        ->where('primary.0.children', [])
        ->where('project.name', $name));
})->with(['Apollo' => [42, 'Apollo'], 'Artemis' => [77, 'Artemis']]);

it('shows the profile placement for a verified user without the sign-in link', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('pages-trial')
        ->has('footer', 2)
        ->where('footer.1.children.0.pageId', 'profile'));
});

it('omits verified-account navigation for an unverified user', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('pages-trial')
        ->has('footer', 1));
});

it('returns 404 for an unknown project', function () {
    $this->get(route('projects.show', 'missing'))->assertNotFound();
});
