<?php

declare(strict_types=1);

use App\Models\User;
use App\ProjectExample;
use App\Prototype\Actions\Actions;
use App\TaskExample;
use Inertia\Testing\AssertableInertia as Assert;

// Actions prototype (WK-008): what users can do, described once; the routes
// perform and authorize. Hidden actions are absent; disabled ones keep a reason.

it('shows guests the task list without any actions', function () {
    $this->get(route('tasks.index', 42))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('tasks')
        ->where('title', 'Tasks')
        ->where('actions', [])
        ->has('rows', 2)
        ->where('rows.0.subject.label', 'Fix login')
        ->where('rows.0.actions', []));
});

it('gives signed-in users the page action and each row its actions', function () {
    $this->actingAs(User::factory()->create())->get(route('tasks.index', 42))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('actions.0', [
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
        ]));
});

it('disables archive with the policy reason for unverified users', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('tasks.show', [42, 1]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('actions.0.id', 'task.edit')
        ->where('actions.1.id', 'task.archive')
        ->where('actions.1.enabled', false)
        ->where('actions.1.reason', 'Verify your email address to archive tasks.'));

    $this->post(route('tasks.archive', [42, 1]))->assertForbidden();
});

it('takes the current subject’s actions on pages below it', function () {
    $this->actingAs(User::factory()->create())->get(route('tasks.edit', [77, 4]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('tasks')
        ->where('title', 'Edit')
        ->where('actions.0.id', 'task.edit')
        ->where('actions.1.id', 'task.archive')
        ->where('form.method', 'put'));

    $this->assertCurrentPage('tasks.edit')->assertBreadcrumbs(['Projects', 'Artemis', 'Tasks', 'Review budget', 'Edit']);
});

it('resolves one object’s actions for a table row', function () {
    $this->actingAs(User::factory()->create())->get(route('projects.index'))->assertOk();

    $task = TaskExample::findExample(ProjectExample::findExample('77') ?? throw new LogicException, '5');

    expect(array_map(fn ($action) => $action->id, app(Actions::class)->for($task ?? throw new LogicException)))
        ->toBe(['task.edit', 'task.archive']);
});

it('performs actions only through the app’s routes, which never write', function () {
    $this->get(route('tasks.create', 42))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->post(route('tasks.archive', [42, 2]))
        ->assertRedirect(route('tasks.index', 42))
        ->assertSessionHas('status', 'Prototype: “Write release notes” was not archived.');

    $this->put(route('tasks.update', [42, 2]), ['title' => 'Changed'])
        ->assertSessionHas('status', 'Prototype: changes to “Write release notes” were not saved.');

    $this->post(route('tasks.store', 42), ['title' => 'New'])->assertSessionHas('status');
});
