# Changelog

## Unreleased

No changes yet.

## 0.1.0 - 2026-11-23

### Added

- First release. Types for Current's shared experience data, previously shipped
  as `current/inertia`'s `resources/types/current.d.ts`, with the same names and
  shapes.
- `is(current, ...patterns)`, which matches the current page ID exactly as PHP's
  `Current::is()` does, verified against the PHP implementation on a shared case
  table.
