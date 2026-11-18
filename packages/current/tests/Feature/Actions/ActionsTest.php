<?php

declare(strict_types=1);

use Current\Actions\Action;
use Current\Actions\ActionRegistry;
use Current\Actions\Actions;
use Current\Context;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Facades\Current;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Subjects\Subject;
use Current\Subjects\SubjectRegistry;
use Current\Testing\InteractsWithCurrent;
use Current\Tests\Fixtures\Project;
use Current\Tests\Fixtures\Task;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\GenericUser;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;

uses(InteractsWithCurrent::class);

function apolloProject(): Project
{
    return new Project(['id' => 42, 'name' => 'Apollo']);
}

function loginTask(): Task
{
    return Task::in(apolloProject(), ['id' => 7, 'title' => 'Fix login']);
}

beforeEach(function () {
    Route::bind('project', fn (string $id): Project => $id === '42' ? apolloProject() : abort(404));
    Route::bind('task', fn (string $id): Task => $id === '7' ? loginTask() : abort(404));
    Route::middleware(SubstituteBindings::class)->group(function () {
        Route::get('/projects', fn () => 'index')->name('projects.index');
        Route::get('/projects/{project}', fn () => 'show')->name('projects.show');
        Route::get('/projects/{project}/edit', fn () => 'edit')->name('projects.edit');
        Route::post('/projects/{project}/archive', fn () => 'archived')->name('projects.archive');
        Route::get('/projects/{project}/tasks/{task}', fn () => 'task')->name('tasks.show');
        Route::post('/projects/{project}/tasks/{task}/archive', fn () => 'archived')->name('tasks.archive');
        Route::get('/projects/create', fn () => 'create')->name('projects.create');
    });

    app(SubjectRegistry::class)->register(
        Subject::make('project')->for(Project::class)
            ->label(fn (Project $project): string => (string) $project->getAttribute('name'))
            ->page('projects.show'),
        Subject::make('task')->for(Task::class)
            ->label(fn (Task $task): string => (string) $task->getAttribute('title'))
            ->page('tasks.show', fn (Task $task): array => ['project' => $task->project, 'task' => $task]),
    );
    app(PageRegistry::class)->register(
        Page::make('projects')->route('projects.index')->label('Projects')->children(
            Page::make('projects.show')->route('projects.show')->presents('project')->children(
                Page::make('projects.edit')->route('projects.edit')->label('Edit'),
                Page::make('tasks.show')->route('tasks.show')->presents('task'),
            ),
        ),
        Page::make('projects.create')->route('projects.create')->label('New project'),
    );
});

test('subject actions resolve in order with their URL, method, and presentation', function () {
    app(ActionRegistry::class)->register(
        Action::make('archive')->for('project')->label('Archive')->order(2)
            ->route('projects.archive', 'post')->icon('archive')->destructive()
            ->confirm(fn (Project $project): string => 'Archive '.$project->getAttribute('name').'?'),
        Action::make('edit')->for('project')->label(fn (Project $project, Context $context): string => 'Edit '.$project->getAttribute('name'))
            ->order(1)->route('projects.edit'),
    );

    $actions = app(Actions::class)->for(apolloProject());

    expect(json_decode(json_encode($actions, JSON_THROW_ON_ERROR), true))->toBe([
        ['id' => 'project.edit', 'name' => 'edit', 'label' => 'Edit Apollo', 'url' => 'http://localhost/projects/42/edit', 'method' => 'GET',
            'icon' => null, 'destructive' => false, 'enabled' => true, 'reason' => null, 'confirm' => null, 'current' => false],
        ['id' => 'project.archive', 'name' => 'archive', 'label' => 'Archive', 'url' => 'http://localhost/projects/42/archive', 'method' => 'POST',
            'icon' => 'archive', 'destructive' => true, 'enabled' => true, 'reason' => null, 'confirm' => 'Archive Apollo?', 'current' => false],
    ]);
});

test('mapped route parameters reach routes that do not name the kind', function () {
    app(ActionRegistry::class)->register(
        Action::make('archive')->for('task')->label('Archive')
            ->route('tasks.archive', 'POST', fn (Task $task): array => ['project' => $task->project, 'task' => $task, 'extra' => 'ignored']),
    );

    expect(app(Actions::class)->for(loginTask())[0]->url)->toBe('http://localhost/projects/42/tasks/7/archive');
});

test('the current subject’s actions come before the current page’s, on pages below the presenting page too', function () {
    app(ActionRegistry::class)->register(
        Action::make('edit')->for('project')->label('Edit')->route('projects.edit'),
        Action::make('help')->on('projects.edit')->label('Help')->route('projects.index'),
        Action::make('create')->on('projects')->label('New project')->route('projects.create'),
    );

    $this->get('/projects/42/edit')->assertOk();
    $this->assertActionHas('project.edit')->assertActionHas('projects.edit.help')->assertActionMissing('projects.create');
    expect(array_map(fn ($action) => $action->id, Current::actions()))->toBe(['project.edit', 'projects.edit.help'])
        ->and(Current::actions()[0]->current)->toBeTrue();

    $this->get('/projects/42')->assertOk();
    expect(Current::actions()[0]->current)->toBeFalse();

    $this->get('/projects')->assertOk();
    expect(array_map(fn ($action) => $action->id, Current::actions()))->toBe(['projects.create']);
});

