# WavyKit

One description of your Laravel app's experience — where users are, how they
move around, and what they see — that every part of the interface reads.

Laravel has primitives for handling requests, but none for the experience
around them, so route names, breadcrumb arrays, menu configuration, and page
headings tend to drift apart. WavyKit starts with pages: each page is defined
once, with a stable ID, its place in the hierarchy, a label, and the named
route it belongs to. Breadcrumbs and menus read from that definition, so
labels, URLs, and active states always agree. Routes, controllers,
authorization, and rendering stay with your application, and everything
returns plain data for Blade, Inertia, or any other frontend.

## Getting started

```sh
composer require wavykit/core
```

The core provides pages, breadcrumbs, and menus; its current release is 0.2.0,
for Laravel 13 and PHP 8.3+. Inertia apps add `composer require
wavykit/inertia` (0.1.0). Pull requests are not open yet; issues
and feedback are welcome (see [CONTRIBUTING.md](CONTRIBUTING.md)).

| Package | Purpose |
| --- | --- |
| [`wavykit/core`](packages/core/README.md) | Pages (identity, hierarchy, route association, request context), breadcrumbs, and menus. |
| [`wavykit/inertia`](packages/inertia/README.md) | Shares the page ID, breadcrumbs, and menus with Inertia, with TypeScript types. |

Integrations that need other libraries are separate packages.

See [Direction and decisions](docs/decisions.md) for package boundaries,
supported versions, and what is planned next.

## Development

Development runs from this root. One install links every package from
`packages/*`, and each package keeps its own runtime dependencies,
documentation, and tool configuration:

```sh
composer install
composer check        # or: composer check core inertia
```

Each package has its own version, released with a `<package>/vX.Y.Z` tag and
distributed through a read-only split repository; CI runs every package's checks. See [Monorepo layout and releases](docs/monorepo.md)
for the layout and release workflow. The monorepo and the `wavykit/core` and
`wavykit/inertia` split repositories are public on GitHub, and both packages
are published on Packagist:
[`wavykit/core`](https://packagist.org/packages/wavykit/core) and
[`wavykit/inertia`](https://packagist.org/packages/wavykit/inertia).

## Ownership

WavyKit maintainers own this repository. The core began as the Pages package,
brought into the monorepo with its existing Git history; menus were developed
as a separate Navigation package and merged back into the core before the
first release.
Publishing and repository distribution require separate decisions and approval.
