<?php

declare(strict_types=1);

use Current\Actions\Action;
use Current\Actions\ActionRegistry;
use Current\Context;
use Current\Exceptions\ResolutionFailed;
use Current\Inertia\CurrentProps;
use Current\Inertia\Tests\Fixtures\DeclaredTypes;
use Current\Inertia\Tests\Fixtures\Project;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Subjects\Subject;
use Current\Subjects\SubjectRegistry;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

beforeEach(function () {
    $this->calls = array_fill_keys(['pageTitle', 'pageDescription', 'subjectTitle', 'subjectDescription', 'actions'], 0);
    $this->failing = null;
    $this->fact = function (string $name, ?string $value): ?string {
        $this->calls[$name]++;
        if ($this->failing === $name) {
            throw new LogicException('Broken '.$name);
        }

        return $value;
    };
    Route::bind('project', fn (string $id): Project => new Project($id, $id === '42' ? 'Apollo' : 'Artemis'));
    Route::middleware(SubstituteBindings::class)->group(function () {
        Route::get('/opt/projects/{project}', fn () => Inertia::render('Project', ['other' => 'value']))->name('opt.show');
        Route::get('/opt/projects/{project}/edit', fn () => Inertia::render('Project', ['other' => 'value']))->name('opt.edit');
        Route::post('/opt/projects/{project}/archive', fn () => 'archive')->name('opt.archive');
    });
    Route::get('/opt/unregistered', fn () => Inertia::render('Project'));
    app(SubjectRegistry::class)->register(Subject::make('project')->for(Project::class)
        ->label(fn (Project $project): string => $project->name)
        ->title(fn (Project $project): ?string => ($this->fact)('subjectTitle', 'Project '.$project->name))
        ->description(fn (Project $project): ?string => ($this->fact)('subjectDescription', $project->id === '42' ? 'Subject description' : null))
        ->page('opt.show'));
    app(PageRegistry::class)->register(Page::make('opt.show')->route('opt.show')->presents('project')->children(
        Page::make('opt.edit')->route('opt.edit')->label('Edit')
            ->title(fn (): ?string => ($this->fact)('pageTitle', 'Edit project'))
            ->description(fn (): ?string => ($this->fact)('pageDescription', 'Page description')),
    ));
    Gate::define('archive', fn (?Authenticatable $user, Project $project): Response => Response::deny('Protected project.'));
    app(ActionRegistry::class)->register(
        Action::make('edit')->for('project')->label(fn (): ?string => ($this->fact)('actions', 'Edit'))->route('opt.edit'),
        Action::make('archive')->for('project')->label('Archive')->route('opt.archive', 'POST')->can('archive'),
        Action::make('hidden')->for('project')->label(fn () => throw new LogicException('Hidden label ran.'))->route('opt.show')->visibleWhen(fn (): bool => false),
        Action::make('help')->on('opt.edit')->label(fn (Context $context): string => 'Help '.$context->parameter('project')->name)->route('opt.show'),
    );
});

test('all independent opt-in combinations have exact serialized shapes and matching compiler fixtures', function (int $mask) {
    $prop = CurrentProps::make();
    $features = [];
    foreach (['pagePresentation', 'subjectPresentation', 'actions'] as $bit => $feature) {
        if ($mask & (1 << $bit)) {
            $prop->{$feature}();
            $features[] = $feature;
        }
    }
    Inertia::share('current', $prop);
    $data = $this->getJson('/opt/projects/42/edit', ['X-Inertia' => 'true'])->assertOk()->json('props.current');
    $keys = ['pageId', 'subject', 'breadcrumbs', 'menus'];
    if ($mask & 1) {
        $keys = [...$keys, 'title', 'description'];
        expect($data['title'])->toBe('Edit project')->and($data['description'])->toBe('Page description');
    }
    expect(array_keys($data['subject']))->toBe($mask & 2 ? ['kind', 'id', 'label', 'url', 'title', 'description'] : ['kind', 'id', 'label', 'url']);
    if ($mask & 2) {
        expect($data['subject']['title'])->toBe('Project Apollo')->and($data['subject']['description'])->toBe('Subject description');
    }
    if ($mask & 4) {
        $keys[] = 'actions';
        expect(array_column($data['actions'], 'id'))->toBe(['project.edit', 'project.archive', 'opt.edit.help'])
            ->and($data['actions'][0]['current'])->toBeTrue()
            ->and($data['actions'][1]['enabled'])->toBeFalse()
            ->and($data['actions'][1]['reason'])->toBe('Protected project.')
            ->and($data['actions'][1]['icon'])->toBeNull()
            ->and($data['actions'][1]['confirm'])->toBeNull();
        $types = new DeclaredTypes(dirname(__DIR__, 2).'/resources/types/current.d.ts');
        foreach ($data['actions'] as $action) {
            $types->assertMatches('Action', $action, 'action');
        }
    }
    expect(array_keys($data))->toEqualCanonicalizing($keys)
        ->and($this->calls)->toBe([
            'pageTitle' => $mask & 1 ? 1 : 0, 'pageDescription' => $mask & 1 ? 1 : 0,
            'subjectTitle' => $mask & 2 ? 1 : 0, 'subjectDescription' => $mask & 2 ? 1 : 0,
            'actions' => $mask & 4 ? 1 : 0,
        ]);
    $missing = $this->getJson('/opt/unregistered', ['X-Inertia' => 'true'])->assertOk()->json('props.current');
    expect($missing['subject'])->toBeNull()->and($missing['breadcrumbs'])->toBe([]);
    if ($mask & 1) {
        expect($missing['title'])->toBeNull()->and($missing['description'])->toBeNull();
    }
    if ($mask & 4) {
        expect($missing['actions'])->toBe([]);
    }
    $directory = dirname(__DIR__).'/types/generated';
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    $type = 'Current<string, '.($features === [] ? 'never' : implode(' | ', array_map(fn (string $feature): string => "'{$feature}'", $features))).'>';
    // Use actual JSON, preserving `{}` menus, as contextually typed TS literals.
    $json = json_encode($data, JSON_THROW_ON_ERROR);
    $emptyJson = json_encode($missing, JSON_THROW_ON_ERROR);
    $json = str_replace('"menus":[]', '"menus":{}', $json);
    $emptyJson = str_replace('"menus":[]', '"menus":{}', $emptyJson);
    file_put_contents($directory.'/case-'.$mask.'.ts', "import type { Current } from '../../../resources/types/current';\nexport const selected: {$type} = {$json};\nexport const absent: {$type} = {$emptyJson};\n");
})->with(range(0, 7));

