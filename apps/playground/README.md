# Current Examples

A curated set of interactive examples for Current, built with Laravel 13,
Inertia 3, React, Blade, and Livewire. Created from App Foundation `v0.1.0` (commit
`29dab9eb1f186fd6a214364baa03d23d18fac39d`).

## Components

| Package and area                         | Registered in                                     | Demonstrated by                                                                          |
| ---------------------------------------- | ------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `current/current`: pages                 | `ExperienceServiceProvider::registerPages()`      | Page identity and resource-aware labels on every page; the projects demo.                |
| `current/current`: breadcrumbs and menus | `ExperienceServiceProvider::registerNavigation()` | Breadcrumbs, and primary and footer menus with `except()` and exclusions, on every page. |
| `current/current`: Inertia               | `HandleInertiaRequests::share()`                  | The `current` prop on every page, typed by `@currentphp/client`.                         |
| `current/current`: subjects              | `ExperienceServiceProvider::registerSubjects()`   | Project and task subjects in breadcrumbs, task links, and `current.subject`.             |
| `current/current`: actions               | `ExperienceServiceProvider::registerActions()`    | Contextual project/task actions, disabled reasons, and route authorization.              |

[`HandleInertiaRequests`](app/Http/Middleware/HandleInertiaRequests.php) shares
component output with every Inertia page as the `current` prop, through
`CurrentProps` from `current/current`. Its TypeScript types come from the
monorepo's client package, `packages/client`, mapped to `@currentphp/client` in
`tsconfig.json` and declared in
[`resources/js/types/global.d.ts`](resources/js/types/global.d.ts); the
components in [`resources/js/components/current/`](resources/js/components/current/)
render it.
Tests live in [`tests/Feature/Current/`](tests/Feature/Current/), one file per
component, plus `SharedPropsTest.php` for pages outside the demos.

## Demo data

Projects are seeded once by migration from
[`database/fixtures/projects.json`](database/fixtures/projects.json), then persisted
in SQLite alongside browser sessions. Renames are shared by every rendering
approach. Task examples are loaded read-only through `App\Fixtures` from
`database/fixtures/tasks.json`. Tests use their own databases and initial data.

## Adding a component

1. Require it in `composer.json` at its development line (for a new package,
   `^0.1@dev`), and add it to the path repository's `versions` option at the
   same line (`0.1.x-dev`). The repository already reads
   every folder in the monorepo's `packages/`.
2. Add a `register<Component>()` method to `ExperienceServiceProvider` and call it
   from `boot()`.
3. Expose any request output through the `current` prop in
   `HandleInertiaRequests`, or pass it from a demo controller.
4. Add `tests/Feature/Current/<Component>Test.php`, fixtures if the demo needs
   data, and a row in the table above.

## Inspecting and testing

`php artisan current:pages`, `current:menus`, and `current:check` list and
validate what `ExperienceServiceProvider` registers; add `--json` to the listings
for machine-readable output. Run them directly from this application directory.

The base `TestCase` uses `Current\Testing\InteractsWithCurrent`, so tests can
assert the current page, breadcrumbs, and menu visibility after a request, for
example `$this->assertMenuMissing('footer', 'profile')`.

## Local setup

Run from this application directory with PHP 8.5 and Node 24.21.0 (pinned in
`mise.toml`; `mise install` provides them). No Docker or database server is
needed for the app itself: it uses SQLite in `database/database.sqlite`.
No account, SMTP service, or email verification is needed. Every request uses a
synthetic demo identity. It exists to exercise Current's user-aware callbacks and
policies; it is not a real authentication system. The UI labels this as demo mode.

```sh
composer setup   # install, create .env and the SQLite file, migrate, build
composer dev     # serve at http://localhost:8000 with Vite
```

Open `/` to choose an example. Projects is a resource workspace; Onboarding is
an optional session-only signup simulation. Editable projects can be renamed immediately. A
protected task demonstrates disabled actions and server-side denial without
requiring an account. Existing starter migration history is retained; account
records are no longer used by the application.

Current packages are local Composer path dependencies, symlinked from the
monorepo's `packages/` folder at each package's development line (core
`0.6.x-dev`, inertia `0.5.x-dev`), so package changes are
immediately available here and no package source is copied into the app.
This app is not a standalone distributable application. The application keeps
its Composer and npm lockfiles.

