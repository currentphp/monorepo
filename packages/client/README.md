# @currentphp/client

TypeScript types and small helpers for the experience data that
[Current](https://github.com/currentphp/monorepo) shares with your frontend:
the Inertia prop from `Current\Inertia\CurrentProps`, or the same data as JSON.

```sh
npm install @currentphp/client
```

ES modules only, with no dependencies and no side effects. Type-only imports
add no JavaScript to your bundle.

## Types

Type the shared Inertia prop with your menu names and the PHP opt-ins you
selected:

```ts
import type { Current } from '@currentphp/client';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            current: Current<'primary' | 'footer', 'pagePresentation' | 'subjectPresentation' | 'actions'>;
        };
    }
}
```

The package also exports `Subject`, `Breadcrumb`, `MenuItem`, `MenuItemKind`,
`Action`, `CurrentFeature`, `PagePresentation`, `SubjectPresentation`, and
`HasPageId`. See the [Inertia guide](https://github.com/currentphp/monorepo/blob/main/packages/current/docs/inertia.md)
for what each opt-in adds.

## is()

`is(current, ...patterns)` tells you whether the current page matches any
pattern, exactly as PHP's `Current::is()` decides:

```ts
import { is } from '@currentphp/client';

is(current, 'projects.*'); // projects.show, projects.tasks.show, ...
is(current, 'projects.edit', 'projects.create');
```

`*` matches any characters, dots included. Matching is anchored at both ends and
case-sensitive. No page, or no patterns, never matches. It accepts anything with
a `pageId`, such as the Inertia prop or the JSON output.

## Compatibility

The client versions independently of the PHP packages. Use the client version
listed for your `current/current` minor version.

| Client | `current/current` |
| --- | --- |
| 0.1 | 0.7 |

While Current is on 0.x, minor releases may change the public API.

## Development

From the monorepo, `composer test:types` writes the type cases and the `is()`
parity table from the PHP tests, then runs this package's tests:
`tsc` compiles every type case, and Node's test runner compares `is()` with PHP
on every parity case and checks the compatibility table. `npm run build` writes
`dist/`. See the monorepo's `CONTRIBUTING.md` and `docs/monorepo.md`.

See [the changelog](CHANGELOG.md) for changes.