test('an excluded partial reload or initial deferred prop evaluates no new facts', function (bool $deferred) {
    $make = fn () => CurrentProps::make()->pagePresentation()->subjectPresentation()->actions();
    Inertia::share('experience', $deferred ? Inertia::defer($make) : $make());
    $headers = ['X-Inertia' => 'true'];
    if (! $deferred) {
        $headers += ['X-Inertia-Partial-Component' => 'Project', 'X-Inertia-Partial-Data' => 'other'];
    }
    $this->getJson('/opt/projects/42/edit', $headers)->assertOk()->assertJsonMissingPath('props.experience');
    expect(array_sum($this->calls))->toBe(0);
    $this->getJson('/opt/projects/42/edit', ['X-Inertia' => 'true', 'X-Inertia-Partial-Component' => 'Project', 'X-Inertia-Partial-Data' => 'experience'])
        ->assertOk()->assertJsonPath('props.experience.subject.title', 'Project Apollo')->assertJsonPath('props.experience.actions.1.reason', 'Protected project.');
})->with([false, true]);

test('unrequested failures stay unevaluated and requested failures surface', function (string $feature, string $fact) {
    $this->failing = $fact;
    Inertia::share('current', CurrentProps::make());
    $this->getJson('/opt/projects/42/edit', ['X-Inertia' => 'true'])->assertOk();
    Inertia::share('current', CurrentProps::make()->{$feature}());
    $this->withoutExceptionHandling();
    expect(fn () => $this->getJson('/opt/projects/42/edit', ['X-Inertia' => 'true']))->toThrow(ResolutionFailed::class, 'Broken '.$fact);
})->with([
    ['pagePresentation', 'pageTitle'], ['pagePresentation', 'pageDescription'],
    ['subjectPresentation', 'subjectTitle'], ['subjectPresentation', 'subjectDescription'], ['actions', 'actions'],
]);

test('selected subject presentation stays isolated across bound requests and can be nullable', function () {
    Inertia::share('current', CurrentProps::make()->subjectPresentation());
    $this->getJson('/opt/projects/42/edit', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.current.subject.title', 'Project Apollo');
    $this->getJson('/opt/projects/77/edit', ['X-Inertia' => 'true'])->assertOk()
        ->assertJsonPath('props.current.subject.title', 'Project Artemis')->assertJsonPath('props.current.subject.description', null);
});

test('all six core HTTP methods survive action serialization and compile as package Action types', function () {
    $methods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];
    Route::match($methods, '/opt/commands/{project}', fn () => 'command')->name('opt.commands');
    foreach ($methods as $method) {
        app(ActionRegistry::class)->register(Action::make('http-'.strtolower($method))->on('opt.edit')->label($method)->route('opt.commands', $method));
    }
    Inertia::share('current', CurrentProps::make()->actions());
    $data = $this->getJson('/opt/projects/42/edit', ['X-Inertia' => 'true'])->assertOk()->json('props.current.actions');
    $actions = array_values(array_filter($data, fn (array $action): bool => str_starts_with($action['id'], 'opt.edit.http-')));
    expect(array_column($actions, 'method'))->toBe($methods);
    $directory = dirname(__DIR__).'/types/generated';
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    file_put_contents($directory.'/methods.ts', "import type { Action } from '../../../resources/types/current';\nexport const actions: Action[] = ".json_encode($actions, JSON_THROW_ON_ERROR).";\n");
});
