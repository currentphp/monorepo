# Pages Trial

A disposable Laravel 13 / Inertia 3 / React consumer for `wavykit/pages`.
Created from App Foundation `v0.1.0` (commit `29dab9eb1f186fd6a214364baa03d23d18fac39d`).
The foundation and Pages retain their independent repositories.

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

Pages is a local Composer path dependency with symlinking enabled. The Compose
services mount its source read-only at the matching relative path. Development
changes to Pages are immediately available here; no package source is copied into
the app. Composer installation requires that local source checkout. This trial is
intentionally not a standalone distributable application.

The app uses port 8081, Vite 5174, PostgreSQL 5434, and Mailpit 8026. Its
`pages-trial` Compose project and database volume are independent of other apps.
The application keeps its Composer and npm lockfiles.

## Trial pages

Open `/projects`, then Apollo or Artemis and its edit-page link. The projects are
in-memory examples; the edit screen is read-only. Breadcrumbs resolve the bound
project name, Projects remains active in primary navigation, and the footer places
the same index under Resources with a different label. Guest and verified-user
account links follow the foundation's existing authentication state.

## Checks

```sh
./vendor/bin/sail composer ci:check
```

Tests use the foundation's dedicated `testing` PostgreSQL database. No migrations,
seeds, or tests should point at another application's database. No CI or agent
scaffolding was installed, and nothing has been committed or published.

## Dependency status

The trial updates vulnerable PHP packages within the foundation's constraints;
Composer audit is clear after that update. Compatible npm fixes were also applied.
Seven frontend-tooling advisories remain in the inherited Vite+ 0.3 dependency
chain (four moderate, three critical); npm reports that clearing them requires a
major Vite+ upgrade. No forced major upgrade was applied. Keep this a local trial
until the tooling update is reviewed separately.
