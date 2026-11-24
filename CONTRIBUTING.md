# Contributing to Current

**Current is not accepting pull requests yet.** The public API is still being
finalized during the 0.x releases, so pull requests are limited to
maintainers for now. Feedback is welcome: please open an issue on this
repository with questions, bug reports, or how you would use the packages.
This section will change when contributions open.

Current is developed in this monorepo. Each package in `packages/` is published
through a read-only split repository, which closes pull requests automatically;
open issues here instead.

## Development

Requires PHP 8.3+ and Composer 2. From the repository root:

```sh
composer install
composer check            # every package
composer check current       # one package
composer format [package] # apply Pint formatting
composer format:check     # verify formatting only
composer analyse          # PHPStan only
composer test             # Pest only
composer audit            # known-vulnerability advisories (network)
```

`composer check` validates the manifests and shared dependency constraints, then
runs Pint, PHPStan at maximum level, and Pest in each package. CI runs the same
checks on PHP 8.3, 8.4, and 8.5 with lowest and stable dependency resolutions.
See [docs/monorepo.md](docs/monorepo.md) for the layout and release process.

## Complete integration checks

For the full local gate, install each owner's development dependencies explicitly:

```sh
composer install
npm ci --prefix packages/client
composer install --working-dir=apps/playground
npm ci --prefix apps/playground
npm exec --prefix apps/playground -- playwright install chromium
composer check:all
```

Requires the playground's PHP/Node toolchain and Python 3 with PHP SQLite for the
playground HTTP checks. Existing `composer check [package]` remains PHP-only. Individual
extra tasks are `composer test:types` and `composer check:playground`. Missing dependencies produce an actionable failure;
checks do not install or update them.

The app gate builds current assets before browser comparisons; it requires the
explicit Chromium install above. `npm run test:parity` in the app compares semantic
DOM, screenshots, and interaction states across the three UI renderers.

The complete gate covers compiler assertions for all Inertia opt-ins, real
Blade/Livewire HTTP scenarios, and playground adoption alongside PHP checks.
The playground owns the one application dependency lock and runs its HTTP checks
through `composer check` (or `composer test:http` alone). Its existing CI runs on
PHP 8.5; the separate PHP 8.3–8.5 package matrix is retained. Livewire is only an
application dependency. Browser event wiring and `wire:navigate` are not claimed.

## Design guidelines

1. **Describe, don't execute.** Current records identities, relationships,
   labels, and availability. Routes, controllers, jobs, authorization, and
   rendering stay with the application.
2. **Framework at the edges, plain PHP in the model.** Current targets Laravel
   only, but builders, definitions, and registries make no Laravel calls;
   Laravel-specific code lives in readers, the request context, and service
   providers. This keeps the model easy to test; it is not a portability
   promise.
3. **Fluent builders for declarations, immutable definitions after
   registration, side-effect-free readers.** Builder state is private; one
   name per concept across builders, definitions, and output.
4. **SOLID, applied pragmatically.** One responsibility per class: builders
   declare, registries store and validate, readers resolve. Depend on an
   abstraction only when a second implementation or a test seam exists.
5. **DRY for knowledge, not for shape.** Each rule has one source of truth.
   Similar-looking code is merged when a third case appears, not before.
6. **No speculative extension points.** No plugin systems, generic metadata
   bags, or events without a concrete consumer.
7. **Fail early and clearly.** Validate at registration; throw
   `InvalidDefinition` or `ResolutionFailed`; name the page, menu, or entry in
   every message.
8. **Every public addition is deliberate.** Each public class or method is
   documented, tested, and either supported or marked `@internal`.

## Public API

What the maintainers support, and what a release may change:

- **Supported:** every public class, method, property, and enum case in a
  package's `src/` that is not marked `@internal`, and the JSON shapes of output
  objects such as `Breadcrumb` and `MenuItem`. The core's
  `tests/Unit/PublicApiTest.php` lists the supported symbols, so any change to
  the list is deliberate and reviewed.
- **Internal:** anything marked `@internal`, such as constructors of objects
  the package creates, registry methods only readers use, and builder hooks.
  It may change in any release. PHPStan's `internalTag` feature toggle reports
  calls to it from outside the `Current` namespace.
- **While on 0.x:** a minor release (for example 0.1 to 0.2) may change or
  remove supported API; a patch release does not. Every such change is listed
  under **Breaking** in the package's CHANGELOG. From 1.0, Semantic Versioning
  applies.
- **Checks:** the API list and architecture tests run in `composer check`. The
  [backward-compatibility workflow](.github/workflows/backward-compatibility.yml)
  reports changes since the latest tag on each pull request, without blocking
  it while on 0.x.

## Pull requests

These rules apply to maintainers now, and to everyone once contributions open.

- Keep each pull request to one change, with tests for behavior changes.
- Keep formatting separate from behavior changes.
- Update the affected package's README and the Unreleased section of its
  CHANGELOG when public behavior changes.
- Use commit subjects of the form `type: short description`, for example
  `fix: keep ancestors active for excluded pages`.

## Security

Please do not report security vulnerabilities in public issues. Use GitHub's
private vulnerability reporting on this repository instead.

## License

Contributions are released under the [MIT License](LICENSE).
