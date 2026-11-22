<?php

declare(strict_types=1);

use Current\Actions\Action;
use Current\Actions\ActionRegistry;
use Current\Actions\Actions;
use Current\Context;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Facades\Current;
use Current\Navigation\MenuRegistry;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Pages\Pages;
use Current\Subjects\Subject;
use Current\Subjects\SubjectRegistry;
use Current\Subjects\Subjects;
use Current\Tests\Fixtures\Project;
use Current\Tests\Fixtures\Task;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::bind('project', fn (): Project => new Project(['id' => 42, 'name' => 'Apollo']));
    Route::middleware(SubstituteBindings::class)->group(function () {
        Route::get('/projects/{project}', fn () => 'show')->name('projects.show');
        Route::get('/projects/{project}/edit', fn () => 'edit')->name('projects.edit');
    });
    app(SubjectRegistry::class)->register(
        // Broad callbacks deliberately cannot mask a wrong binding with a TypeError.
        Subject::make('project')->for(Project::class)->label(fn (object $object): string => 'Apollo')
            ->title(fn (object $object): string => 'Project Apollo')
            ->description(fn (object $object): string => 'A project'),
        Subject::make('task')->for(Task::class)->label(fn (object $object): string => 'A task'),
    );
    app(PageRegistry::class)->register(
        Page::make('project')->route('projects.show')->presents('project')->children(
            Page::make('project.edit')->route('projects.edit')->label('Edit')->title('Edit project'),
        ),
    );
    app(MenuRegistry::class)->derive('primary', 'project');
    app(ActionRegistry::class)->register(
        Action::make('edit')->for('project')->label('Edit')->route('projects.edit'),
    );
});

test('every subject consumer rejects a bound object of the wrong registered class', function (Closure $read) {
    Route::bind('project', fn (): Task => new Task(['id' => 7]));
    $this->get('/projects/7')->assertOk();

    expect(fn () => $read())->toThrow(ResolutionFailed::class,
        "Page 'project' presents subject 'project', but route parameter 'project' must be an instance of ".Project::class.'; got '.Task::class.'.');
})->with([
    'subject' => [fn () => Current::subject()],
    'title' => [fn () => Current::title()],
    'description' => [fn () => Current::description()],
    'breadcrumbs' => [fn () => Current::breadcrumbs()],
    'menu' => [fn () => Current::menu('primary')],
    'actions' => [fn () => Current::actions()],
]);

test('declaration validity does not depend on the reader used', function (Closure $read) {
    app(SubjectRegistry::class)->register(
        Subject::make('plain')->for(ArrayObject::class)->label(fn (): string => 'Plain'),
    );
    $this->get('/projects/42')->assertOk();

    expect(fn () => $read())->toThrow(InvalidDefinition::class,
        "Subject 'plain' class ArrayObject must be routable (implement UrlRoutable) to have an identity.");
})->with([
    'page identity' => [fn () => Current::pageId()],
    'page title' => [fn () => Current::title()],
    'page description' => [fn () => Current::description()],
    'subject' => [fn () => Current::subject()],
    'object title' => [fn () => app(Subjects::class)->title(new Project)],
    'object actions' => [fn () => app(Actions::class)->for(new Project)],
    'explicit page title' => [fn () => app(Pages::class)->title(app(PageRegistry::class)->page('project.edit'), new Context(null, []))],
]);

test('page overrides and ancestor subject selection remain independent', function () {
    $this->get('/projects/42/edit')->assertOk();

    expect(Current::title())->toBe('Edit project')
        ->and(Current::description())->toBeNull()
        ->and(Current::subject()?->key())->toBe('project:42')
        ->and(array_map(fn ($item) => $item->id, Current::actions()))->toBe(['project.edit'])
        ->and(array_map(fn ($item) => $item->label, Current::breadcrumbs()))->toBe(['Apollo', 'Edit']);
});

test('presenting route parameter declarations are checked before presentation callbacks', function (Closure $read) {
    Route::get('/missing/{other}', fn () => 'missing')->name('missing');
    app(PageRegistry::class)->register(Page::make('missing')->route('missing')->presents('project'));
    $this->get('/projects/42')->assertOk();

    expect(fn () => $read())->toThrow(InvalidDefinition::class,
        "Page 'missing' presents subject 'project', but its route has no 'project' parameter.");
})->with([
    'page' => [fn () => Current::title()],
    'subject' => [fn () => app(Subjects::class)->title(new Project)],
    'action' => [fn () => app(Actions::class)->for(new Project)],
]);

test('a wrong binding is rejected before subject and action callbacks run', function () {
    $unexpected = fn () => throw new LogicException('An unrequested callback ran.');
    $registry = new SubjectRegistry(app(PageRegistry::class));
    $registry->register(Subject::make('project')->for(Project::class)->label($unexpected));
    app()->instance(SubjectRegistry::class, $registry);
    app(ActionRegistry::class)->register(
        Action::make('unexpected')->for('project')->label($unexpected)->visibleWhen($unexpected)->route('projects.show'),
    );
    Route::bind('project', fn (): Task => new Task(['id' => 7]));
    $this->get('/projects/7')->assertOk();

    expect(fn () => Current::subject())->toThrow(ResolutionFailed::class, 'must be an instance of '.Project::class)
        ->and(fn () => Current::actions())->toThrow(ResolutionFailed::class, 'must be an instance of '.Project::class);
});

test('presentation readers do not assemble unrelated subject or action output', function () {
    $registry = new SubjectRegistry(app(PageRegistry::class));
    $unexpected = fn () => throw new LogicException('An unrequested callback ran.');
    $registry->register(
        Subject::make('project')->for(Project::class)->label($unexpected)
            ->title(fn (): string => 'Project Apollo')
            ->description($unexpected)->page('project', $unexpected),
    );
    app()->instance(SubjectRegistry::class, $registry);
    app(ActionRegistry::class)->register(
        Action::make('unexpected')->for('project')->label($unexpected)->route('projects.show'),
    );
    $this->get('/projects/42')->assertOk();

    expect(Current::title())->toBe('Project Apollo')
        ->and(app(Subjects::class)->title(new Project))->toBe('Project Apollo');

    $this->get('/projects/42/edit')->assertOk();
    expect(Current::title())->toBe('Edit project')
        ->and(Current::description())->toBeNull();
});

test('destination failures identify their originating page subject or action', function (Closure $read, string $owner) {
    Route::get('/required/{project}/{missing}', fn () => 'required')->name('required');
    app(PageRegistry::class)->register(Page::make('required')->route('required')->presents('project'));
    $registry = new SubjectRegistry(app(PageRegistry::class));
    $registry->register(Subject::make('project')->for(Project::class)->label(fn (): string => 'Apollo')->page('required'));
    app()->instance(SubjectRegistry::class, $registry);
    app(ActionRegistry::class)->register(Action::make('required')->for('project')->label('Required')->route('required'));
    $this->get('/projects/42')->assertOk();

    expect(fn () => $read())->toThrow(ResolutionFailed::class, "Could not generate URL for {$owner} and route 'required':");
})->with([
    'page' => [fn () => app(Pages::class)->url(app(PageRegistry::class)->page('required'), Current::context()), "page 'required'"],
    'subject' => [fn () => Current::subject(), "subject 'project' on page 'required'"],
    'action' => [fn () => Current::actions(), "action 'project.required'"],
]);
