# Pages Trial

A disposable Laravel 13 / Inertia 3 / React consumer for `wavykit/pages` and
`wavykit/navigation`.
Created from App Foundation `v0.1.0` (commit `29dab9eb1f186fd6a214364baa03d23d18fac39d`).
The foundation and the WavyKit monorepo retain their independent repositories.

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

Pages and Navigation are local Composer path dependencies, symlinked from the
WavyKit monorepo's `packages/` folder at version `0.1.x-dev`. The Compose
services mount that folder read-only at the matching relative path. Development
changes to either package are immediately available here; no package source is
copied into the app. Composer installation requires that local source checkout. This trial is
intentionally not a standalone distributable application.

The app uses port 8081, Vite 5174, PostgreSQL 5434, and Mailpit 8026. Its
`pages-trial` Compose project and database volume are independent of other apps.
The application keeps its Composer and npm lockfiles.

## Trial pages

Open `/projects`, then Apollo or Artemis and its edit-page link. The projects are
in-memory examples; the edit screen is read-only. Breadcrumbs resolve the bound
project name. The primary menu is derived from Projects and excludes the project
pages with `except()`, so Projects stays active without listing them. The footer
places the same index under Resources with a different label. Guest and verified-user
account links follow the foundation's existing authentication state.

## Checks

```sh
./vendor/bin/sail composer ci:check
```

Tests use the foundation's dedicated `testing` PostgreSQL database. No migrations,
seeds, or tests should point at another application's database. No CI or agent
scaffolding was installed.

## Dependency status

The trial updates vulnerable PHP packages within the foundation's constraints.
The frontend uses Vite+ 1.5.0 with its matching `vite` alias and npm override to
`@voidzero-dev/vite-plus-core@1.5.0`, following the
[documented upgrade path](https://viteplus.dev/guide/upgrade-project).
The migration preserves npm, existing lint/format rules, and the PHP test suite;
it adds no agent files, editor configuration, or Git hooks.

As checked on 2026-11-08, npm audit reports zero vulnerabilities after the upgrade.
The foundation source retains its own dependency choices; this trial's tooling
upgrade does not modify the starter or the WavyKit packages.