test('nearest presenting page wins, and no page means no actions', function () {
    app(ActionRegistry::class)->register(
        Action::make('edit')->for('project')->label('Edit')->route('projects.edit'),
        Action::make('archive')->for('task')->label('Archive')->route('tasks.archive', 'POST', fn (Task $task): array => ['project' => $task->project, 'task' => $task]),
    );
    Route::get('/elsewhere', fn () => 'elsewhere');

    $this->get('/projects/42/tasks/7')->assertOk();
    expect(array_map(fn ($action) => $action->id, Current::actions()))->toBe(['task.archive']);

    $this->get('/elsewhere')->assertOk();
    expect(Current::actions())->toBe([]);
});

test('visibility hides, and enabled callbacks and abilities disable with reasons', function () {
    Gate::define('archive', fn (?GenericUser $user, Project $project): Response => $user === null
        ? Response::deny('Sign in to archive.')
        : Response::allow());
    $enabledCalls = 0;
    app(ActionRegistry::class)->register(
        Action::make('edit')->for('project')->label('Edit')->route('projects.edit')
            ->visibleWhen(fn (Project $project, Context $context): bool => $context->user !== null),
        Action::make('archive')->for('project')->label('Archive')->route('projects.archive', 'POST')->can('archive')
            ->enabledWhen(function () use (&$enabledCalls): bool {
                $enabledCalls++;

                return true;
            }),
        Action::make('lock')->for('project')->label('Lock')->route('projects.archive', 'POST')
            ->enabledWhen(fn (): bool => false),
        Action::make('freeze')->for('project')->label('Freeze')->route('projects.archive', 'POST')
            ->enabledWhen(fn (): Response => Response::deny('Frozen projects stay frozen.')),
    );

    $this->get('/projects/42')->assertOk();
    $this->assertActionMissing('project.edit')
        ->assertActionDisabled('project.archive', 'Sign in to archive.')
        ->assertActionDisabled('project.lock')
        ->assertActionDisabled('project.freeze', 'Frozen projects stay frozen.');
    expect($enabledCalls)->toBe(0);

    $this->app['router']->matched(function ($event) {
        $event->request->setUserResolver(fn () => new GenericUser(['id' => 'alice']));
    });
    $this->get('/projects/42')->assertOk();
    $this->assertActionHas('project.edit')->assertActionEnabled('project.archive');
    $enabledCalls = 0;
    Current::actions();
    expect($enabledCalls)->toBe(1);
});

test('guests are denied abilities that require a user, with no reason given', function () {
    Gate::define('archive', fn (GenericUser $user): bool => true);
    app(ActionRegistry::class)->register(Action::make('archive')->for('project')->label('Archive')->route('projects.archive', 'POST')->can('archive'));

    $this->get('/projects/42')->assertOk();
    $this->assertActionDisabled('project.archive');
    expect(Current::actions()[0]->reason)->toBeNull();
});

test('invalid declarations fail at registration', function (Action $action, string $message) {
    expect(fn () => app(ActionRegistry::class)->register($action))->toThrow(InvalidDefinition::class, $message);
})->with([
    'no attachment' => [Action::make('archive')->label('Archive')->route('projects.archive'), "Action 'archive' must use exactly one of for() or on()."],
    'both attachments' => [Action::make('archive')->for('project')->on('projects.show')->label('Archive')->route('projects.archive'), 'exactly one of for() or on()'],
    'no label' => [Action::make('archive')->for('project')->route('projects.archive'), "Action 'project.archive' must have a label()."],
    'no route' => [Action::make('archive')->for('project')->label('Archive'), "Action 'project.archive' must have a route()."],
    'bad method' => [Action::make('archive')->for('project')->label('Archive')->route('projects.archive', 'FETCH'), "unsupported method 'FETCH'"],
]);

test('IDs must be unique across kinds and pages', function () {
    app(PageRegistry::class)->register(Page::make('project')->label('A page named like a kind'));
    app(ActionRegistry::class)->register(Action::make('edit')->for('project')->label('Edit')->route('projects.edit'));

    expect(fn () => app(ActionRegistry::class)->register(Action::make('edit')->on('project')->label('Edit')->route('projects.edit')))
        ->toThrow(InvalidDefinition::class, "Action 'project.edit' is declared twice");
});

test('unknown kinds, pages, routes, methods, and parameters are reported', function () {
    app(ActionRegistry::class)->register(
        Action::make('archive')->for('project')->label('Archive')->route('projects.archive', 'GET'),
        Action::make('remove')->for('project')->label('Remove')->route('missing.route', 'DELETE'),
        Action::make('open')->for('task')->label('Open')->route('projects.show'),
    );

    $this->artisan('current:check')
        ->expectsOutputToContain("Action 'project.archive' uses GET, but route 'projects.archive' accepts POST.")
        ->expectsOutputToContain("Action 'project.remove' targets unknown route 'missing.route'.")
        ->expectsOutputToContain("Action 'task.open' targets route 'projects.show', which has no 'task' parameter")
        ->assertExitCode(1);

    expect(fn () => app(Actions::class)->for(apolloProject()))->toThrow(InvalidDefinition::class, "Action 'project.archive' uses GET");
});

