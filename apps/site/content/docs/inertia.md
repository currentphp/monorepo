---
title: Inertia
description: Share the current page, subject, breadcrumbs, and menus with your React or Vue pages, with TypeScript types.
order: 3
---

`current/inertia` hands Current's description to your Inertia frontend as one shared prop, so components stop keeping their own breadcrumb arrays and navigation lists.

## Installation

```shell
composer require current/inertia
```

It requires `inertiajs/inertia-laravel` 3 and `current/current` 0.3.

## Sharing the prop

Add one entry to `share()` in your `HandleInertiaRequests` middleware, naming the menus every page needs:

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

- `pageId` is null, and `breadcrumbs` empty, on routes without a registered page. Menus still render, with no active items.
- `subject` is the [current subject](/docs/subjects#reading-subjects), or null.
- `menus` holds the menus you named, in that order. Unknown names raise `Current\Exceptions\ResolutionFailed`.

The prop resolves when the response renders, after route-model binding. A partial reload that leaves out its key doesn't resolve it at all. To change when it's sent, wrap it in Inertia's own prop types, for example `Inertia::defer(fn () => CurrentProps::make()->menus('primary'))`.

## TypeScript types

The package ships types in `vendor/current/inertia/resources/types/current.d.ts`. Map them to an import path in `tsconfig.json`:

```json
{
    "compilerOptions": {
        "paths": {
            "@current/inertia": ["./vendor/current/inertia/resources/types/current.d.ts"]
        }
    }
}
```

Then type the shared prop with your menu names:

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

The file also exports `Subject`, `Breadcrumb`, `MenuItem`, and `MenuItemKind` for your components.
