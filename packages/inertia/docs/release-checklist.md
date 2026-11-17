# First release checklist

> Historical: this checklist predates the 2026-11-16 rename from WavyKit to
> Current and keeps the names used at the time.

Working checklist for the first public release of `wavykit/inertia`,
`v0.1.0`, versioned independently of `wavykit/core` (it requires core `^0.2`).
Checked items record completed work.

## Local readiness

- [x] Keep scope to sharing `wavykit/core` output with Inertia; the application owns the prop name, which menus to send, and rendering.
- [x] Read only through the supported `WavyKit\Current` API.
- [x] Document installation, sharing the prop, its shape, evaluation, and the TypeScript types in the README.
- [x] Ship hand-written TypeScript types in `resources/types/wavykit.d.ts`, outside the export-ignored paths.
- [x] Include MIT license, matching Composer identity, and a dated changelog entry (`0.1.0 - 2026-11-16`).
- [x] Pass the root `composer check` and CI on PHP 8.3–8.5 with lowest and stable resolutions.
- [x] Consume the package in the playground (`apps/playground`), with its checks passing.

## Repository and release preparation

- [x] Create the read-only `wavykit/inertia` split repository and add it to the split GitHub App (2026-11-13).
- [x] Confirm the split workflow mirrors `main` to `wavykit/inertia` (2026-11-14, `efb0218`).
- [x] Rebuild and inspect the final distribution, and smoke-test it in a fresh Laravel 13 application (2026-11-16, split `191b88bd`).

## Publication

- [x] Merge the reviewed release changes (PR #11, then PR #12 for the 0.1.0 version).
- [x] Remove the `v0.2.0` tag that the last lockstep release pushed to `wavykit/inertia` (never downloaded), so Packagist drops that version (2026-11-16).
- [x] Create and push the `inertia/v0.1.0` tag; confirm the split workflow pushes `v0.1.0` to `wavykit/inertia` only (2026-11-16, on `242eb68a`).
- [x] Register the `wavykit/inertia` split repository on Packagist, with its update webhook (2026-11-16).
- [x] Verify installation from the published version (2026-11-16: a Packagist-only install matched the reviewed build).

Repository creation, commits, pushes, tags, and publication are separate
actions requiring explicit authorization.
