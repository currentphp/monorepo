<?php

declare(strict_types=1);

use Current\Inertia\CurrentProps;
use Current\Inertia\Tests\Fixtures\DeclaredTypes;
use Current\Inertia\Tests\Fixtures\Project;
use Current\Navigation\MenuEntry;
use Current\Navigation\MenuRegistry;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Subjects\Subject;
use Current\Subjects\SubjectRegistry;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use PHPUnit\Framework\AssertionFailedError;

function declaredTypes(): DeclaredTypes
{
    return new DeclaredTypes(dirname(__DIR__, 2).'/resources/types/current.d.ts');
}

test('the concrete declaration shapes are inventoried', function () {
    expect(declaredTypes()->names())->toEqualCanonicalizing(['Breadcrumb', 'MenuItem', 'MenuItemKind', 'Subject', 'BaseCurrent', 'CurrentFeature', 'PagePresentation', 'SubjectPresentation', 'Action']);
});

test('the shared prop matches the TypeScript types', function () {
    Route::get('/docs', fn () => Inertia::render('Docs'))->name('docs');
    Route::get('/docs/install', fn () => Inertia::render('Install'))->name('docs.install');

    // Cover every nullable field both ways: icons set and unset, a route-less
    // page (null URL), and a group (null page ID and URL).
    app(PageRegistry::class)->register(
        Page::make('guides')->label('Guides')->children(
            Page::make('docs')->route('docs')->label('Docs')->icon('book')->children(
                Page::make('docs.install')->route('docs.install')->label('Install'),
            ),
        ),
    );
    app(MenuRegistry::class)->derive('primary', 'guides');
    app(MenuRegistry::class)->define('footer',
        MenuEntry::group('help')->label('Help')->icon('lifebuoy')->children(MenuEntry::page('docs.install')),
    );
    Inertia::share('current', CurrentProps::make()->menus('primary', 'footer'));

    $prop = $this->getJson('/docs/install', ['X-Inertia' => 'true'])->assertOk()->json('props.current');

    expect($prop['breadcrumbs'][0]['url'])->toBeNull()
        ->and($prop['breadcrumbs'][1]['icon'])->toBe('book')
        ->and($prop['menus']['footer'][0]['kind'])->toBe('group');
    declaredTypes()->assertMatches('BaseCurrent', $prop, 'current');
});

test('a prop without menus and outside any page still matches', function () {
    Route::get('/elsewhere', fn () => Inertia::render('Elsewhere'));
    Inertia::share('current', CurrentProps::make());

    $content = $this->getJson('/elsewhere', ['X-Inertia' => 'true'])->assertOk()->getContent();
    $prop = json_decode($content, true, flags: JSON_THROW_ON_ERROR)['props']['current'];

    // json_decode turns the empty `{}` record into an empty array.
    expect($content)->toContain('"menus":{}');
    declaredTypes()->assertMatches('BaseCurrent', $prop, 'current');
});

test('drift is reported with the path and the declared type', function (string $type, mixed $value, string $message) {
    expect(fn () => declaredTypes()->assertMatches($type, $value, 'value'))
        ->toThrow(AssertionFailedError::class, $message);
})->with([
    'missing field' => ['Breadcrumb', ['pageId' => 'a', 'label' => 'A', 'url' => null, 'current' => true], 'value has different fields from `Breadcrumb`'],
    'extra field' => ['Breadcrumb', ['pageId' => 'a', 'label' => 'A', 'url' => null, 'current' => true, 'icon' => null, 'title' => 'A'], 'value has different fields from `Breadcrumb`'],
    'wrong kind' => ['MenuItemKind', 'link', 'value does not match `MenuItemKind`: "link"'],
    'wrong scalar' => ['Breadcrumb', ['pageId' => 'a', 'label' => 'A', 'url' => null, 'current' => 'yes', 'icon' => null], 'value.current does not match `boolean`'],
]);

test('a shared subject matches the TypeScript types', function () {
    Route::bind('project', fn (string $id): Project => new Project($id, 'Apollo'));
    Route::get('/projects/{project}', fn () => Inertia::render('Project'))
        ->middleware(SubstituteBindings::class)->name('projects.show');
    app(SubjectRegistry::class)->register(
        Subject::make('project')->for(Project::class)->label(fn (Project $project): string => $project->name)->page('projects.show'),
    );
    app(PageRegistry::class)->register(Page::make('projects.show')->route('projects.show')->presents('project'));
    Inertia::share('current', CurrentProps::make());

    $prop = $this->getJson('/projects/42', ['X-Inertia' => 'true'])->assertOk()->json('props.current');

    expect($prop['subject'])->toBe(['kind' => 'project', 'id' => '42', 'label' => 'Apollo', 'url' => 'http://localhost/projects/42'])
        ->and($prop['breadcrumbs'][0]['label'])->toBe('Apollo');
    declaredTypes()->assertMatches('BaseCurrent', $prop, 'current');
});
