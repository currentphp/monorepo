<?php

declare(strict_types=1);

namespace App\Providers;

use App\DemoWorkspace;
use App\Livewire\OnboardingPage;
use App\Livewire\ProjectPage;
use App\OnboardingDemo;
use App\ProjectExample;
use App\TaskExample;
use Current\Actions\Action;
use Current\Actions\ActionRegistry;
use Current\Context;
use Current\Navigation\MenuEntry;
use Current\Navigation\MenuRegistry;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Subjects\Subject;
use Current\Subjects\SubjectRegistry;
use Illuminate\Auth\Access\Response;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * Registers the playground's declarations for each Current component.
 *
 * Add one method per component, and call it from boot(). Registration runs on
 * every boot, including boots that restore cached routes.
 */
final class ExperienceServiceProvider extends ServiceProvider
{
    public function boot(PageRegistry $pages, MenuRegistry $menus, SubjectRegistry $subjects, ActionRegistry $actions): void
    {
        Livewire::component('project-page', ProjectPage::class);
        Livewire::component('onboarding-page', OnboardingPage::class);
        Gate::define('prepare-demo-workspace', fn () => app(OnboardingDemo::class)->profile() !== null ? Response::allow() : Response::deny('Complete your demo profile first.'));
        $this->registerSubjects($subjects);
        $this->registerPages($pages);
        $this->registerNavigation($menus);
        $this->registerActions($actions);
    }

    /**
     * Actions (Current\Actions): what users can do to tasks and on the task list.
     * Described only; TaskController performs and authorizes each action.
     */
    private function registerActions(ActionRegistry $actions): void
    {
        $actions->register(
            Action::make('start')->on('onboarding')->label('Try simulated signup')->route('onboarding.profile'),
            Action::make('continue')->on('onboarding.profile')->label('Continue to workspace')->route('onboarding.profile.store', 'POST'),
            Action::make('continue')->on('onboarding.workspace')->label('Review workspace')->route('onboarding.workspace.store', 'POST')->can('prepare-demo-workspace'),
            Action::make('finish')->for('workspace')->label('Finish simulation')->route('onboarding.finish', 'POST')->can('finish')
                ->confirm(fn (DemoWorkspace $workspace): string => 'Finish setup for '.$workspace->name.'?'),
            Action::make('projects')->on('onboarding.review')->label('Open Projects')->route('projects.index')
                ->visibleWhen(fn (Context $context): bool => app(OnboardingDemo::class)->complete()),
            Action::make('rename')->for('project')->label('Rename')->route('projects.rename', 'POST')
                ->visibleWhen(fn (ProjectExample $project, Context $context): bool => in_array($context->page?->id, [null, 'projects.show', 'projects.edit'], true))
                ->can('rename')->confirm(fn (ProjectExample $project): string => 'Rename '.$project->name.'?'),
            Action::make('edit')->for('project')->label('Edit')->route('projects.edit')
                ->visibleWhen(fn (ProjectExample $project, Context $context): bool => in_array($context->page?->id, [null, 'projects.show', 'projects.edit'], true)),
            Action::make('create')->on('projects.tasks')
                ->label('New task')->icon('plus')
                ->route('tasks.create'),
            Action::make('edit')->for('task')
                ->label('Edit')->icon('pencil')->order(1)
                ->route('tasks.edit', 'GET', fn (TaskExample $task): array => ['project' => $task->project(), 'task' => $task])
                ->can('update'),
            Action::make('archive')->for('task')
                ->label('Archive')->icon('archive')->order(2)->destructive()
                ->confirm(fn (TaskExample $task): string => "Archive “{$task->title}”?")
                ->route('tasks.archive', 'POST', fn (TaskExample $task): array => ['project' => $task->project(), 'task' => $task])
                ->can('archive'),
        );
    }

    /** Subjects (Current\Subjects): projects and their tasks, described once and read by breadcrumbs, links, and props. */
    private function registerSubjects(SubjectRegistry $subjects): void
    {
        $subjects->register(
            Subject::make('workspace')->for(DemoWorkspace::class)->label(fn (DemoWorkspace $workspace): string => $workspace->name)
                ->title(fn (DemoWorkspace $workspace): string => $workspace->name)
                ->description(fn (DemoWorkspace $workspace): string => 'A simulated workspace prepared for '.(app(OnboardingDemo::class)->profile()['name'] ?? 'your demo profile').'.')
                ->page('onboarding.review'),
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

        Route::bind('workspace', fn (string $id): DemoWorkspace => $id === 'demo' ? app(OnboardingDemo::class)->workspace() : abort(404));
        $pages->register(
            Page::make('examples')->route('home')->label('Examples'),
            Page::make('onboarding')->route('onboarding.index')->label('Onboarding')->title('Try simulated onboarding')->children(
                Page::make('onboarding.profile')->route('onboarding.profile')->label('Profile')->title('Create your demo profile'),
                Page::make('onboarding.workspace')->route('onboarding.workspace')->label('Workspace')
                    ->title(fn (Context $context): string => 'Prepare a workspace for '.(app(OnboardingDemo::class)->profile()['name'] ?? 'your demo profile')),
                Page::make('onboarding.review')->route('onboarding.review')->label('Review')->presents('workspace')
                    ->title(fn (Context $context): string => 'Review '.$context->parameter('workspace')->name)
                    ->routeParameters(fn (Context $context): array => ['workspace' => app(OnboardingDemo::class)->workspace()]),
            ),
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
        );
    }

    /** Wayfinding (Current\Navigation): menus shared with every page by HandleInertiaRequests. */
    private function registerNavigation(MenuRegistry $menus): void
    {
        $menus->derive('primary', 'examples', 'projects', 'onboarding')->except('projects.show')
            ->except('onboarding.profile')->except('onboarding.workspace')->except('onboarding.review');
        $menus->define('onboarding', MenuEntry::page('onboarding.profile'),
            MenuEntry::page('onboarding.workspace')->visibleWhen(fn (Context $context): bool => app(OnboardingDemo::class)->profile() !== null),
            MenuEntry::page('onboarding.review')->label('Review')->visibleWhen(fn (Context $context): bool => app(OnboardingDemo::class)->hasWorkspace()));
        $menus->define('footer', MenuEntry::group('resources')->label('Resources')
            ->children(MenuEntry::page('projects')->label('All projects'), MenuEntry::page('examples')->label('All examples')));
    }
}
