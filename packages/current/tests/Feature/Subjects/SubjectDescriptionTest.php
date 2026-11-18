<?php

declare(strict_types=1);

use Current\Exceptions\ResolutionFailed;
use Current\Facades\Current;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Subjects\Subject;
use Current\Subjects\SubjectRegistry;
use Current\Subjects\Subjects;
use Current\Testing\InteractsWithCurrent;
use Current\Tests\Fixtures\Project;
use Current\Tests\Fixtures\Task;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

uses(InteractsWithCurrent::class);

function mission(): Project
{
    return new Project(['id' => 42, 'name' => 'Apollo']);
}

function missionTask(): Task
{
    return Task::in(mission(), ['id' => 7, 'title' => 'Fix login']);
}

beforeEach(function () {
    Route::bind('project', fn (string $id): Project => $id === '42' ? mission() : abort(404));
    Route::bind('item', fn (string $id): Task => $id === '7' ? missionTask() : abort(404));
    Route::middleware(SubstituteBindings::class)->group(function () {
        Route::get('/projects', fn () => 'index')->name('projects.index');
        Route::get('/projects/{project}', fn () => 'show')->name('projects.show');
        Route::get('/projects/{project}/overview', fn () => 'overview')->name('projects.overview');
        Route::get('/projects/{project}/edit', fn () => 'edit')->name('projects.edit');
        Route::get('/projects/{project}/tasks/{item}', fn () => 'task')->name('tasks.show');
    });

    app(SubjectRegistry::class)->register(
        Subject::make('project')->for(Project::class)
            ->label(fn (Project $project): string => (string) $project->getAttribute('name'))
            ->title(fn (Project $project): string => 'Project '.$project->getAttribute('name'))
            ->description(fn (Project $project): string => 'Everything about '.$project->getAttribute('name').'.')
            ->icon('folder')
            ->page('projects.show'),
        Subject::make('task')->for(Task::class)
            ->label(fn (Task $task): string => (string) $task->getAttribute('title'))
            ->page('tasks.show', fn (Task $task): array => ['project' => $task->project, 'item' => $task]),
    );
    app(PageRegistry::class)->register(
        Page::make('projects')->route('projects.index')->label('Projects')->icon('stack')->children(
            Page::make('projects.show')->route('projects.show')->presents('project')->children(
                Page::make('projects.overview')->route('projects.overview')->presents('project')->label('Overview'),
                Page::make('projects.edit')->route('projects.edit')->label('Edit'),
                Page::make('tasks.show')->route('tasks.show')->presents('task', parameter: 'item')
                    ->title('Task details')->icon('check'),
            ),
        ),
    );
});

test('a page that presents a subject takes its title and description; breadcrumbs keep the label', function () {
    $this->get('/projects/42')->assertOk();

    $this->assertTitle('Project Apollo')
        ->assertDescription('Everything about Apollo.')
        ->assertBreadcrumbs(['Projects', 'Apollo']);
});

test('the subject title beats an explicit page label, and the page title beats both', function () {
    $this->get('/projects/42/overview')->assertOk();
    $this->assertTitle('Project Apollo')->assertBreadcrumbs(['Projects', 'Apollo', 'Overview']);

    $this->get('/projects/42/tasks/7')->assertOk();
    $this->assertTitle('Task details')->assertDescription(null);
});

test('a page below a subject keeps its own label as its title', function () {
    $this->get('/projects/42/edit')->assertOk();

    $this->assertTitle('Edit')->assertDescription(null)->assertCurrentSubject('project', '42');
});

test('any object resolves its title and description; subject output is unchanged', function () {
    $subjects = app(Subjects::class);

    expect($subjects->title(mission()))->toBe('Project Apollo')
        ->and($subjects->description(mission()))->toBe('Everything about Apollo.')
        ->and($subjects->title(missionTask()))->toBe('Fix login')
        ->and($subjects->description(missionTask()))->toBeNull()
        ->and(json_decode(json_encode($subjects->of(mission()), JSON_THROW_ON_ERROR), true))->toBe([
            'kind' => 'project',
            'id' => '42',
            'label' => 'Apollo',
            'url' => 'http://localhost/projects/42',
        ]);
});

test('pages take the presented kind icon unless they have their own', function () {
    $this->get('/projects/42/tasks/7')->assertOk();

    expect(array_map(fn ($crumb) => $crumb->icon, Current::breadcrumbs()))->toBe(['stack', 'folder', 'check']);
});

test('invalid subject titles and descriptions fail with the subject named', function () {
    app()->forgetScopedInstances();
    $registry = new SubjectRegistry(app(PageRegistry::class));
    $registry->register(
        Subject::make('project')->for(Project::class)
            ->label(fn (): string => 'Apollo')
            ->title(fn (): string => '')
            ->description(fn (): int => 3),
        Subject::make('task')->for(Task::class)->label(fn (): string => 'Fix login'),
    );
    app()->instance(SubjectRegistry::class, $registry);

    expect(fn () => app(Subjects::class)->title(mission()))
        ->toThrow(ResolutionFailed::class, "Title callback for subject 'project' must return a non-empty string.")
        ->and(fn () => app(Subjects::class)->description(mission()))
        ->toThrow(ResolutionFailed::class, "Description callback for subject 'project' must return a non-empty string or null.");
});

test('current:subjects shows which kinds have titles, descriptions, and icons', function () {
    $this->artisan('current:subjects')
        ->expectsOutputToContain('project  Current\Tests\Fixtures\Project · page projects.show · title · description · icon folder · presented by projects.show, projects.overview')
        ->assertExitCode(0);

    Artisan::call('current:subjects', ['--json' => true]);
    expect(json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR)[0])->toMatchArray([
        'name' => 'project',
        'title' => true,
        'description' => true,
        'icon' => 'folder',
    ]);
});
