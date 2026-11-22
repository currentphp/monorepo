<?php

declare(strict_types=1);

namespace Prototype;

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
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->make(Repository::class)->set([
            'app.key' => 'base64:'.base64_encode(str_repeat('p', 32)), // Synthetic fixture key.
            'app.url' => 'http://127.0.0.1:18765',
            'app.debug' => true,
            'session.driver' => 'file',
            'session.files' => base_path('.runtime/storage/framework/sessions'),
            'session.secure' => false,
            'session.same_site' => 'lax',
            'database.default' => 'sqlite',
            'database.connections.sqlite' => ['driver' => 'sqlite', 'database' => base_path('.runtime/prototype.sqlite'), 'foreign_key_constraints' => true],
            'view.paths' => [base_path('views')],
            'view.compiled' => base_path('.runtime/storage/framework/views'),
            'cache.default' => 'array',
            'logging.default' => 'single',
            'logging.channels.single.path' => base_path('.runtime/storage/logs/laravel.log'),
            'livewire.layout' => 'layout',
            'livewire.component_layout' => 'layout',
        ]);
    }

    public function boot(PageRegistry $pages, SubjectRegistry $subjects, MenuRegistry $menus, ActionRegistry $actions): void
    {
        Livewire::component('project-page', ProjectPage::class);
        Livewire::component('project-child', ProjectChild::class);
        Route::bind('project', fn (string $id): Project => Project::query()->findOrFail($id));
        Gate::define('rename', fn ($user, Project $project): Response => DB::table('permissions')->where('id', 1)->value('allowed') ? Response::allow() : Response::deny('Permission revoked.'));
        Gate::define('view', fn ($user, Project $project): bool => (bool) DB::table('permissions')->where('id', 1)->value('view_allowed'));
        $subjects->register(Subject::make('project')->for(Project::class)
            ->label(fn (Project $project): string => (string) $project->getAttribute('name'))
            ->title(fn (Project $project): string => 'Project '.$project->getAttribute('name'))
            ->description(fn (Project $project): string => 'About '.$project->getAttribute('name'))
            ->page('projects.show'));
        $pages->register(Page::make('projects')->label('Projects')->route('projects.index')->children(
            Page::make('projects.show')->route('projects.show')->presents('project')->children(
                Page::make('projects.edit')->route('projects.edit')->label('Edit')->title('Edit project'),
            ),
        ));
        $menus->define('primary', MenuEntry::page('projects'),
            MenuEntry::page('projects.show')->visibleWhen(fn (Context $context): bool => $context->page !== null),
            MenuEntry::page('projects.edit')->visibleWhen(fn (Context $context): bool => $context->user !== null && $context->page?->id === 'projects.edit'));
        $actions->register(Action::make('rename')->for('project')->label('Rename')->route('projects.rename', 'POST')->can('rename'),
            Action::make('edit')->for('project')->label('Edit')->route('projects.edit'),
            Action::make('help')->on('projects.edit')->label(fn (Context $context): string => 'Help for '.$context->parameter('project')->getAttribute('name'))
                ->route('projects.index')->visibleWhen(fn (Context $context): bool => $context->user !== null && $context->page?->id === 'projects.edit')
                ->enabledWhen(fn (): Response => DB::table('permissions')->where('id', 1)->value('allowed') ? Response::allow() : Response::deny('Page help disabled.')));
    }
}
