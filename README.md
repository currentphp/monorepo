# WavyKit Playground

A Laravel 13 / Inertia 3 / React app for exercising every WavyKit component from
the local monorepo.
Created from App Foundation `v0.1.0` (commit `29dab9eb1f186fd6a214364baa03d23d18fac39d`).
The foundation and the WavyKit monorepo keep their independent repositories.

## Components

| Package and area                      | Registered in                                  | Demonstrated by                                                                               |
| ------------------------------------- | ---------------------------------------------- | --------------------------------------------------------------------------------------------- |
| `wavykit/core`: pages                 | `WavyKitServiceProvider::registerPages()`      | Page identity and resource-aware labels on every page; the projects demo.                     |
| `wavykit/core`: breadcrumbs and menus | `WavyKitServiceProvider::registerNavigation()` | Breadcrumbs, and primary and footer menus with `except()` and user visibility, on every page. |

[`app/WavyKit/SharedProps.php`](app/WavyKit/SharedProps.php) resolves component
output for each request. [`HandleInertiaRequests`](app/Http/Middleware/HandleInertiaRequests.php)
shares it with every Inertia page as the `wavykit` prop, typed in
[`resources/js/types/wavykit.ts`](resources/js/types/wavykit.ts) and rendered by
the components in [`resources/js/components/wavykit/`](resources/js/components/wavykit/).
Tests live in [`tests/Feature/WavyKit/`](tests/Feature/WavyKit/), one file per
component, plus `SharedPropsTest.php` for pages outside the demos.

## Demo data

Demo content comes from JSON files in [`database/fixtures/`](database/fixtures/),
loaded with `App\Fixtures::load('<name>')`. Fixtures need no migrations or
seeding, and the app and tests read the same data. Edit
`database/fixtures/projects.json` to change the example projects. PostgreSQL
holds only the foundation's application data, such as users and sessions.

## Adding a component

1. Require it in `composer.json` as `^0.1@dev`, and add it to the path
   repository's `versions` option at `0.1.x-dev`. The repository already reads
   every folder in the monorepo's `packages/`.
2. Add a `register<Component>()` method to `WavyKitServiceProvider` and call it
   from `boot()`.
3. Expose any request output through `SharedProps` and its TypeScript type, or
   pass it from a demo controller.
4. Add `tests/Feature/WavyKit/<Component>Test.php`, fixtures if the demo needs
   data, and a row in the table above.

## Local setup

Run from this application directory. Requires Docker Compose; the development
runtime includes PHP 8.5 and Node 24.21.0.

```sh
cp -n .env.example .env
export WWWUSER="$(id -u)" WWWGROUP="$(id -g)"
docker compose build laravel.test
docker compose run --rm --no-deps laravel.test composer install --no-interaction --prefer-dist
./vendor/bin/sail up -d
./vendor/bin/sail composer setup
```

WavyKit packages are local Composer path dependencies, symlinked from the
monorepo's `packages/` folder at version `0.1.x-dev`. The Compose services mount
that folder read-only at the matching relative path, so package changes are
immediately available here and no package source is copied into the app.
Installation requires the monorepo checkout at `../../packages/wavykit`; this app
is intentionally not a standalone distributable application.

The app uses port 8081, Vite 5174, PostgreSQL 5434, and Mailpit 8026. Its
`wavykit-playground` Compose project and database volume are independent of
other apps. The application keeps its Composer and npm lockfiles.

## Demo pages

Open `/projects`, then Apollo or Artemis and its edit-page link. The edit screen
is read-only. Breadcrumbs resolve the bound project name. The primary menu is
derived from Projects and excludes the project pages with `except()`, so
Projects stays active without listing them. The footer places the same index
under Resources with a different label. Guest and verified-user account links
follow the foundation's existing authentication state.

## Checks

```sh
./vendor/bin/sail composer ci:check
```

Tests use the foundation's dedicated `testing` PostgreSQL database. No migrations,
seeds, or tests should point at another application's database. No CI or agent
scaffolding is installed.

## Dependency status

The app updates vulnerable PHP packages within the foundation's constraints.
The frontend uses Vite+ 1.5.0 with its matching `vite` alias and npm override to
`@voidzero-dev/vite-plus-core@1.5.0`, following the
[documented upgrade path](https://viteplus.dev/guide/upgrade-project).
The migration preserves npm, existing lint/format rules, and the PHP test suite;
it adds no agent files, editor configuration, or Git hooks.

As checked on 2026-11-08, npm audit reports zero vulnerabilities after the upgrade.
The foundation source retains its own dependency choices; this app's tooling
upgrade does not modify the starter or the WavyKit packages.
