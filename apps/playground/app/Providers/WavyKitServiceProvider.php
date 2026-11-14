<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\ProjectExample;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LogicException;
use WavyKit\Context;
use WavyKit\Navigation\MenuEntry;
use WavyKit\Navigation\MenuRegistry;
use WavyKit\Pages\Page;
use WavyKit\Pages\PageRegistry;

/**
 * Registers the playground's declarations for each WavyKit component.
 *
 * Add one method per component, and call it from boot(). Registration runs on
 * every boot, including boots that restore cached routes.
 */
final class WavyKitServiceProvider extends ServiceProvider
{
    public function boot(PageRegistry $pages, MenuRegistry $menus): void
    {
        $this->registerPages($pages);
        $this->registerNavigation($menus);
    }

    /** Places (WavyKit\Pages): page identity, hierarchy, and resource-aware labels. */
    private function registerPages(PageRegistry $pages): void
    {
        Route::bind('project', fn (string $id): ProjectExample => ProjectExample::findExample($id) ?? abort(404));

        $pages->register(
            Page::make('home')->route('home')->label('Home'),
            Page::make('projects')->route('projects.index')->label('Projects')->children(
                Page::make('projects.show')->route('projects.show')
                    ->label(function (Context $context): string {
                        $project = $context->parameter('project');
                        if (! $project instanceof ProjectExample) {
                            throw new LogicException('Expected a bound project.');
                        }

                        return $project->name;
                    })->children(Page::make('projects.edit')->route('projects.edit')->label('Edit')),
            ),
            Page::make('dashboard')->route('dashboard')->label('Overview'),
            Page::make('profile')->route('profile.edit')->label('Profile')->parent('dashboard'),
            Page::make('login')->route('login')->label('Sign in'),
        );
    }

    /** Wayfinding (WavyKit\Navigation): menus shared with every page by HandleInertiaRequests. */
    private function registerNavigation(MenuRegistry $menus): void
    {
        $menus->derive('primary', 'projects')->except('projects.show');
        $menus->define('footer',
            MenuEntry::group('resources')->label('Resources')->children(MenuEntry::page('home'), MenuEntry::page('projects')->label('All projects')),
            MenuEntry::group('account')->label('Your account')
                ->visibleWhen(fn (Context $context): bool => $context->user instanceof User && $context->user->hasVerifiedEmail())
                ->children(MenuEntry::page('profile')),
            MenuEntry::page('login')->visibleWhen(fn (Context $context): bool => $context->user === null),
        );
    }
}
