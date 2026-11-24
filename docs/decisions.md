# Direction and decisions

Decisions about what Current contains and how it grows. Recorded 2026-11-09 by
the maintainers. Plans below are intentions, not release promises; the
[monorepo guide](monorepo.md) owns layout, versioning, and the release process.

## What Current is for

Laravel has primitives for handling requests, but none for the experience
around them: where users are, how they move around, what they are looking at,
and what they can do. That knowledge usually ends up scattered across route
names, breadcrumb arrays, menu configuration, page headings, and frontend
code, and drifts. Current gives an application one description of its
experience that every part of the interface reads.

**Current describes; Laravel and the application execute.** Current records
identities, relationships, labels, and availability. Routes, controllers,
jobs, authorization, and rendering stay with the application. Hiding a menu
entry, for example, is presentation and never replaces an authorization check.

## Package boundaries

**One core package.** `current/current` contains everything that needs only
Laravel, organized by namespace:

- `Current\Pages\`: places in the application, with stable IDs, hierarchy,
  route association, labels, and icons;
- `Current\Navigation\`: breadcrumbs and menus, read from those places;
- `Current\Context`: the request context passed to every callback.

**Integrations are separate packages.** A package exists when it brings a
third-party dependency that an application might not want, such as Inertia or
a search engine, or serves a different kind of adopter. Concepts that share
the core's dependencies stay in the core as namespaces.

**Laravel at the edges.** Current targets Laravel only. Builders,
definitions, and registries make no Laravel calls; readers, the request
context, and service providers hold the Laravel-specific code. The full design
guidelines are in [CONTRIBUTING.md](../CONTRIBUTING.md#design-guidelines).

**Each package owns its own tools.** Inspection commands and test assertions
ship with the package whose concepts they check, not in a shared testing or
development package.

**History.** Pages and menus were first developed as two packages,
`wavykit/pages` and `wavykit/navigation`, and merged into `wavykit/core`, now `current/current`, before
`v0.1.0`. Neither was published, so no installed application is affected.

## Supported versions

Packages support Laravel 13 and PHP 8.3 or later. Support for earlier Laravel
versions is not planned and will be considered only if adopters ask for it;
widening support means adding those versions to CI.

## Plan

### v0.1

The first release, `v0.1.0`, published `current/current` on 2026-11-09 after an
API review. The review decided every public class, method, and output shape
deliberately, such as making breadcrumbs and menu items the same kind of
object, so that the release marks an API the maintainers intend to keep. Releases stay on 0.x, and
changes remain possible but intentional;
[CONTRIBUTING.md](../CONTRIBUTING.md#public-api) defines what counts as public
API and how breaking changes are flagged. Release preparation is tracked in each
package's release checklist.

Pull requests are limited to maintainers until the API is settled; issues and
feedback are open.

### v0.2

Released 2026-11-16 as `wavykit/core` 0.2.0 and the first release of
`wavykit/inertia`, 0.1.0, before the rename to Current (packages are versioned
independently since then; the names below are today's):

- **`current/inertia`**, a new package that shares breadcrumbs, the current
  page, and menus with Inertia. Applications opt in with one explicit call in
  their `HandleInertiaRequests::share()` method, so they choose the prop name
  and which menus to send; the prop resolves at render time. The package ships
  hand-written TypeScript types for the shared data.
- **Inspection commands and test assertions** in the core: `current:pages`,
  `current:menus`, and `current:check`, and a test trait asserting the current
  page, breadcrumbs, and menu contents for a request.
- **A read-only `Current` facade** for reading the current request, and an
  explicit public API: everything else is marked `@internal`, as
  [CONTRIBUTING.md](../CONTRIBUTING.md#public-api) describes.

### v0.3

Released 2026-11-16 as `current/current` 0.3.0 and `current/inertia` 0.2.0,
the first releases under the name Current (formerly WavyKit):

- **Subjects** in the core (`Current\Subjects\`): the resources users look at
  and act on, such as a project or an invoice, declared once with their own
  identity and linked to the pages that present them. A subject is the same
  thing in a breadcrumb, a link from anywhere, and the current request
  (`Current::subject()`). Designed and prototyped in the playground before the
  API was fixed.
- **The current subject in Inertia**, shared by `CurrentProps` (formerly
  `WavyKitProps`) with its TypeScript type.
- **The rename** to Current, and faster derived menus on large page trees.

### v0.4

Released 2026-11-17 as `current/current` 0.4.0 and `current/inertia` 0.3.0,
finishing the description of each place and closing a menu gap:

- **Titles and descriptions** for pages: a title for headings and the document
  title, falling back to the label, and an optional description. Subjects get
  the same facts, and a page that presents a subject takes its title and
  description from it unless it has its own.
- **Request-time visibility for derived menus**, so a derived menu can hide a
  page from some users without becoming an explicit menu.

`current/inertia` releases alongside only to require core 0.4 and keep its
types matching. Integrations for Blade, Livewire, and Inertia are designed
together once the core primitives are complete.

### v0.5

Released 2026-11-17 as `current/current` 0.5.0 and `current/inertia` 0.4.0, the
last core primitive before the integrations are designed together:

- **Actions** (`Current\Actions\`): what users can do to a subject or on a
  page, described and never executed. Each action targets the application's
  own route; visibility and enabled state, including Gate abilities, decide
  what the readers return, and routes still authorize.

`current/inertia` follows with a release that only requires core 0.5.

### v0.6

Released 2026-11-23 as `current/current` 0.6.0 and `current/inertia` 0.5.0, the
integration wave. The core primitives are complete. The coordinated integration
design keeps Blade and Livewire guide-first, with application-owned rendering and
execution, and extends the Inertia adapter additively.

A local explicit experience reader preserves logical page context on transports
such as Livewire updates without replacing Laravel's actual request. Inertia's
independent presentation/action opt-ins keep its default payload unchanged.
One playground application hosts Inertia, Blade, Livewire, and JSON consumers
against shared projects, declarations, and authorization. Package-owned compiler
checks and the playground HTTP suite verify the described
contracts. The package READMEs and [Livewire guide](../packages/current/docs/livewire.md)
own exact behavior and tested limits; no Livewire runtime adapter or browser
navigation guarantee is implied.

The consumer is Current Examples: a curated overview with Projects and optional
session-only Onboarding. Rendering transports are comparisons within a use case.
A synthetic demo identity removes account setup; this is not an authentication
product. The application owns simulation state, validation, and authorization;
Current supplies its page, subject, navigation, and action descriptions. Canonical
usage and checks live in the [examples README](../apps/playground/README.md).

### Unreleased: v0.7

The Inertia adapter moves into `current/current` with the same class name and
output; `inertiajs/inertia-laravel` becomes optional (`suggest`, with a
`conflict` guard). `current/inertia` released in step with every core minor, so
a separate package cost a release each time without letting the two move
independently. Its last release, 0.6.0, contains no PHP and requires core 0.7,
so applications update without code changes. The TypeScript types and a small
`is()` helper that mirrors `Current::is()` move to the npm package
`@currentphp/client`, starting at 0.1.0. The PHP API only grows in 0.7.

### Later

These are candidates without a release target:

- menu entries that link outside the application;
- generated TypeScript types for an application's page IDs;
- a check that every named route has a page or is deliberately excluded;
- optional route and view declarations alongside page definitions.


## Not planned

The maintainers are not shipping a content management system, an admin panel,
a breadcrumbs-only package, or support for frameworks other than Laravel as
official Current products. CMSs and admin panels are exactly the kind of thing
you can build with Current's components; they are just not on the maintainers'
roadmap. These are current decisions, and the maintainers may revisit them.
