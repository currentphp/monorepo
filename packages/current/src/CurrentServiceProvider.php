<?php

declare(strict_types=1);

namespace Current;

use Current\Console\CheckCommand;
use Current\Console\MenusCommand;
use Current\Console\PagesCommand;
use Current\Console\SubjectsCommand;
use Current\Navigation\MenuRegistry;
use Current\Navigation\Navigation;
use Current\Pages\PageRegistry;
use Current\Pages\Pages;
use Current\Subjects\SubjectRegistry;
use Current\Subjects\Subjects;
use Illuminate\Support\ServiceProvider;

/** Shares declarations across requests while scoping resolvers to the application request/job lifecycle. */
final class CurrentServiceProvider extends ServiceProvider
{
    /** @internal Called by Laravel; the bindings and their lifetimes are implementation details. */
    public function register(): void
    {
        $this->app->singleton(PageRegistry::class);
        $this->app->singleton(MenuRegistry::class);
        $this->app->singleton(SubjectRegistry::class);
        $this->app->scoped(Pages::class);
        $this->app->scoped(Navigation::class);
        $this->app->scoped(Subjects::class);
        // Stateless, so the facade's cached instance never holds request state.
        $this->app->singleton(Experience::class);
    }

    /** @internal Called by Laravel; registers the inspection commands in the console. */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PagesCommand::class, MenusCommand::class, SubjectsCommand::class, CheckCommand::class]);
        }
    }
}
