<?php

declare(strict_types=1);

use App\Docs\DocsCatalog;
use App\Docs\Guide;

/**
 * @return array<string, array{Guide}>
 */
function routableGuides(): array
{
    $guides = [];

    foreach (app(DocsCatalog::class)->routable() as $guide) {
        $guides[$guide->id] = [$guide];
    }

    return $guides;
}

it('renders the landing page as the root page', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('composer require current/current');

    $this->assertCurrentPage('home')->assertBreadcrumbs(['Current']);
});

it('renders every guide as its Current page', function (): void {
    $docs = app(DocsCatalog::class);

    foreach ($docs->routable() as $guide) {
        $this->get('/'.$guide->path)
            ->assertOk()
            ->assertSee('<title>'.e($guide->title).' · Current</title>', false)
            ->assertSee('<meta name="description" content="'.e($guide->description).'">', false);

        $trail = [];

        for ($current = $guide; $current !== null; $current = $current->parentId === null ? null : $docs->get($current->parentId)) {
            array_unshift($trail, $current->label);
        }

        $this->assertCurrentPage($guide->id)->assertBreadcrumbs(['Current', ...$trail]);
    }
});

it('marks the guide and its section active in the sidebar', function (): void {
    $this->get('/docs/getting-started/menus')->assertOk();

    $this->assertMenuActive('docs', 'docs.getting-started')
        ->assertMenuActive('docs', 'docs.getting-started.menus')
        ->assertMenuActive('top', 'docs');
});

it('keeps a section without a body as a route-less page', function (): void {
    expect(app(DocsCatalog::class)->get('docs.getting-started')->routable)->toBeFalse();

    $this->get('/docs/getting-started')->assertNotFound();
});

it('links each guide to the previous and next one in reading order', function (): void {
    $this->get('/docs/getting-started/pages')
        ->assertOk()
        ->assertSeeInOrder(['rel="prev"', 'Installation', 'rel="next"', 'Breadcrumbs'], false);

    $this->get('/docs')
        ->assertOk()
        ->assertDontSee('rel="prev"', false)
        ->assertSeeInOrder(['rel="next"', 'Installation'], false);
});

it('passes current:check', function (): void {
    $this->artisan('current:check')->assertSuccessful();
});

it('lists every page with a URL in the sitemap', function (): void {
    $response = $this->get('/sitemap.xml')->assertOk();

    foreach (app(DocsCatalog::class)->routable() as $guide) {
        $response->assertSee(url($guide->path), false);
    }

    $response->assertSee('<loc>'.url('/').'</loc>', false);
});

it('resolves every internal link and anchor', function (): void {
    $docs = app(DocsCatalog::class);
    $paths = ['/', ...array_map(fn (Guide $guide): string => '/'.$guide->path, $docs->routable())];
    $pages = [];

    foreach ($paths as $path) {
        $pages[$path] = $this->get($path)->assertOk()->getContent();
    }

    $base = rtrim(url('/'), '/');
    $broken = [];

    foreach ($pages as $from => $html) {
        preg_match_all('/href="([^"]+)"/', (string) $html, $matches);

        foreach ($matches[1] as $href) {
            $href = html_entity_decode($href);

            if (str_starts_with($href, $base)) {
                $href = substr($href, strlen($base)) ?: '/';
            }

            if (! str_starts_with($href, '/') && ! str_starts_with($href, '#')) {
                continue;
            }

            [$path, $fragment] = array_pad(explode('#', $href, 2), 2, null);
            $path = $path === '' ? $from : $path;

            if (in_array($path, ['/sitemap.xml', '/favicon.svg', '/pagefind/pagefind-ui.css'], true)) {
                continue;
            }

            if (! isset($pages[$path])) {
                $broken[] = "{$from} → {$href} (no such page)";

                continue;
            }

            if ($fragment !== null && $fragment !== '' && ! str_contains((string) $pages[$path], 'id="'.$fragment.'"')) {
                $broken[] = "{$from} → {$href} (no such anchor)";
            }
        }
    }

    expect($broken)->toBe([]);
});

it('serves a not-found page for GitHub Pages', function (): void {
    $this->get('/404.html')->assertOk()->assertSee('Page not found');
});
