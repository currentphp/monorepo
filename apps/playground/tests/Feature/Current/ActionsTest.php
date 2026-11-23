<?php

declare(strict_types=1);

use App\ProjectExample;
use App\TaskExample;
use Current\Actions\Actions;
use Inertia\Testing\AssertableInertia as Assert;

// Actions (Current\Actions): what users can do, described once; the routes
// perform and authorize. Hidden actions are absent; disabled ones keep a reason.

it('gives every visitor the page action and each row its actions', function () {
    $this->get(route('tasks.index', 42))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.actions.0', [
            'id' => 'projects.tasks.create',
            'name' => 'create',
            'label' => 'New task',
            'url' => route('tasks.create', 42),
            'method' => 'GET',
            'icon' => 'plus',
            'destructive' => false,
            'enabled' => true,
            'reason' => null,
            'confirm' => null,
            'current' => false,
        ])
        ->where('rows.0.actions.0.id', 'task.edit')
        ->where('rows.0.actions.0.url', route('tasks.edit', [42, 1]))
        ->where('rows.0.actions.1', [
            'id' => 'task.archive',
            'name' => 'archive',
            'label' => 'Archive',
            'url' => route('tasks.archive', [42, 1]),
            'method' => 'POST',
            'icon' => 'archive',
            'destructive' => true,
            'enabled' => true,
            'reason' => null,
            'confirm' => 'Archive “Fix login”?',
            'current' => false,
        ]));
});

it('disables protected task actions and enforces their policy on the route', function () {
    $this->get(route('tasks.show', [42, 2]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.actions.1.enabled', false)
        ->where('current.actions.1.reason', 'This demo task is protected from archiving.'));
    $this->post(route('tasks.archive', [42, 2]))->assertForbidden();
});

it('takes the current subject’s actions on pages below it', function () {
    $this->get(route('tasks.edit', [77, 4]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('tasks')
        ->where('current.title', 'Edit')
        ->where('current.actions.0.id', 'task.edit')
        ->where('current.actions.0.current', true)
        ->where('current.actions.1.id', 'task.archive')
        ->where('form.method', 'put'));

    $this->assertCurrentPage('tasks.edit')->assertBreadcrumbs(['Projects', 'Artemis', 'Tasks', 'Review budget', 'Edit']);
});

it('resolves one object’s actions for a table row', function () {
    $this->get(route('projects.index'))->assertOk();

    $task = TaskExample::findExample(ProjectExample::findExample('77') ?? throw new LogicException, '5');

    expect(array_map(fn ($action) => $action->id, app(Actions::class)->for($task ?? throw new LogicException)))
        ->toBe(['task.edit', 'task.archive']);
});

it('performs actions only through the app’s routes, which never write', function () {
    $this->get(route('tasks.create', 42))->assertOk();

    $this->post(route('tasks.archive', [42, 1]))
        ->assertRedirect(route('tasks.index', 42))
        ->assertSessionHas('status', 'Demo: “Fix login” was not archived.');

    $this->put(route('tasks.update', [42, 2]), ['title' => 'Changed'])
        ->assertSessionHas('status', 'Demo: changes to “Write release notes” were not saved.');

    $this->post(route('tasks.store', 42), ['title' => 'New'])->assertSessionHas('status');
});
