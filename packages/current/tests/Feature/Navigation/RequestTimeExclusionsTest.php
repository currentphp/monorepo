<?php

declare(strict_types=1);

use Current\Context;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Facades\Current;
use Current\Navigation\MenuRegistry;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Testing\InteractsWithCurrent;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

uses(InteractsWithCurrent::class);

beforeEach(function () {
    Route::get('/dashboard', fn () => 'dashboard')->name('dashboard');
    Route::get('/billing', fn () => 'billing')->name('billing');
    Route::get('/billing/invoices', fn () => 'invoices')->name('billing.invoices');
    $this->app['router']->matched(function ($event) {
        $event->request->setUserResolver(fn () => $event->request->header('X-Owner') === 'yes'
            ? new GenericUser(['id' => 'alice', 'owner' => true])
            : null);
    });

    app(PageRegistry::class)->register(
        Page::make('dashboard')->label('Dashboard')->route('dashboard'),
        Page::make('billing')->label('Billing')->route('billing')->children(
            Page::make('billing.invoices')->label('Invoices')->route('billing.invoices'),
        ),
    );
});

function ownerOnly(Context $context): bool
{
    return ! ($context->user?->owner ?? false);
}

test('a derived menu hides a page and its subtree when the callback returns true', function () {
    app(MenuRegistry::class)->derive('primary', 'dashboard', 'billing')->exceptWhen('billing', ownerOnly(...));

    $this->get('/dashboard')->assertOk();
    $this->assertMenuHas('primary', 'dashboard')
        ->assertMenuMissing('primary', 'billing')
        ->assertMenuMissing('primary', 'billing.invoices');

    $this->get('/dashboard', ['X-Owner' => 'yes'])->assertOk();
    $this->assertMenuHas('primary', 'billing')->assertMenuHas('primary', 'billing.invoices');
});

test('breadcrumbs and the current page ignore menu exclusions', function () {
    app(MenuRegistry::class)->derive('primary', 'dashboard', 'billing')->exceptWhen('billing', fn (): bool => true);

    $this->get('/billing/invoices')->assertOk();

    $this->assertCurrentPage('billing.invoices')
        ->assertBreadcrumbs(['Billing', 'Invoices'])
        ->assertMenuMissing('primary', 'billing');
});

test('callbacks under an excluded branch, and labels of hidden pages, never run', function () {
    app(PageRegistry::class)->register(
        Page::make('reports')->label(fn (): string => throw new LogicException('Hidden labels must not resolve.'))->children(
            Page::make('reports.yearly')->label('Yearly'),
        ),
    );
    app(MenuRegistry::class)->derive('primary', 'dashboard', 'reports')
        ->exceptWhen('reports', fn (): bool => true)
        ->exceptWhen('reports.yearly', fn (): bool => throw new LogicException('Nested callbacks must not run.'));

    $this->get('/dashboard')->assertOk();

    expect(array_column(Current::menu('primary'), 'pageId'))->toBe(['dashboard']);
});

test('several callbacks for one page hide it when any returns true', function () {
    app(MenuRegistry::class)->derive('primary', 'dashboard', 'billing')
        ->exceptWhen('billing', fn (): bool => false)
        ->exceptWhen('billing', fn (): bool => true);

    $this->get('/dashboard')->assertOk();
    $this->assertMenuMissing('primary', 'billing');
});

test('invalid exclusions fail clearly', function () {
    app(MenuRegistry::class)->derive('unknown', 'dashboard')->exceptWhen('missing', fn (): bool => true);

    expect(fn () => app(MenuRegistry::class)->finalize())
        ->toThrow(InvalidDefinition::class, "Menu 'unknown' excludes unknown page 'missing'.");
});

test('exclusion callbacks must return a bool', function () {
    app(MenuRegistry::class)->derive('primary', 'dashboard', 'billing')->exceptWhen('billing', fn (): string => 'no');

    $this->get('/dashboard')->assertOk();

    expect(fn () => Current::menu('primary'))
        ->toThrow(ResolutionFailed::class, "Exclusion callback for menu 'primary' exclusion of page 'billing' must return a bool.");
});

test('current:menus lists request-time exclusions without running them', function () {
    app(MenuRegistry::class)->derive('primary', 'dashboard', 'billing')
        ->exceptWhen('billing', fn (): bool => throw new LogicException('Commands must not run callbacks.'));

    $this->artisan('current:menus')
        ->expectsOutputToContain('derived from dashboard, billing; except when: billing')
        ->expectsOutputToContain('billing  Billing · hidden when')
        ->assertExitCode(0);

    Artisan::call('current:menus primary --json');
    $menu = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR)[0];

    expect($menu['exceptWhen'])->toBe(['billing'])
        ->and($menu['entries'][1]['visibility'])->toBeTrue();
});
