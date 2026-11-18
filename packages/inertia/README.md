# Current for Inertia

Share Current's page ID, subject, breadcrumbs, and menus with every Inertia page, with
TypeScript types for the shared data.

[`current/current`](https://github.com/currentphp/monorepo/tree/main/packages/current)
describes your application's pages once; this package hands that description
to your React or Vue frontend as one shared prop, so components stop keeping
their own breadcrumb arrays and navigation lists.

Package: `current/inertia`. Namespace: `Current\Inertia\`. Current is a Wavy
project. Version 0.1.0 was released as `wavykit/inertia`; the first release as
`current/inertia` is 0.2.0. Version 0.3.0 is for `current/current` 0.4. While
Current is on 0.x, minor releases may change the public API.

**Maintenance:** `current/inertia` follows each `current/current` release, and
new fields wait until the Blade, Livewire, and Inertia integrations are
designed together. Until then, share anything else the core offers, such as
`Current::title()`, through your own props.

## Requirements

PHP 8.3+, Laravel 13, `inertiajs/inertia-laravel` 3, and `current/current` 0.4.

## Installation

```sh
composer require current/inertia
```

Register pages and menus with `current/current` as its README describes.

## Sharing the prop

Add one entry to `share()` in your `HandleInertiaRequests` middleware, under
any key you like, naming the menus every page needs:

```php
use Current\Inertia\CurrentProps;

public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'current' => CurrentProps::make()->menus('primary', 'footer'),
    ];
}
```

Every page then receives:

```json
{
    "pageId": "projects.show",
    "subject": { "kind": "project", "id": "42", "label": "Apollo", "url": "https://example.test/projects/42" },
    "breadcrumbs": [
        { "pageId": "projects", "label": "Projects", "url": "https://example.test/projects", "current": false, "icon": null },
        { "pageId": "projects.show", "label": "Apollo", "url": "https://example.test/projects/42", "current": true, "icon": null }
    ],
    "menus": {
        "primary": [
            { "pageId": "projects", "label": "Projects", "url": "https://example.test/projects", "current": false, "active": true, "children": [], "icon": "folder", "kind": "page" }
        ],
        "footer": []
    }
}
```

- `pageId` is null, and `breadcrumbs` empty, on routes without a registered
  page. Menus still render, with no active items.
- `subject` is the current subject from `current/current` (the active page's, or
  the nearest ancestor's that presents one), or null when there is none.
- `menus` holds the menus passed to `menus()`, in that order. With no
  `menus()` call it is an empty object. Unknown menu names raise
  `Current\Exceptions\ResolutionFailed` when the prop resolves.
- Labels, URLs, and current and active states follow `current/current`'s rules.

The prop resolves when the response renders, after route-model binding, so
labels can use bound models. A partial reload that leaves out its key does not
resolve it at all. To change when it is sent, wrap it in Inertia's own prop
types, for example `Inertia::defer(fn () => CurrentProps::make()->menus('primary'))`.

## TypeScript types

The package ships hand-written types in
`vendor/current/inertia/resources/types/current.d.ts`, versioned with the PHP
output. Map them to an import path in `tsconfig.json`:

```json
{
    "compilerOptions": {
        "paths": {
            "@current/inertia": ["./vendor/current/inertia/resources/types/current.d.ts"]
        }
    }
}
```

Then type the shared prop, passing your menu names:

```ts
import type { Current } from '@current/inertia';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            current: Current<'primary' | 'footer'>;
        };
    }
}
```

The file also exports `Subject`, `Breadcrumb`, `MenuItem`, and `MenuItemKind` for your
components.

## Development

Development, checks, and releases run from the Current monorepo; see its
`docs/monorepo.md`. This repository is a read-only split.

See [the changelog](CHANGELOG.md) for changes.
