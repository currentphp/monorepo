<?php

declare(strict_types=1);

use Current\Actions\Action;
use Current\Actions\ActionRegistry;
use Current\Context;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Experience;
use Current\Facades\Current;
use Current\Navigation\MenuEntry;
use Current\Navigation\MenuRegistry;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Subjects\Subject;
use Current\Subjects\SubjectRegistry;
use Current\Tests\Fixtures\Project;
use Current\Tests\Fixtures\Task;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\GenericUser;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

function experienceProject(int $id = 42): Project
{
    return new Project(['id' => $id, 'name' => $id === 42 ? 'Apollo' : 'Artemis', 'summary' => 'Subject description']);
}

function experienceData(Experience $reader): array
{
    return json_decode(json_encode([
        'pageId' => $reader->pageId(),
        'title' => $reader->title(),
        'description' => $reader->description(),
        'subject' => $reader->subject(),
        'subjectTitle' => $reader->subjectTitle(),
        'subjectDescription' => $reader->subjectDescription(),
        'breadcrumbs' => $reader->breadcrumbs(),
        'menu' => $reader->menu('primary'),
        'footer' => $reader->menu('footer'),
        'actions' => $reader->actions(),
        'user' => $reader->user()?->getAuthIdentifier(),
    ], JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
}

beforeEach(function () {
    Route::bind('project', fn (string $id): Project => experienceProject((int) $id));
    Route::middleware(SubstituteBindings::class)->group(function () {
        Route::get('/projects', fn () => 'index')->name('projects.index');
        Route::get('/projects/{project}', fn () => 'show')->name('projects.show');
        Route::get('/projects/{project}/edit', fn () => 'edit')->name('projects.edit');
        Route::post('/projects/{project}/archive', fn () => 'archive')->name('projects.archive');
        Route::get('/projects/{project}/tasks/{task}', fn () => 'task')->name('tasks.show');
    });
    Route::post('/component-update', fn () => 'update')->name('component.update');
    Gate::define('archive', fn (?GenericUser $user, Project $project): Response => $user === null
        ? Response::deny('Sign in.') : Response::allow());
    app(SubjectRegistry::class)->register(
        Subject::make('project')->for(Project::class)
            ->label(fn (Project $project): string => (string) $project->getAttribute('name'))
            ->title(fn (Project $project): string => 'Project '.$project->getAttribute('name'))
            ->description(fn (Project $project): ?string => $project->getAttribute('summary'))
            ->page('projects.show'),
        Subject::make('task')->for(Task::class)->label(fn (Task $task): string => (string) $task->getAttribute('title')),
    );
    app(PageRegistry::class)->register(
        Page::make('projects')->route('projects.index')->label('Projects')->children(
            Page::make('projects.show')->route('projects.show')->presents('project')->children(
                Page::make('projects.edit')->route('projects.edit')->label('Edit')->title('Edit project')->description('Page description'),
                Page::make('tasks.show')->route('tasks.show')->presents('task')->title('Task page')->description('Task page description'),
            ),
        ),
        Page::make('structural')->label('Structural page'),
    );
    app(MenuRegistry::class)->derive('primary', 'projects')->except('tasks.show');
    app(MenuRegistry::class)->define('footer', MenuEntry::page('projects.show')->visibleWhen(
        fn (Context $context): bool => $context->user !== null && $context->page?->id === 'projects.edit',
    ));
    app(ActionRegistry::class)->register(
        Action::make('edit')->for('project')->label('Edit')->route('projects.edit')->order(1),
        Action::make('archive')->for('project')->label('Archive')->route('projects.archive', 'POST')->can('archive')->order(2),
        Action::make('other')->for('project')->label('Other project')->route('projects.edit', 'GET', fn (): array => ['project' => experienceProject(77)])->order(3),
        Action::make('hidden')->for('project')->label('Hidden')->route('projects.show')->visibleWhen(fn (): bool => false),
        Action::make('help')->on('projects.edit')->label(fn (Context $context): string => $context->parameter('project')->getAttribute('name').' help')
            ->route('projects.index')->visibleWhen(fn (Context $context): bool => $context->page?->id === 'projects.edit'),
    );
});

test('ambient and explicit readers produce the same experience on the selected route', function () {
    $this->app['router']->matched(fn ($event) => $event->request->setUserResolver(fn () => new GenericUser(['id' => 'alice'])));
    $this->get('/projects/42/edit')->assertOk();
    $explicit = Current::forPage('projects.edit', Current::context()->parameters());

    expect(experienceData($explicit))->toBe(experienceData(Current::getFacadeRoot()))
        ->and($explicit->subjectTitle())->toBe('Project Apollo')
        ->and($explicit->subjectDescription())->toBe('Subject description')
        ->and($explicit->title())->toBe('Edit project')
        ->and($explicit->description())->toBe('Page description');
});

test('multiple explicit readers leave ambient transport and each other unchanged', function () {
    $this->post('/component-update')->assertOk();
    $parameters = ['project' => experienceProject()];
    $first = Current::forPage('projects.edit', $parameters);
    $parameters['project'] = experienceProject(77);
    $second = $first->forPage('projects.show', $parameters);

    expect($first->page()?->id)->toBe('projects.edit')
        ->and($first->is('projects.*'))->toBeTrue()
        ->and($first->subject()?->key())->toBe('project:42')
        ->and($second->subject()?->key())->toBe('project:77')
        ->and($first->context()->parameter('project')->getKey())->toBe(42)
        ->and(Current::pageId())->toBeNull()
        ->and(Current::subjectTitle())->toBeNull()
        ->and(Current::actions())->toBe([])
        ->and(request()->route()->getName())->toBe('component.update');
});

test('explicit navigation and actions use the logical page and its bound values on another endpoint', function () {
    $this->app['router']->matched(fn ($event) => $event->request->setUserResolver(fn () => new GenericUser(['id' => 'alice'])));
    $this->post('/component-update')->assertOk();
    $reader = Current::forPage('projects.edit', ['project' => experienceProject(), 'user' => new GenericUser(['id' => 'forged'])]);
    $data = experienceData($reader);

    expect($reader->user()?->getAuthIdentifier())->toBe('alice')
        ->and($data['breadcrumbs'][1]['label'])->toBe('Apollo')
        ->and($data['breadcrumbs'][2]['url'])->toBe('http://localhost/projects/42/edit')
        ->and($data['menu'][0]['active'])->toBeTrue()
        ->and($data['menu'][0]['children'][0]['children'][0]['current'])->toBeTrue()
        ->and($data['footer'][0]['pageId'])->toBe('projects.show')
        ->and(array_column($data['actions'], 'id'))->toBe(['project.edit', 'project.archive', 'project.other', 'projects.edit.help'])
        ->and($data['actions'][0]['current'])->toBeTrue()
        ->and($data['actions'][1]['current'])->toBeFalse()
        ->and($data['actions'][1]['enabled'])->toBeTrue()
        ->and($data['actions'][2]['current'])->toBeFalse()
        ->and($data['actions'][3]['label'])->toBe('Apollo help');
});

test('an explicit reader works outside HTTP without inheriting an unrelated authenticated guard', function () {
    $this->actingAs(new GenericUser(['id' => 'alice']));
    $reader = Current::forPage('projects.edit', ['project' => experienceProject()]);
    $archive = $reader->actions()[1];

    expect($reader->user())->toBeNull()
        ->and($reader->subjectTitle())->toBe('Project Apollo')
        ->and($reader->menu('footer'))->toBe([])
        ->and($reader->actions()[0]->current)->toBeTrue()
        ->and($archive->enabled)->toBeFalse()
        ->and($archive->reason)->toBe('Sign in.')
        ->and(Current::pageId())->toBeNull();
});

test('route-less pages and pages without a subject have useful explicit output', function () {
    $reader = Current::forPage('structural');
    expect($reader->title())->toBe('Structural page')
        ->and($reader->subject())->toBeNull()
        ->and($reader->subjectTitle())->toBeNull()
        ->and($reader->subjectDescription())->toBeNull()
        ->and($reader->breadcrumbs()[0]->url)->toBeNull()
        ->and($reader->actions())->toBe([]);
});

test('factory checks page identity and parameter names eagerly', function (string $id, array $parameters, string $message) {
    expect(fn () => Current::forPage($id, $parameters))->toThrow(ResolutionFailed::class, $message);
})->with([
    'unknown page' => ['missing', [], "Unknown page 'missing'."],
    'positional binding' => ['projects.edit', [experienceProject()], 'route parameters must be keyed by non-empty names'],
    'blank binding' => ['projects.edit', [' ' => 42], 'route parameters must be keyed by non-empty names'],
]);

test('factory validates subject declarations before returning an explicit reader', function () {
    app(SubjectRegistry::class)->register(Subject::make('plain')->for(ArrayObject::class)->label(fn (): string => 'Plain'));
    expect(fn () => Current::forPage('projects.edit'))->toThrow(InvalidDefinition::class, 'must be routable');
});

test('factory validates named route references without resolving output', function () {
    app(PageRegistry::class)->register(Page::make('invalid')->route('not.registered'));
    expect(fn () => Current::forPage('projects.edit'))->toThrow(InvalidDefinition::class, "Page 'invalid' references unknown route 'not.registered'.");
});

test('explicit callbacks do not temporarily replace the ambient experience', function () {
    app(PageRegistry::class)->register(Page::make('probe')->label(
        fn (Context $context): string => $context->page->id.':'.(Current::pageId() ?? 'no ambient page'),
    ));
    $this->post('/component-update')->assertOk();

    expect(Current::forPage('probe')->title())->toBe('probe:no ambient page')
        ->and(Current::pageId())->toBeNull();
});

test('explicit construction rejects an invalid server request user', function () {
    $this->app['router']->matched(fn ($event) => $event->request->setUserResolver(fn () => new stdClass));
    $this->post('/component-update')->assertOk();

    expect(fn () => Current::forPage('structural'))->toThrow(ResolutionFailed::class, 'The current request user must implement Authenticatable.');
});

test('bound values are checked only when a requested read needs them', function (mixed $value, string $message) {
    $reader = Current::forPage('projects.edit', $value === null ? [] : ['project' => $value]);
    expect($reader->title())->toBe('Edit project')
        ->and($reader->description())->toBe('Page description')
        ->and(fn () => $reader->subjectTitle())->toThrow(ResolutionFailed::class, $message)
        ->and(fn () => $reader->subjectDescription())->toThrow(ResolutionFailed::class, $message)
        ->and(fn () => $reader->actions())->toThrow(ResolutionFailed::class, $message);
})->with([
    'missing' => [null, 'is not a bound object'],
    'scalar' => ['42', 'is not a bound object'],
    'wrong object' => [new Task, 'must be an instance of '.Project::class],
]);

test('subject presentation readers do not resolve labels URLs descriptions or actions they did not request', function () {
    $unexpected = fn () => throw new LogicException('Unrequested output.');
    $registry = new SubjectRegistry(app(PageRegistry::class));
    $registry->register(
        Subject::make('project')->for(Project::class)->label($unexpected)->title(fn (): string => 'Subject title')
            ->description(fn (): string => 'Subject description')->page('projects.show', $unexpected),
        Subject::make('task')->for(Task::class)->label(fn (): string => 'Task'),
    );
    app()->instance(SubjectRegistry::class, $registry);
    app(ActionRegistry::class)->register(Action::make('unexpected')->for('project')->label($unexpected)->route('projects.show'));
    $reader = Current::forPage('projects.edit', ['project' => experienceProject()]);

    expect($reader->subjectTitle())->toBe('Subject title')
        ->and($reader->subjectDescription())->toBe('Subject description')
        ->and($reader->title())->toBe('Edit project')
        ->and(fn () => $reader->subject())->toThrow(ResolutionFailed::class, 'Unrequested output.');
});

test('nearest subject selection and title fallback match the ambient rules', function () {
    $task = Task::in(experienceProject(), ['id' => 7, 'title' => 'Fix login']);
    $reader = Current::forPage('tasks.show', ['project' => $task->project, 'task' => $task]);
    expect($reader->title())->toBe('Task page')
        ->and($reader->description())->toBe('Task page description')
        ->and($reader->subjectTitle())->toBe('Fix login')
        ->and($reader->subjectDescription())->toBeNull()
        ->and($reader->subject()?->key())->toBe('task:7');
});

test('a focused subject read skips the other presentation callback', function (string $method, string $expected) {
    $unexpected = fn () => throw new LogicException('Unrequested fact.');
    $registry = new SubjectRegistry(app(PageRegistry::class));
    $registry->register(
        Subject::make('project')->for(Project::class)->label($unexpected)->page('projects.show', $unexpected)
            ->title($method === 'subjectTitle' ? fn (): string => 'Only title' : $unexpected)
            ->description($method === 'subjectDescription' ? fn (): string => 'Only description' : $unexpected),
        Subject::make('task')->for(Task::class)->label(fn (): string => 'Task'),
    );
    app()->instance(SubjectRegistry::class, $registry);
    $this->get('/projects/42/edit')->assertOk();
    $reader = Current::forPage('projects.edit', Current::context()->parameters());

    expect($reader->{$method}())->toBe($expected)
        ->and(Current::{$method}())->toBe($expected);
})->with([
    'title' => ['subjectTitle', 'Only title'],
    'description' => ['subjectDescription', 'Only description'],
]);

test('logical GET current-state respects the selected page parameter mapping', function () {
    Route::get('/mapped/{project}', fn () => 'mapped')->name('mapped');
    app(PageRegistry::class)->register(
        Page::make('mapped')->route('mapped')->routeParameters(fn (): array => ['project' => experienceProject(77)]),
    );
    app(ActionRegistry::class)->register(Action::make('view')->on('mapped')->label('View')->route('mapped'));

    expect(Current::forPage('mapped', ['project' => experienceProject()])->actions()[0]->current)->toBeFalse()
        ->and(Current::forPage('mapped', ['project' => experienceProject(77)])->actions()[0]->current)->toBeTrue();
});
