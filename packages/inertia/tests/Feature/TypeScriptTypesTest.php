<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use PHPUnit\Framework\AssertionFailedError;
use WavyKit\Inertia\Tests\Fixtures\DeclaredTypes;
use WavyKit\Inertia\WavyKitProps;
use WavyKit\Navigation\MenuEntry;
use WavyKit\Navigation\MenuRegistry;
use WavyKit\Pages\Page;
use WavyKit\Pages\PageRegistry;

function declaredTypes(): DeclaredTypes
{
    return new DeclaredTypes(dirname(__DIR__, 2).'/resources/types/wavykit.d.ts');
}

test('the declaration file declares the shared types', function () {
    expect(declaredTypes()->names())->toEqualCanonicalizing(['Breadcrumb', 'MenuItem', 'MenuItemKind', 'WavyKit']);
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
    Inertia::share('wavykit', WavyKitProps::make()->menus('primary', 'footer'));

    $prop = $this->getJson('/docs/install', ['X-Inertia' => 'true'])->assertOk()->json('props.wavykit');

    expect($prop['breadcrumbs'][0]['url'])->toBeNull()
        ->and($prop['breadcrumbs'][1]['icon'])->toBe('book')
        ->and($prop['menus']['footer'][0]['kind'])->toBe('group');
    declaredTypes()->assertMatches('WavyKit', $prop, 'wavykit');
});

test('a prop without menus and outside any page still matches', function () {
    Route::get('/elsewhere', fn () => Inertia::render('Elsewhere'));
    Inertia::share('wavykit', WavyKitProps::make());

    $content = $this->getJson('/elsewhere', ['X-Inertia' => 'true'])->assertOk()->getContent();
    $prop = json_decode($content, true, flags: JSON_THROW_ON_ERROR)['props']['wavykit'];

    // json_decode turns the empty `{}` record into an empty array.
    expect($content)->toContain('"menus":{}');
    declaredTypes()->assertMatches('WavyKit', $prop, 'wavykit');
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
