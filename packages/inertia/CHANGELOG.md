# Changelog

## Unreleased

### Added

- `subject` in the shared prop: the current subject from `wavykit/core`'s
  `Current::subject()` (`{ kind, id, label, url }` or null), with a `Subject`
  TypeScript type. Requires `wavykit/core` 0.3.

### Developer Experience

- A test checks the shared prop's JSON against `resources/types/wavykit.d.ts`,
  so a change to either fails until the other is updated.

## 0.1.0 - 2026-11-16

First release.

### Added

- `WavyKitProps`, an Inertia prop that shares the current page ID, breadcrumbs,
  and named menus from `wavykit/core` under a key the application chooses. It
  resolves at render time, after route-model binding, and is skipped by partial
  reloads that exclude it.
- Hand-written TypeScript types for the shared prop in
  `resources/types/wavykit.d.ts`, generic over menu names.
