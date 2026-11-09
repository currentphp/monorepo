<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\ProjectExample;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LogicException;
use WavyKit\Navigation\Menu;
use WavyKit\Navigation\MenuRegistry;
use WavyKit\Pages\Page;
use WavyKit\Pages\PageContext;
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

    /** wavykit/pages: page identity, hierarchy, and resource-aware labels. */
    private function registerPages(PageRegistry $pages): void
    {
        Route::bind('project', fn (string $id): ProjectExample => ProjectExample::findExample($id) ?? abort(404));

        $pages->register(
            Page::make('home')->route('home')->label('Home'),
            Page::make('projects')->route('projects.index')->label('Projects')->children(
                Page::make('projects.show')->route('projects.show')
                    ->label(function (PageContext $context): string {
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

    /** wavykit/navigation: menus shared with every page through SharedProps::MENUS. */
    private function registerNavigation(MenuRegistry $menus): void
    {
        $menus->derive('primary', 'projects')->except('projects.show');
        $menus->define('footer',
            Menu::group('resources')->label('Resources')->children(Menu::page('home'), Menu::page('projects')->label('All projects')),
            Menu::group('account')->label('Your account')
                ->visibleWhen(fn (PageContext $context): bool => $context->user instanceof User && $context->user->hasVerifiedEmail())
                ->children(Menu::page('profile')),
            Menu::page('login')->visibleWhen(fn (PageContext $context): bool => $context->user === null),
        );
    }
}