test('unknown subject kinds and pages fail at finalization', function () {
    app(ActionRegistry::class)->register(Action::make('archive')->for('invoice')->label('Archive')->route('projects.archive', 'POST'));

    expect(fn () => app(ActionRegistry::class)->all())->toThrow(InvalidDefinition::class, "Action 'invoice.archive' is for unknown subject 'invoice'.");
});

test('actions cannot be registered once read', function () {
    app(ActionRegistry::class)->all();

    expect(fn () => app(ActionRegistry::class)->register(Action::make('a')->for('project')->label('A')->route('projects.edit')))
        ->toThrow(InvalidDefinition::class, 'after the action registry has been read');
});

test('failing or wrongly typed callbacks name the action', function (Action $action, string $message) {
    app(ActionRegistry::class)->register($action);

    expect(fn () => app(Actions::class)->for(apolloProject()))->toThrow(ResolutionFailed::class, $message);
})->with([
    'label throws' => [Action::make('a')->for('project')->label(fn (): string => throw new RuntimeException('boom'))->route('projects.edit'), "Could not resolve label for action 'project.a': boom"],
    'empty label' => [Action::make('a')->for('project')->label(fn (): string => '')->route('projects.edit'), "Label callback for action 'project.a' must return a non-empty string."],
    'visibility type' => [Action::make('a')->for('project')->label('A')->route('projects.edit')->visibleWhen(fn (): int => 1), "Visibility callback for action 'project.a' must return a bool."],
    'enabled type' => [Action::make('a')->for('project')->label('A')->route('projects.edit')->enabledWhen(fn (): string => 'yes'), "Enabled callback for action 'project.a' must return a bool or an Illuminate\\Auth\\Access\\Response."],
    'parameters type' => [Action::make('a')->for('project')->label('A')->route('projects.edit', 'GET', fn (): string => 'no'), "Route-parameter callback for action 'project.a' must return an array"],
]);

test('objects of no registered kind have no actions', function () {
    expect(fn () => app(Actions::class)->for(new stdClass))->toThrow(ResolutionFailed::class, 'No subject kind accepts stdClass.');
});

test('current:actions lists actions without running callbacks', function () {
    app(ActionRegistry::class)->register(
        Action::make('archive')->for('project')->label('Archive')->route('projects.archive', 'POST')->icon('archive')
            ->destructive()->confirm('Sure?')->can('archive')
            ->visibleWhen(fn (): bool => throw new LogicException('Commands must not run callbacks.')),
        Action::make('create')->on('projects')->label(fn (): string => throw new LogicException('No.'))->route('projects.create'),
    );

    $this->artisan('current:actions')
        ->expectsOutputToContain('subject project')
        ->expectsOutputToContain('archive  Archive · POST projects.archive · icon archive · can archive · visible when · destructive · confirm')
        ->expectsOutputToContain('page projects')
        ->expectsOutputToContain('create  (dynamic) · GET projects.create')
        ->assertExitCode(0);

    Artisan::call('current:actions --json');
    expect(json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR)[0])->toMatchArray([
        'id' => 'project.archive', 'subject' => 'project', 'page' => null, 'route' => 'projects.archive', 'method' => 'POST',
        'ability' => 'archive', 'visibility' => true, 'enabled' => false, 'destructive' => true, 'confirm' => true,
    ]);
    $this->artisan('current:check')->expectsOutputToContain('2 actions.')->assertExitCode(0);
});

test('action assertions explain failures', function () {
    app(ActionRegistry::class)->register(
        Action::make('edit')->for('project')->label('Edit')->route('projects.edit'),
        Action::make('archive')->for('project')->label('Archive')->route('projects.archive', 'POST')
            ->enabledWhen(fn (): Response => Response::deny('Paid projects stay.')),
    );

    $this->get('/projects/42')->assertOk();

    expect(fn () => $this->assertActionHas('project.delete'))
        ->toThrow(AssertionFailedError::class, "Expected action 'project.delete', but the actions were [project.edit, project.archive (disabled)].")
        ->and(fn () => $this->assertActionMissing('project.edit'))
        ->toThrow(AssertionFailedError::class, "Expected no action 'project.edit'")
        ->and(fn () => $this->assertActionEnabled('project.archive'))
        ->toThrow(AssertionFailedError::class, "Expected action 'project.archive' to be enabled, but it was disabled (Paid projects stay.).")
        ->and(fn () => $this->assertActionDisabled('project.edit'))
        ->toThrow(AssertionFailedError::class, "Expected action 'project.edit' to be disabled, but it was enabled.")
        ->and(fn () => $this->assertActionDisabled('project.archive', 'Other reason'))
        ->toThrow(AssertionFailedError::class, "Expected action 'project.archive' to be disabled because 'Other reason', but the reason was 'Paid projects stay.'.");
});
