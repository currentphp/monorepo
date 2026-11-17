<?php

declare(strict_types=1);

namespace App\Providers;

use App\Docs\DocsCatalog;
use App\Docs\Guide;
use Current\Navigation\MenuEntry;
use Current\Navigation\MenuRegistry;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocsCatalog::class, fn (): DocsCatalog => new DocsCatalog(base_path('content/docs')));
    }

    /**
     * Describes the site to Current: the landing page is the root, and every
     * guide in content/docs is a page under it. Runs on every boot, including
     * boots from cached routes.
     */
    public function boot(DocsCatalog $docs, PageRegistry $pages, MenuRegistry $menus): void
    {
        $pages->register(Page::make('home')->label('Current')->route('home'));

        foreach ($docs->all() as $guide) {
            $page = Page::make($guide->id)
                ->label($guide->label)
                ->parent($guide->parentId ?? 'home')
                ->order($guide->order);

            $pages->register($guide->routable ? $page->route($guide->routeName()) : $page);
        }

        $menus->derive('docs', ...array_map(
            fn (Guide $guide): string => $guide->id,
            $docs->children('docs'),
        ));

        $menus->define('top',
            MenuEntry::page('docs')->label('Docs'),
            MenuEntry::page('docs.support-policy')->label('Support'),
        );
    }
}
