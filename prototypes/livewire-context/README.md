# Livewire page-context proof — throwaway fixture

Question: can Current describe a stable page experience through real Livewire
initial renders, parent updates, and child-only updates without replacing the
actual HTTP request or retaining stale presentation?

**Result, 2026-11-22:** the fixture now uses only Current's supported public
interface. `Current::forPage()` preserves page, subject presentation, breadcrumbs,
menus, and subject/page actions without replacing Laravel's actual request.
The initial spike had 27 assertions and a partial internal reader; the public
implementation now passes 32 real-HTTP assertions. This is evidence for guide-first
Livewire usage, not a released integration or browser-navigation support.

Run from this directory:

```sh
python3 run.py
```

Requires PHP, Composer, Python 3, PHP SQLite, and an unused loopback port 18765.
The runner installs this isolated application's dependencies when absent, starts
its own PHP HTTP server, exercises signed Livewire snapshots with session cookies
and CSRF tokens, and stops its server even when a scenario fails. It resets only
`.runtime/prototype.sqlite`, containing synthetic projects and permissions.
It does not start a browser or change Current's runtime packages or playground.

The manifest pins the tested Laravel and Livewire versions. Vendor, generated
bootstrap files, local lockfile, database, HTTP captures, and session files are
ignored. The repeatable scenario summary is in [RESULTS.md](RESULTS.md); verbose
machine evidence is regenerated under `.runtime/` and printed by the runner.

## Tested source

- Current core source: `1fdce8726b191f0568f08aede8e816842b6a2294`
  (`feat: add explicit page experience readers`). The fixture consumes that
  local core through its Composer path repository.
- Laravel 13.42.0; Livewire 4.4.9; PHP 8.5.13; Python standard-library HTTP client.
- Livewire 4.3.0 was the initial research candidate, but Composer blocked it for
  advisories. The isolated resolution selected 4.4.9; the final lock refresh
  reported no security vulnerability advisories. No advisory policy was disabled.
- This is one tested combination, not a PHP/Laravel/Livewire support matrix.

## What the HTTP evidence establishes

Initial `/projects/42/edit` reads resolve `projects.edit` and subject `project:42`.
The following real update request resolves Livewire's named update endpoint.
Ambient Current then has no page, subject, or current actions. A menu predicate
that requires the active page also changes its result.

[PageReader](src/PageReader.php) is now an application-owned projection of the
public `Current::forPage()` reader, using freshly loaded server-owned models.
It does not construct Context, call internal methods, or reproduce resolution.
The [core README](../../packages/current/README.md#explicit-page-experiences)
owns the supported contract and lifetime rules.

Initial ambient and explicit menus/actions are identical. On real parent and
child-only updates, explicit menus retain predicates and active/current states;
subject and page actions retain order, bound callback context, disabled reasons,
and GET current-state against the logical page destination. Two local readers
remain independent, including in mixed-page bundled requests.

Existing public `Subjects::title($object)` and `Actions::for($object)` remain a
separate object-reader path; the latter still gives callbacks ambient context.
Page-aware output uses the explicit experience.

The application persists only a locked subject ID. Each render and mutation
reloads the subject; mutations reauthorize. Persistent `can:view,project` route
middleware also rejects revoked page access on parent and child-only updates.
Deleted route subjects return 404. Tampered signed context, attempts to update a
locked ID, and invalid CSRF tokens fail through their actual framework paths.
The locked-property and corrupt-snapshot failures are framework exceptions, not
claims of a friendly adopter-facing error page.

## A separate nested-rendering limit

Fresh server data does not imply that every existing component rerenders.
A successful child mutation updates the child's own rendered presentation, while
its parent does not render. The parent sees the new subject description when
explicitly refreshed. Guides must explain application-owned refresh/event
coordination for page-shell headings, breadcrumbs, menus, and action controls.
The proof does not validate browser event wiring or document-title updates.

## Implemented public interface — not released

```php
// Server-authored page ID and server-resolved models; never a client-trusted map.
$experience = Current::forPage('projects.edit', ['project' => $project]);

$experience->title();
$experience->subjectTitle();
$experience->breadcrumbs();
$experience->menu('primary');
$experience->actions();
```

The reader exposes the same page, subject-presentation, navigation, and action
meaning as ambient Current through shared core resolution. It changes neither
ambient reads nor the request. The server supplies the user, or null outside
HTTP. Construction validates page/subject structure and parameter names without
running presentation callbacks; requested reads validate needed bindings/URLs.
Models remain references: the application loads/authorizes fresh objects and
recreates readers after changes. Current does not load or refresh models.

Menu and action callbacks receive this explicit context; ambient facade calls
inside a callback still describe the actual request. GET `ActionItem.current`
compares both route name and resolved URL with the logical page destination.
The HTTP proof now exercises these semantics entirely through supported calls.

**Recommendation:** keep guide-first Livewire. No private route-restoration
plumbing or dedicated adapter was needed for this public-only fixture. Broader
guide scenarios still need their own coverage; the fixture is not a claim of
universal lifecycle support.

## Static boundary check

From this directory with the development root's installed tools:

```sh
../../vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M
```

The fixture passes level 7 with `internalTag` enabled and no ignores. A temporary
negative control calling the internal Context constructor from the Prototype
namespace was rejected with `method.internal`. Core's maximum-level analysis and
public-interface allowlist remain separate checks.

## Limits and next gate

Not tested: `wire:navigate`, browser DOM/event coordination, document metadata,
Octane or concurrent workers, authentication login/logout transitions, tenant or
scoped bindings, soft deletes, multiple named route parameters, retained model
properties instead of locked IDs, or a Livewire version matrix. Sequential and
bundled HTTP isolation are established for this fixture only. The synthetic user
middleware is intentionally not an authentication implementation.

The public core reader and subject-presentation methods are committed in the
revision above, with package-owned regression coverage and this public-only HTTP proof. General
Blade/Livewire guides, Inertia opt-ins, and playground adoption remain WK-048–050
work. No runtime Livewire dependency, adapter, release, publication, or site change
was added. This fixture and its results are retained for review alongside the core change.

## Primary framework sources

These explain the mechanism; the executable scenarios above establish the
observations for the tested versions:

- [Livewire 4.4.9 persistent middleware](https://github.com/livewire/livewire/blob/v4.4.9/src/Mechanisms/PersistentMiddleware/PersistentMiddleware.php)
- [Livewire 4.4.9 update handler](https://github.com/livewire/livewire/blob/v4.4.9/src/Mechanisms/HandleRequests/HandleRequests.php)
- [Livewire 4.4.9 locked properties](https://github.com/livewire/livewire/blob/v4.4.9/src/Features/SupportLockedProperties/SupportLockedProperties.php)
- [Livewire 4.4.9 nested components](https://github.com/livewire/livewire/blob/v4.4.9/src/Features/SupportNestingComponents/SupportNestingComponents.php)
