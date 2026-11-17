<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\ProjectExample;
use App\TaskExample;
use App\WavyKit\Subjects\SubjectKind;
use App\WavyKit\Subjects\SubjectRegistry;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
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
    public function register(): void
    {
        $this->app->singleton(SubjectRegistry::class);
    }

    public function boot(PageRegistry $pages, MenuRegistry $menus, SubjectRegistry $subjects): void
    {
        $this->registerSubjects($subjects);
        $this->registerPages($pages, $subjects);
        $this->registerNavigation($menus);
    }

    /**
     * Subjects (WK-019 prototype, App\WavyKit\Subjects): projects and their
     * tasks, each described once and read by breadcrumbs, links, and props.
     */
    private function registerSubjects(SubjectRegistry $subjects): void
    {
        $subjects->register(
            SubjectKind::make('project')->for(ProjectExample::class)
                ->label(fn (ProjectExample $project): string => $project->name)
                ->page('projects.show'),
            SubjectKind::make('task')->for(TaskExample::class)
                ->label(fn (TaskExample $task): string => $task->title)
                ->page('tasks.show', fn (TaskExample $task): array => ['project' => $task->project(), 'task' => $task]),
        );
    }

    /** Places (WavyKit\Pages): page identity, hierarchy, and resource-aware labels. */
    private function registerPages(PageRegistry $pages, SubjectRegistry $subjects): void
    {
        Route::bind('project', fn (string $id): ProjectExample => ProjectExample::findExample($id) ?? abort(404));
        Route::bind('task', function (string $id, RouteDefinition $route): TaskExample {
            $project = $route->parameter('project');

            return ($project instanceof ProjectExample ? TaskExample::findExample($project, $id) : null) ?? abort(404);
        });

        $pages->register(
            Page::make('home')->route('home')->label('Home'),
            Page::make('projects')->route('projects.index')->label('Projects')->children(
                $subjects->presents(Page::make('projects.show')->route('projects.show'), 'project')->children(
                    Page::make('projects.edit')->route('projects.edit')->label('Edit'),
                    Page::make('projects.tasks')->label('Tasks')->children(
                        $subjects->presents(Page::make('tasks.show')->route('tasks.show'), 'task'),
                    ),
                ),
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
