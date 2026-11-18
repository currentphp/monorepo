# Changelog

## Unreleased

No changes yet.

## 0.3.0 - 2026-11-17

### Changed

- Requires `current/current` 0.4. The shared prop is unchanged: page and
  subject titles and descriptions are read on the server with
  `Current::title()` and `Current::description()`, and can be added to your
  own shared props.
- `current/inertia` is in maintenance: it follows each `current/current`
  release, and new fields wait until the Blade, Livewire, and Inertia
  integrations are designed together.

## 0.2.0 - 2026-11-16

The first release as `current/inertia`, continuing from `wavykit/inertia` 0.1.0.

### Breaking

- Renamed from WavyKit to Current. The package is `current/inertia`
  (previously `wavykit/inertia`, now abandoned), the namespace is `Current\`
  (previously `WavyKit\`), and the TypeScript types file is `current.d.ts`.
  Version numbers continue from `wavykit/inertia`.
- Renamed `WavyKitProps` to `CurrentProps` and the TypeScript type `WavyKit`
  to `Current`: the prop is the serialized form of the `Current` facade. The
  documentation now shares it under the key `current`; applications still
  choose their own key.

### Added

- `subject` in the shared prop: the current subject from `current/current`'s
  `Current::subject()` (`{ kind, id, label, url }` or null), with a `Subject`
  TypeScript type. Requires `current/current` 0.3.

### Developer Experience

- A test checks the shared prop's JSON against `resources/types/current.d.ts`,
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
