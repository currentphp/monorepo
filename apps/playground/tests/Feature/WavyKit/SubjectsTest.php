<?php

declare(strict_types=1);

use App\ProjectExample;
use App\TaskExample;
use Inertia\Testing\AssertableInertia as Assert;
use WavyKit\Subjects\Subjects;

// Subjects described once and read by breadcrumbs, links, the current
// subject, and the Inertia prop.

it('labels nested breadcrumbs from subjects', function () {
    $this->get(route('tasks.show', [42, 1]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('wavykit.pageId', 'tasks.show')
        ->where('wavykit.breadcrumbs.0.label', 'Projects')
        ->where('wavykit.breadcrumbs.1.label', 'Apollo')
        ->where('wavykit.breadcrumbs.1.url', route('projects.show', 42))
        ->where('wavykit.breadcrumbs.2.label', 'Tasks')
        ->where('wavykit.breadcrumbs.2.url', null)
        ->where('wavykit.breadcrumbs.3.label', 'Fix login'));

    $this->assertBreadcrumbs(['Projects', 'Apollo', 'Tasks', 'Fix login'])
        ->assertMenuActive('primary', 'projects');
});

it('shares the subject the active page presents', function () {
    $this->get(route('tasks.show', [77, 4]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('wavykit.subject', [
            'kind' => 'task',
            'id' => '4',
            'label' => 'Review budget',
            'url' => route('tasks.show', [77, 4]),
        ]));

    $this->get(route('projects.show', 77))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('wavykit.subject.kind', 'project')
        ->where('wavykit.subject.label', 'Artemis'));
});

it('shares no subject on pages that present none', function (string $uri) {
    $this->get($uri)->assertOk()->assertInertia(fn (Assert $page) => $page->where('wavykit.subject', null));
})->with([
    'project index' => fn () => route('projects.index'),
    'unregistered page' => fn () => route('password.request'),
]);

it('takes the subject from the nearest presenting ancestor', function () {
    $this->get(route('projects.edit', 42))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('wavykit.subject.kind', 'project')
        ->where('wavykit.subject.label', 'Apollo'));

    $this->assertCurrentSubject('project', '42');
});

it('keeps explicit page labels under a subject page', function () {
    $this->get(route('projects.edit', 42))->assertOk();

    $this->assertBreadcrumbs(['Projects', 'Apollo', 'Edit']);
});

it('links a subject from anywhere through its canonical page', function () {
    $task = TaskExample::findExample(ProjectExample::findExample('77') ?? throw new LogicException, '5')
        ?? throw new LogicException;

    $subject = app(Subjects::class)->of($task);

    expect($subject->key())->toBe('task:5')
        ->and($subject->label)->toBe('Book venue')
        ->and($subject->url)->toBe(route('tasks.show', [77, 5]));

    $this->get(route('projects.show', 42))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('tasks.0.label', 'Fix login')
        ->where('tasks.0.url', route('tasks.show', [42, 1]))
        ->has('tasks', 2));
});

it('returns 404 for a task outside its project', function () {
    $this->get('/projects/77/tasks/1')->assertNotFound();
});
