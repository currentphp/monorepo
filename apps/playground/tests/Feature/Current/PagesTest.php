<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

it('resolves the active page and breadcrumbs for the project index', function () {
    $this->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('projects')
        ->where('current.pageId', 'projects')
        ->where('current.breadcrumbs.0.label', 'Projects')
        ->where('current.breadcrumbs.0.current', true)
        ->has('projects', 2));
});

it('resolves project labels and URLs from the active bound resource', function (int $id, string $name) {
    $this->get(route('projects.edit', $id))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('projects')
        ->where('current.pageId', 'projects.edit')
        ->where('current.breadcrumbs.1.label', $name)
        ->where('current.breadcrumbs.1.url', route('projects.show', $id))
        ->where('current.breadcrumbs.2.label', 'Edit')
        ->where('current.breadcrumbs.2.url', route('projects.edit', $id))
        ->where('current.breadcrumbs.2.current', true)
        ->where('project.name', $name));

    $this->assertCurrentPage('projects.edit')
        ->assertBreadcrumbs(['Projects', $name, 'Edit'])
        ->assertMenuActive('primary', 'projects');
})->with(['Apollo' => [42, 'Apollo'], 'Artemis' => [77, 'Artemis']]);

it('returns 404 for an unknown project', function () {
    $this->get(route('projects.show', 'missing'))->assertNotFound();
});

it('takes each projects page title from Current', function (string $url, string $title) {
    $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->where('current.title', $title));

    $this->assertTitle($title);
})->with([
    'index, from the label' => ['/projects', 'Projects'],
    'project, from the subject title' => ['/projects/42', 'Project Apollo'],
    'task, from the subject label' => ['/projects/42/tasks/1', 'Fix login'],
    'edit, from its title callback' => ['/projects/77/edit', 'Edit Artemis'],
]);
