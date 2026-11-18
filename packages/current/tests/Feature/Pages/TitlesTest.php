<?php

declare(strict_types=1);

use Current\Context;
use Current\Exceptions\ResolutionFailed;
use Current\Facades\Current;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Testing\InteractsWithCurrent;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;

uses(InteractsWithCurrent::class);

beforeEach(function () {
    Route::bind('project', fn (string $id): string => $id === '42' ? 'Apollo' : 'Artemis');
    Route::middleware(SubstituteBindings::class)->group(function () {
        Route::get('/projects', fn () => 'projects')->name('projects.index');
        Route::get('/projects/{project}', fn () => 'project')->name('projects.show');
        Route::get('/settings', fn () => 'settings')->name('settings');
        Route::get('/unregistered', fn () => 'unregistered');
    });
});

function projectName(Context $context): string
{
    $project = $context->parameter('project');

    return is_string($project) ? $project : 'Unknown';
}

test('a page without a title uses its label, and has no description', function () {
    app(PageRegistry::class)->register(Page::make('settings')->route('settings')->label('Settings'));

    $this->get('/settings')->assertOk();

    expect(Current::title())->toBe('Settings')
        ->and(Current::description())->toBeNull();
});

test('static titles and descriptions are returned as declared', function () {
    app(PageRegistry::class)->register(
        Page::make('projects')->route('projects.index')->label('Projects')
            ->title('All projects')
            ->description('Every project you can see.'),
    );

    $this->get('/projects')->assertOk();

    $this->assertTitle('All projects')
        ->assertDescription('Every project you can see.')
        ->assertBreadcrumbs(['Projects']);
});

test('title and description callbacks read the bound resource', function () {
    app(PageRegistry::class)->register(
        Page::make('projects')->route('projects.index')->label('Projects')->children(
            Page::make('projects.show')->route('projects.show')
                ->label(projectName(...))
                ->title(fn (Context $context): string => 'Project '.projectName($context))
                ->description(fn (Context $context): ?string => projectName($context) === 'Apollo' ? 'The first mission.' : null),
        ),
    );

    $this->get('/projects/42')->assertOk();
    $this->assertTitle('Project Apollo')
        ->assertDescription('The first mission.')
        ->assertBreadcrumbs(['Projects', 'Apollo']);

    $this->get('/projects/7')->assertOk();
    $this->assertTitle('Project Artemis')->assertDescription(null);
});

test('no title or description is resolved without an active page', function () {
    app(PageRegistry::class)->register(Page::make('settings')->route('settings')->title('Settings'));

    $this->get('/unregistered')->assertOk();

    expect(Current::title())->toBeNull()
        ->and(Current::description())->toBeNull();
});

test('invalid callback results fail with the page named', function (Page $page, string $read, string $message) {
    app(PageRegistry::class)->register($page);

    $this->get('/settings')->assertOk();

    expect(fn () => Current::$read())->toThrow(ResolutionFailed::class, $message);
})->with([
    'empty title' => [Page::make('settings')->route('settings')->title(fn (): string => ' '), 'title', "Title callback for page 'settings' must return a non-empty string."],
    'non-string title' => [Page::make('settings')->route('settings')->title(fn (): int => 1), 'title', "Title callback for page 'settings' must return a string."],
    'failing title' => [Page::make('settings')->route('settings')->title(fn (): string => throw new RuntimeException('boom')), 'title', "Could not resolve title for page 'settings': boom"],
    'empty description' => [Page::make('settings')->route('settings')->description(fn (): string => ''), 'description', "Description callback for page 'settings' must return a non-empty string or null."],
    'non-string description' => [Page::make('settings')->route('settings')->description(fn (): array => []), 'description', "Description callback for page 'settings' must return a non-empty string or null."],
]);

test('title and description assertions explain failures', function () {
    app(PageRegistry::class)->register(Page::make('settings')->route('settings')->label('Settings')->description('Your preferences.'));

    $this->get('/settings')->assertOk();

    expect(fn () => $this->assertTitle('Preferences'))
        ->toThrow(AssertionFailedError::class, "Expected the title 'Preferences', but it was 'Settings'.")
        ->and(fn () => $this->assertDescription(null))
        ->toThrow(AssertionFailedError::class, "Expected the description to be empty, but it was 'Your preferences.'.");

    $this->get('/unregistered');

    expect(fn () => $this->assertTitle('Settings'))
        ->toThrow(AssertionFailedError::class, "Expected the title 'Settings', but it was none (no page is active).");
});
