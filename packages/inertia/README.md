# current/inertia (superseded)

Inertia support is part of [`current/current`](https://github.com/currentphp/monorepo/tree/main/packages/current)
from 0.7, as `Current\Inertia\CurrentProps` with the same class name. The
TypeScript types are published on npm as `@currentphp/client`.

This last release, 0.6.0, contains no PHP. It requires `current/current` 0.7 and
`inertiajs/inertia-laravel` 3, so `composer update` keeps existing applications
working without code changes. It keeps the type declarations at
`resources/types/current.d.ts`, unchanged from 0.5, so an existing `tsconfig`
alias still compiles; they will not be updated.

To finish moving, follow [Upgrading from current/inertia](https://github.com/currentphp/monorepo/blob/main/packages/current/docs/inertia.md#upgrading-from-currentinertia).

See [the changelog](CHANGELOG.md) for earlier releases.
