# Changelog

## Unreleased

No changes yet.

## 0.6.0 - 2026-11-23

### Upgrading

Finish registering subjects before reading pages. Page reads now validate
subject declarations, so registering a subject after a page has been read, or
an invalid subject declaration, fails on that read instead of going unnoticed.
Applications that register subjects in a service provider, with declarations
that subject and action reads already accept, need no changes.

### Breaking

- Page reads now finalize and validate subject declarations too, including reads
  of page IDs and explicit page titles. Complete subject registration before
  reading pages; late registration and invalid declarations previously missed
  by page readers now fail consistently with subject and action readers.

### Added

- Blade and Livewire rendering guides, backed by a playground HTTP
  suite using supported core calls. The Blade guide's example is rendered
  in the playground; native forms, escaping, authorization, and component
  lifecycle behavior are exercised without a core runtime Livewire dependency.

- `Current::forPage($pageId, $parameters)` returns a separate `Experience`
  reader for a registered page and server-resolved bindings, without changing
  ambient Current reads or Laravel's actual request. It supports the same
  page, subject, navigation, and action reads, with selective evaluation.
- `Current::subjectTitle()` and `Current::subjectDescription()` read the
  nearest presented subject's own presentation independently of page overrides,
  without resolving its URL or unrelated output. Both are available on explicit
  readers too; existing `SubjectItem` output stays unchanged.
- Explicit readers resolve menu/action callbacks against their local context
  and GET action current-state against their selected page's route and URL.
  The server supplies the user; outside HTTP the explicit user is null.

### Fixed

- Reject bound objects that do not match a page's declared subject class before
  resolving subject presentation or actions.
- Apply the same subject declaration checks through page, subject, and action
  readers, without evaluating unrelated callbacks.
- Identify the subject and canonical page when its destination URL cannot be generated.

### Developer Experience

- Centralize subject selection and presentation, destination URL generation,
  and required presentation-text validation internally. Existing supported
  symbols and serialized output shapes are retained; new reader methods are
  listed above.

## 0.5.0 - 2026-11-17

### Added

