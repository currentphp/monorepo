# WavyKit Playground

A Laravel 13 / Inertia 3 / React app for exercising every WavyKit component from
this monorepo. Created from App Foundation `v0.1.0` (commit
`29dab9eb1f186fd6a214364baa03d23d18fac39d`).

## Components

| Package and area                      | Registered in                                  | Demonstrated by                                                                               |
| ------------------------------------- | ---------------------------------------------- | --------------------------------------------------------------------------------------------- |
| `wavykit/core`: pages                 | `WavyKitServiceProvider::registerPages()`      | Page identity and resource-aware labels on every page; the projects demo.                     |
| `wavykit/core`: breadcrumbs and menus | `WavyKitServiceProvider::registerNavigation()` | Breadcrumbs, and primary and footer menus with `except()` and user visibility, on every page. |
| `wavykit/inertia`                     | `HandleInertiaRequests::share()`               | The `wavykit` prop on every page, typed by the package's `wavykit.d.ts`.                      |
| `wavykit/core`: subjects              | `WavyKitServiceProvider::registerSubjects()`   | Project and task subjects in breadcrumbs, task links, and `wavykit.subject`.                  |

[`HandleInertiaRequests`](app/Http/Middleware/HandleInertiaRequests.php) shares
component output with every Inertia page as the `wavykit` prop, through
`WavyKitProps` from `wavykit/inertia`. Its TypeScript types come from the
package, mapped to `@wavykit/inertia` in `tsconfig.json` and declared in
[`resources/js/types/global.d.ts`](resources/js/types/global.d.ts); the
components in [`resources/js/components/wavykit/`](resources/js/components/wavykit/)
render it.
Tests live in [`tests/Feature/WavyKit/`](tests/Feature/WavyKit/), one file per
component, plus `SharedPropsTest.php` for pages outside the demos.

## Demo data

Demo content comes from JSON files in [`database/fixtures/`](database/fixtures/),
loaded with `App\Fixtures::load('<name>')`. Fixtures need no migrations or
seeding, and the app and tests read the same data. Edit
`database/fixtures/projects.json` to change the example projects. PostgreSQL
holds only the foundation's application data, such as users and sessions.

## Adding a component

1. Require it in `composer.json` at its development line (for a new package,
   `^0.1@dev`), and add it to the path repository's `versions` option at the
   same line (`0.1.x-dev`). The repository already reads
   every folder in the monorepo's `packages/`.
2. Add a `register<Component>()` method to `WavyKitServiceProvider` and call it
   from `boot()`.
3. Expose any request output through the `wavykit` prop in
   `HandleInertiaRequests`, or pass it from a demo controller.
4. Add `tests/Feature/WavyKit/<Component>Test.php`, fixtures if the demo needs
   data, and a row in the table above.

## Inspecting and testing

`php artisan wavykit:pages`, `wavykit:menus`, and `wavykit:check` list and
validate what `WavyKitServiceProvider` registers; add `--json` to the listings
for machine-readable output. Run them through Sail like any Artisan command.

The base `TestCase` uses `WavyKit\Testing\InteractsWithWavyKit`, so tests can
assert the current page, breadcrumbs, and menu visibility after a request, for
example `$this->assertMenuMissing('footer', 'profile')`.

## Local setup

Run from this application directory with PHP 8.5 and Node 24.21.0 (pinned in
`mise.toml`; `mise install` provides them). No Docker or database server is
needed: the app uses SQLite in `database/database.sqlite`, and mail goes to the
log.

```sh
composer setup   # install, create .env and the SQLite file, migrate, build
composer dev     # serve at http://localhost:8000 with Vite
```

WavyKit packages are local Composer path dependencies, symlinked from the
monorepo's `packages/` folder at each package's development line (core
`0.3.x-dev`, inertia `0.2.x-dev`), so package changes are
immediately available here and no package source is copied into the app.
This app is not a standalone distributable application. The application keeps
its Composer and npm lockfiles.

## Demo pages

Open `/projects`, then Apollo or Artemis and its edit-page link. The edit screen
is read-only. Breadcrumbs resolve the bound project name. The primary menu is
derived from Projects and excludes the project pages with `except()`, so
Projects stays active without listing them. The footer places the same index
under Resources with a different label. Guest and verified-user account links
follow the foundation's existing authentication state.

### Subjects

Projects and their tasks are subjects (`WavyKit\Subjects`), described once in
`registerSubjects()` and read by breadcrumbs (Projects › Apollo › Tasks ›
Fix login, with no per-page label callback), by canonical links (the task list
on a project page), and by `wavykit.subject` in the Inertia prop. Open
`/projects/42/tasks/1`, or run `php artisan wavykit:subjects`.

## Checks

```sh
composer check
```

Tests use an in-memory SQLite database, so they never touch
`database/database.sqlite`. The monorepo CI checks the app independently of the
packages.

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
