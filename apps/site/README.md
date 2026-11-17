# Current site

The website and guides at [currentphp.com](https://currentphp.com): a Laravel 13
application built with Current, exported to static files and served by GitHub
Pages.

The site dogfoods Current. Each guide in [`content/docs/`](content/docs/) is a
Current page, the sidebar is a derived menu, the top bar is a defined menu, and
breadcrumbs, active states, the sitemap, and previous and next links all read
the same description. Where Current lacks something the site needs, the site's
own code works around it and names the board card that would replace it.

## Writing guides

Each Markdown file under `content/docs/` is a guide. Its path is its URL and its
page ID: `content/docs/getting-started/menus.md` is served at
`/docs/getting-started/menus` as page `docs.getting-started.menus`. Front
matter:

```yaml
---
title: Menus                 # page heading and <title>
label: Menus                 # optional; the menu and breadcrumb label (defaults to title)
description: Build menus...  # the lead paragraph and meta description
order: 4                     # sibling order
banner: Unreleased...        # optional; a notice above the guide, for unreleased features
---
```

- Every folder needs an `index.md`. One with front matter and no body is a
  route-less section: it groups guides in the sidebar and breadcrumbs.
- Link to other guides with root-relative URLs, such as
  `/docs/subjects#reading-subjects`. The tests fail on links to missing pages
  or anchors.
- Code blocks are highlighted with Phiki. GitHub's `> [!NOTE]`, `> [!TIP]`,
  `> [!IMPORTANT]`, `> [!WARNING]`, and `> [!CAUTION]` alerts are supported.
- The guides document `main`. When a guide covers something not yet released,
  give it a `banner` naming the release it will arrive in.

## How it works

| Part | Where |
| --- | --- |
| Reading `content/docs` into guides | [`app/Docs/DocsCatalog.php`](app/Docs/DocsCatalog.php) |
| Pages and menus registered with Current | [`app/Providers/AppServiceProvider.php`](app/Providers/AppServiceProvider.php) |
| A route per guide | [`routes/web.php`](routes/web.php) |
| Markdown, highlighting, alerts, and headings | [`app/Docs/MarkdownRenderer.php`](app/Docs/MarkdownRenderer.php) |
| Previous and next (until WK-037) | [`app/Docs/Sequence.php`](app/Docs/Sequence.php) |
| Sitemap from the page registry | [`app/Http/Controllers/SitemapController.php`](app/Http/Controllers/SitemapController.php) |
| Layout and theme (Blade and Tailwind) | [`resources/views/`](resources/views/) |
| Static export settings | [`config/export.php`](config/export.php) |

The site installs `current/current` from this monorepo's `packages/` through a
path repository, so it always runs, and documents, `main`.

## Development

PHP 8.5 and Node 24.21.0, pinned in [`mise.toml`](mise.toml).

```sh
composer setup     # install, create .env, build assets
composer dev       # serve at http://localhost:8000 (run `npm run dev` alongside for live CSS)
composer check     # Pint, PHPStan, asset build, and Pest
composer build     # export the static site to dist/ for https://currentphp.com
```

`composer build` builds the assets, exports every page reachable from `/` (plus
`404.html` and `sitemap.xml`) with `spatie/laravel-export`, and indexes the
result with Pagefind for search. Search works only in the exported site.

The [site workflow](../../.github/workflows/site.yml) runs `composer check` on
pull requests and pushes, and on `main` builds the site and deploys `dist/` to
GitHub Pages. `public/CNAME` sets the custom domain.
