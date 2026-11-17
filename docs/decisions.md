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

Released 2026-11-16 as `current/current` 0.2.0 and the first release of
`current/inertia`, 0.1.0 (packages are versioned independently since then):

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

### After v0.2

**Subjects:** the resources users look at and act on, such as a project or an
invoice, with their own identity, linked to the pages that present them. A
subject would be the same thing in a breadcrumb, a search result, or a list of
available actions. Subjects will be designed and prototyped before any API is
fixed, and will live in the core under `Current\Subjects\`.

### Later

These are candidates without a release target:

- page titles and descriptions;
- menu entries that link outside the application;
- generated TypeScript types for an application's page IDs;
- a check that every named route has a page or is deliberately excluded;
- optional route and view declarations alongside page definitions.

Describing the actions available on a page or subject is a likely later
direction; it will be named and scoped when it is planned.

## Not planned

The maintainers are not shipping a content management system, an admin panel,
a breadcrumbs-only package, or support for frameworks other than Laravel as
official Current products. CMSs and admin panels are exactly the kind of thing
you can build with Current's components; they are just not on the maintainers'
roadmap. These are current decisions, and the maintainers may revisit them.
