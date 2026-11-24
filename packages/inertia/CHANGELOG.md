# Changelog

## Unreleased

No changes yet.

## 0.6.0 - 2026-11-23

### Removed

- All PHP. `Current\Inertia\CurrentProps` is part of `current/current` 0.7
  with the same class name. This release requires `current/current` `^0.7` and
  `inertiajs/inertia-laravel` `^3.0`, so applications update without code
  changes.

### Changed

- The type declarations stay at `resources/types/current.d.ts`, frozen at their
  0.5 contents. New types are published on npm as `@currentphp/client`.

## 0.5.0 - 2026-11-23

### Added

- Independent `CurrentProps::pagePresentation()`, `subjectPresentation()`, and
  `actions()` opt-ins. The default four-field prop and evaluation timing remain
  unchanged. Requested fields preserve core semantics, nullability, errors,
  partial reload exclusion, and deferred-prop behavior.
- `Current<MenuName, Features>` types with an optional feature-selection generic;
  existing single-generic uses keep their default shape. Export action and
  presentation types matching selected serialized output.
- Package-owned TypeScript compiler checks against real PHP JSON for all eight
  opt-in combinations, with strict and negative type assertions.

### Changed

- Requires `current/current` `^0.6`, which contains the subject-presentation
  readers.
- End maintenance-only behavior after the coordinated integration design review.

## 0.4.0 - 2026-11-17

### Changed

- Requires `current/current` 0.5. The shared prop is unchanged: actions are
  read on the server with `Current::actions()` and can be added to your own
  shared props. The package remains in maintenance until the Blade,
  Livewire, and Inertia integrations are designed together.

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