- Actions (`Current\Actions\`): describe what users can do to a subject
  kind (`Action::make()->for()`) or on a page (`->on()`), targeting the
  application's own route and HTTP method, with `label()`, `icon()`,
  `confirm()`, `destructive()`, `order()`, `visibleWhen()`, `enabledWhen()`
  (bool or `Response`), and `can()` for a Gate ability. Current never runs
  them. `Current::actions()` returns the current subject's and page's visible
  actions, and `Actions::for($object)` one object's, as `ActionItem`
  (`{ id, name, label, url, method, icon, destructive, enabled, reason,
  confirm, current }`).
- `current:actions` (with `--json`), action checks in `current:check`, and
  `assertActionHas()`, `assertActionMissing()`, `assertActionEnabled()`, and
  `assertActionDisabled()` in `InteractsWithCurrent`.

### Changed

- `current:check` counts actions in its summary line.

## 0.4.0 - 2026-11-17

### Added

- Page titles and descriptions: `Page::title()` and `Page::description()`
  take a string or a `Context` callback. `Current::title()` returns the active
  page's title, falling back to its label; `Current::description()` returns
  its description or null, never the label. `Pages::title()` and
  `Pages::description()` resolve any page. Breadcrumbs and menus keep using
  labels.
- `current:pages` lists titles and descriptions, and `--json` adds `title`,
  `dynamicTitle`, `description`, and `dynamicDescription`.
- `assertTitle()` and `assertDescription()` in `InteractsWithCurrent`.
- Subject titles, descriptions, and icons: `Subject::title()`,
  `Subject::description()`, and `Subject::icon()`. A page that presents a
  subject takes its title, description, and icon unless it sets its own;
  breadcrumbs keep the subject's label. `Subjects::title()` and
  `Subjects::description()` resolve any object, and `Pages::icon()` reads a
  page's icon with that fallback. `current:subjects` shows which kinds have
  each. `SubjectItem` output is unchanged.

- Request-time visibility for derived menus: `DerivedMenu::exceptWhen($pageId,
  $callback)` leaves a page and its subtree out of that menu when the callback
  returns true for the request. `current:menus` lists these pages, and
  `--json` adds `exceptWhen` to derived menus.

### Changed

- `PageDefinition` has `title` and `description`, and `SubjectDefinition` has
  `title`, `description`, and `icon`. Empty title or description
  strings are rejected at registration.

## 0.3.0 - 2026-11-16

The first release as `current/current`, continuing from `wavykit/core` 0.2.0.

### Breaking

- Renamed from WavyKit to Current. The package is `current/current`
  (previously `wavykit/core`, now abandoned) and the namespace is `Current\`
  (previously `WavyKit\`). The class behind the facade is `Current\Experience`
  (previously `WavyKit\Current`); the service provider is
  `CurrentServiceProvider`, the test trait `InteractsWithCurrent`, the
  exception interface `CurrentException`, and the commands `current:*`.
  Version numbers continue from `wavykit/core`.

### Added

- Subjects (`Current\Subjects\`): declare kinds once with
  `Subject::make()->for()->label()->page()` in `SubjectRegistry`; pages
  present them with `Page::presents()` and take the subject's label unless
  they have their own. `Current::subject()` returns the active page's subject,
  or the nearest ancestor's; `Subjects::of($object)` resolves any object's
  subject and canonical URL, also outside requests. Output is `SubjectItem`
  (`{ kind, id, label, url }`, identity `kind:id`). Registration is validated:
  overlapping classes, canonical pages that do not present their kind, unknown
  kinds, unroutable classes, and routes missing the presented parameter.
- `current:subjects` (with `--json`), subject checks in `current:check`, a
  `presents` detail in `current:pages`, and `assertCurrentSubject()` in
  `InteractsWithCurrent`.

### Changed

- Derived menus and page lookups no longer slow down quadratically on large
  page trees: the page registry indexes children and routes once when it is
  frozen (a 1,000-page menu went from about 36 ms to 0.1 ms per render).
- `PageDefinition` has `presents`, `presentsParameter`, and `labelled`.
  Internal: `Pages` resolves URLs through one builder shared with subjects, and
  label callbacks share one resolver for pages, menus, and subjects.

## 0.2.0 - 2026-11-16

### Added

- `WavyKit\Facades\Current`, a read-only facade for the current request:
  `page()`, `pageId()`, `is(...$patterns)`, `breadcrumbs()`, `menu($name)`,
  `context()`, and `user()`. The `WavyKit\Current` class behind it can be
  injected instead; it holds no request state, so it is safe in queue workers
  and Octane.
- `wavykit:pages`, `wavykit:menus`, and `wavykit:check` Artisan commands that
  list and validate registered pages and menus without running callbacks, with
  `--json` output for the listings. `wavykit:check` exits 1 on any problem, for
  CI. The core now requires `illuminate/console`.
- `WavyKit\Testing\InteractsWithWavyKit`, a test trait with
  `assertCurrentPage()`, `assertBreadcrumbs()`, `assertMenuHas()`,
  `assertMenuMissing()`, and `assertMenuActive()` for the last test request.

### Breaking

- Marked as `@internal`, so they may change in any release:
  `PageRegistry::forRoute()`, `trail()`, and `children()` (use
  `Pages::current()` and `Pages::activeTrail()`); the `Pages`, `Navigation`,
  and `MenuRegistry` constructors (resolve them from the container);
  `CoreServiceProvider::register()`; and `MenuEntryDefinition` (read resolved
  `MenuItem` output). Behavior is unchanged.

### Developer Experience

- A test lists every supported public symbol, so changes to the public API are
  deliberate; the policy is in the monorepo's CONTRIBUTING.md.
- Architecture tests keep Laravel at the edges (readers, the request context,
  and the service provider) and require strict types.

## 0.1.0 - 2026-11-09

First release.

### Added

- Laravel 13 page registration with stable IDs and existing named-route associations.
- Nested and separately contributed definitions with parent relationships and sibling ordering.
- Resource-aware labels, explicit route-parameter mapping with `routeParameters()`, and a shared request context (`WavyKit\Context`) with the active page, bound parameters, and current user.
- Breadcrumb trails as `Breadcrumb` objects, with route-less ancestors, current-page markers, icons, and URLs kept for the current page.
- Optional page icons as presentation identifiers.
- Named menus derived from the page hierarchy, with authored root order and per-menu `except()` exclusions.
- Explicitly arranged menus built from `MenuEntry` page placements and presentation groups, with label/icon overrides and authored order; `define()` returns a `DefinedMenu` for future menu-level options.
- Current and active states from page ancestry and explicit menu nesting.
- Request-time `visibleWhen()` predicates with access to the current user.
- `MenuItem` output containing resolved data only, with a `MenuItemKind` enum that serializes to `page` or `group`.
- Public active-trail, label, and URL resolution for packages that build on page output.
- `InvalidDefinition` and `ResolutionFailed` exceptions, both implementing `WavyKitException` and extending `LogicException`.
- Validation for duplicate IDs, missing or cyclic parents, conflicting parents, duplicate route associations, unknown routes, duplicate menu names, unknown page references and exclusions, and duplicate page or group placements.

### Fixed

- Resolve the active page from the current request to avoid stale page context after unmatched requests on older Laravel 13 versions.

### Developer Experience

- Public API docblocks describe lifecycle, request callbacks, exclusions, visibility, errors, and resolved output.
- Track first-release readiness in a package-owned checklist.
- Composer validation, Pint formatting, maximum-level PHPStan analysis, and Pest/Testbench tests, run from the WavyKit monorepo root.
- Integration coverage for fresh-process route caching and resource/user request isolation.
