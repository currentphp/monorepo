<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\ProjectExample;
use App\Prototype\Actions\Action;
use App\Prototype\Actions\ActionRegistry;
use App\TaskExample;
use Current\Context;
use Current\Navigation\MenuEntry;
use Current\Navigation\MenuRegistry;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Subjects\Subject;
use Current\Subjects\SubjectRegistry;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the playground's declarations for each Current component.
 *
 * Add one method per component, and call it from boot(). Registration runs on
 * every boot, including boots that restore cached routes.
 */
final class ExperienceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActionRegistry::class);
    }

    public function boot(PageRegistry $pages, MenuRegistry $menus, SubjectRegistry $subjects, ActionRegistry $actions): void
    {
        $this->registerSubjects($subjects);
        $this->registerPages($pages);
        $this->registerNavigation($menus);
        $this->registerActions($actions);
    }

    /**
     * Capability prototype (WK-008): what users can do to tasks and on the task list.
     * Described only; TaskController performs and authorizes each action.
     */
    private function registerActions(ActionRegistry $actions): void
    {
        $signedIn = fn (Context $context): bool => $context->user !== null;
        $signedInForTask = fn (TaskExample $task, Context $context): bool => $context->user !== null;

        $actions->register(
            Action::make('create')->on('projects.tasks')
                ->label('New task')->icon('plus')
                ->route('tasks.create')
                ->visibleWhen($signedIn),
            Action::make('edit')->for('task')
                ->label('Edit')->icon('pencil')->order(1)
                ->route('tasks.edit', 'GET', fn (TaskExample $task): array => ['project' => $task->project(), 'task' => $task])
                ->visibleWhen($signedInForTask)
                ->can('update'),
            Action::make('archive')->for('task')
                ->label('Archive')->icon('archive')->order(2)->destructive()
                ->confirm(fn (TaskExample $task): string => "Archive “{$task->title}”?")
                ->route('tasks.archive', 'POST', fn (TaskExample $task): array => ['project' => $task->project(), 'task' => $task])
                ->visibleWhen($signedInForTask)
                ->can('archive'),
        );
    }

    /** Subjects (Current\Subjects): projects and their tasks, described once and read by breadcrumbs, links, and props. */
    private function registerSubjects(SubjectRegistry $subjects): void
    {
        $subjects->register(
            Subject::make('project')->for(ProjectExample::class)
                ->label(fn (ProjectExample $project): string => $project->name)
                ->title(fn (ProjectExample $project): string => "Project {$project->name}")
                ->description(fn (ProjectExample $project): string => "Tasks and links for the {$project->name} example project.")
                ->icon('folder')
                ->page('projects.show'),
            Subject::make('task')->for(TaskExample::class)
                ->label(fn (TaskExample $task): string => $task->title)
                ->page('tasks.show', fn (TaskExample $task): array => ['project' => $task->project(), 'task' => $task]),
        );
    }

    /** Places (Current\Pages): page identity, hierarchy, and resource-aware labels. */
    private function registerPages(PageRegistry $pages): void
    {
        Route::bind('project', fn (string $id): ProjectExample => ProjectExample::findExample($id) ?? abort(404));
        Route::bind('task', function (string $id, RouteDefinition $route): TaskExample {
            $project = $route->parameter('project');

            return ($project instanceof ProjectExample ? TaskExample::findExample($project, $id) : null) ?? abort(404);
        });

        $pages->register(
            Page::make('home')->route('home')->label('Home'),
            Page::make('projects')->route('projects.index')->label('Projects')->children(
                Page::make('projects.show')->route('projects.show')->presents('project')->children(
                    Page::make('projects.edit')->route('projects.edit')->label('Edit')
                        ->title(function (Context $context): string {
                            $project = $context->parameter('project');

                            return 'Edit '.($project instanceof ProjectExample ? $project->name : 'project');
                        }),
                    Page::make('projects.tasks')->route('tasks.index')->label('Tasks')->children(
                        Page::make('tasks.create')->route('tasks.create')->label('New task'),
                        Page::make('tasks.show')->route('tasks.show')->presents('task')->children(
                            Page::make('tasks.edit')->route('tasks.edit')->label('Edit'),
                        ),
                    ),
                ),
            ),
            Page::make('dashboard')->route('dashboard')->label('Overview'),
            Page::make('profile')->route('profile.edit')->label('Profile')->parent('dashboard'),
            Page::make('login')->route('login')->label('Sign in'),
        );
    }

    /** Wayfinding (Current\Navigation): menus shared with every page by HandleInertiaRequests. */
    private function registerNavigation(MenuRegistry $menus): void
    {
        // Overview is listed only for signed-in users; its routes still require auth.
        $menus->derive('primary', 'projects', 'dashboard')
            ->except('projects.show')
            ->exceptWhen('dashboard', fn (Context $context): bool => $context->user === null);
        $menus->define('footer',
            MenuEntry::group('resources')->label('Resources')->children(MenuEntry::page('home'), MenuEntry::page('projects')->label('All projects')),
            MenuEntry::group('account')->label('Your account')
                ->visibleWhen(fn (Context $context): bool => $context->user instanceof User && $context->user->hasVerifiedEmail())
                ->children(MenuEntry::page('profile')),
            MenuEntry::page('login')->visibleWhen(fn (Context $context): bool => $context->user === null),
        );
    }
}
