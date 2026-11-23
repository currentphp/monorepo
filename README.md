# Current

One description of your Laravel app's experience — where users are, how they
move around, and what they see — that every part of the interface reads.

Laravel has primitives for handling requests, but none for the experience
around them, so route names, breadcrumb arrays, menu configuration, and page
headings tend to drift apart. Current starts with pages: each page is defined
once, with a stable ID, its place in the hierarchy, a label, and the named
route it belongs to. Breadcrumbs and menus read from that definition, so
labels, URLs, and active states always agree. Routes, controllers,
authorization, and rendering stay with your application, and everything
returns plain data for Blade, Inertia, or any other frontend.

## Getting started

```sh
composer require current/current
```

The core provides pages, breadcrumbs, menus, subjects, and actions, for Laravel 13 and
PHP 8.3+; Inertia apps add `composer require current/inertia`. Current was
named WavyKit until 2026-11-16: its earlier releases are `wavykit/core` 0.2.0
and `wavykit/inertia` 0.1.0, and the first releases under the new names,
`current/current` 0.3.0 and `current/inertia` 0.2.0, were released on the same
day. The latest releases are `current/current` 0.5.0 and `current/inertia`
0.4.0 (2026-11-17). Pull
requests are not open yet; issues and feedback are welcome (see
[CONTRIBUTING.md](CONTRIBUTING.md)).

| Package | Purpose |
| --- | --- |
| [`current/current`](packages/current/README.md) | Pages (identity, hierarchy, route association, request context), breadcrumbs, menus, and subjects. |
| [`current/inertia`](packages/inertia/README.md) | Shares the page ID, subject, breadcrumbs, and menus with Inertia, with TypeScript types. |

Integrations that need other libraries are separate packages.

See [Direction and decisions](docs/decisions.md) for package boundaries,
supported versions, and what is planned next.

## Writing about Current

Write the name as **Current**: one word, capital C, never all caps.
`currentphp` is the GitHub organization and domain, not the product's name.
Where readers need context, describe Current rather than renaming it:
"Current, a Laravel package".

Refer to packages by their Composer names in code formatting: `current/current`
for the core and `current/inertia` for the Inertia integration. In prose, say
"the core package" or "the Inertia integration".

Where the everyday word would be ambiguous next to the product, Current's
documentation says "active" instead, as in "the active page".

## Development

Development runs from this root. One install links every package from
`packages/*`, and each package keeps its own runtime dependencies,
documentation, and tool configuration:

```sh
composer install
composer check        # or: composer check current inertia
```

Each package has its own version, released with a `<package>/vX.Y.Z` tag and
distributed through a read-only split repository; CI runs every package's checks. See [Monorepo layout and releases](docs/monorepo.md)
for the layout and release workflow. The monorepo and the `current/current` and
`current/inertia` split repositories are public on GitHub, and both packages
are published on Packagist:
[`current/current`](https://packagist.org/packages/current/current) and
[`current/inertia`](https://packagist.org/packages/current/inertia).

## Ownership

Current maintainers own this repository. The core began as the Pages package,
brought into the monorepo with its existing Git history; menus were developed
as a separate Navigation package and merged back into the core before the
first release.
Publishing and repository distribution require separate decisions and approval.