## Curated examples

The overview at `/` separates use cases from rendering transports:

- **Projects** (`/projects`): resources, nested tasks, contextual actions,
  breadcrumbs, active menus, and fresh model data.
- **Simulated onboarding** (`/onboarding`): a profile/workspace/review flow that
  reveals navigation and actions as session state changes. Use the sample name
  and email; it creates no account, sends no email, and needs no credentials.
  Reset clears only this simulation's state. Projects remains independently
  accessible throughout.

Each example has Inertia, Blade, Livewire, and JSON views selected by
`?view=inertia|blade|livewire|json`. The switcher preserves the page and subject;
native links and form redirects preserve the renderer. Unknown choices return 404. The earlier `/api/projects/{project}/experience` remains a read-only alias
for the canonical project-detail JSON representation.

Onboarding state and validation belong to `OnboardingDemo`; the routes and
explicit Livewire operations call it. Current describes the changing title,
workspace subject, step menu, and gated actions. Request callbacks read fresh
session state, including after reset. Demo sessions are isolated by cookies.
The shared `Rendering` enum and `ExperienceData` projection serve both examples.

The UI shares `resources/css/playground.css` and a common shell. Titles read
Laravel's runtime app name. The repository path and Composer application ID stay
`apps/playground` and `current/playground` for contributor command compatibility.
Projects persist in the database; task mutations remain labeled read-only demos.

`composer check` includes `composer test:http`: real initial and subsequent
Livewire HTTP requests, signed snapshots, CSRF, freshness, isolation, authorization,
escaping, Blade rendering, JSON, and native form redirects. The Python runner uses
its own disposable `tests/http/.runtime/playground.sqlite`, not the database you
browse, and exercises the same synthetic identity as the browser UI. Run checks
with PHP SQLite, Python 3, and installed application dependencies. It refuses an
occupied port 18765 and stops its own server. Actual versions and results are
written to the ignored runtime folder. Browser event delivery, `wire:navigate`,
and coordinated parent-shell refresh remain unverified.

## Cross-renderer parity

Inertia, Blade, and Livewire share a user-facing markup contract: the same shell,
landmarks, content order, tags, classes, links, action controls, labeled fields,
validation messages, disabled reasons, pending controls, and feedback focus.
Project-specific pages and actions enforce the same view policy across every
renderer. Livewire's signed-snapshot probes are hidden test instrumentation.

`npm run test:parity` builds current assets, starts the actual app with an isolated
SQLite database, and runs pinned Chromium through Playwright. Install its browser
explicitly with `npx playwright install chromium` after `npm ci`.

The maintained browser suite compares the full semantic DOM and screenshots for
18 page/state scenarios at desktop and mobile widths. Each case generates its
Inertia reference and compares Blade/Livewire against it with zero differing
pixels after normalizing only the selected-renderer marker. DOM comparisons retain
all user-facing content and attributes; they normalize whitespace, renderer query
parameters, framework bindings, and CSRF/method fields. Separate assertions verify
the correct selected marker. No renderer navigation or content is masked.

Interaction tests cover keyboard order, validation focus, pending read-only fields
and disabled submit controls, rename validation recovery, fresh document titles,
successful rename feedback, confirmation cancellation,
onboarding completion, and reset. Native full-page navigation, Inertia requests,
and Livewire operations retain their own transport mechanisms. Runtime references,
diffs, and failure traces live under ignored `tests/browser/.runtime/`. Open a
failure trace with `npx playwright show-trace <trace.zip>`.

The normal `composer check` includes the browser gate; its fresh asset build also
supplies the following PHP and real-HTTP checks. The existing application CI
installs Chromium explicitly before running the same gate. Tests never install
packages or browsers implicitly.

## Checks

The app imports its action type from `@currentphp/client`, representing all core HTTP methods. Its Inertia navigation
buttons omit `HEAD` actions, which have no page response; the installed Inertia
transport supports GET, POST, PUT, PATCH, and DELETE. Other execution behavior
belongs to the application.

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
upgrade does not modify the starter or the Current packages.
