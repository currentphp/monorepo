# Current for Inertia

Share Current's page ID, subject, breadcrumbs, and menus with every Inertia page, with
TypeScript types for the shared data.

[`current/current`](https://github.com/currentphp/monorepo/tree/main/packages/current)
describes your application's pages once; this package hands that description
to your React or Vue frontend as one shared prop, so components stop keeping
their own breadcrumb arrays and navigation lists.

Package: `current/inertia`. Namespace: `Current\Inertia\`. Version 0.1.0 was
released as `wavykit/inertia`; the first release as
`current/inertia` is 0.2.0. Version 0.4.0 is for `current/current` 0.5. While
Current is on 0.x, minor releases may change the public API.

**Unreleased integration wave:** this branch develops `current/inertia` 0.5
against core 0.6. The new opt-ins below are not in published 0.4.0, which continues
to require core 0.5. No tag or publication is created by moving development lines.

## Requirements

PHP 8.3+, Laravel 13, `inertiajs/inertia-laravel` 3, and core `^0.6` for this
unreleased development line. Core's Composer path repository supplies local 0.6
when working in the monorepo.

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

## Independent presentation and action opt-ins

The default four-field prop is unchanged. Select each additional group explicitly:

```php
'current' => CurrentProps::make()
    ->menus('primary', 'footer')
    ->pagePresentation()
    ->subjectPresentation()
    ->actions(),
```

- `pagePresentation()` adds top-level `title` and `description`, both nullable.
  On an unregistered route both are null. Page overrides and core fallbacks apply.
- `subjectPresentation()` adds the subject's own `title` (a title-or-label string)
  and nullable `description` inside the existing subject object. It preserves
  `subject: null` when there is no subject. These facts remain independent of
  page overrides and use the nearest presenting subject.
- `actions()` adds the ordered visible subject actions followed by page actions.
  Disabled actions retain `enabled: false` and nullable `reason`; hidden actions
  are absent. No active page produces `actions: []`.

Unselected fields are omitted, and their new readers are not evaluated. Requested
resolution errors propagate rather than becoming empty successful output. A present
null is different from an omitted field. Repeated opt-in calls are idempotent.

Actions retain core's `{ id, name, label, url, method, icon, destructive, enabled,
reason, confirm, current }` shape, including present nullable icon/reason/confirm
fields and GET, HEAD, POST, PUT, PATCH, and DELETE methods. An action is data for an
application-owned route, not an instruction that the adapter executes. Its details
are visible to the client; do not put secrets in labels, reasons, or confirmation
text. Routes still validate and authorize requests. Rendering and document metadata
remain application-owned.

All selected facts travel under the application's chosen prop key. Standard
Inertia partial reload and deferred-prop wrappers still control whether that prop
resolves. A partial reload omitting it may leave old values on the client;
request the prop when a mutation changes its descriptions or availability.

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

Then type the shared prop, passing your menu names and the exact PHP opt-ins:

```ts
import type { Current } from '@current/inertia';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            current: Current<'primary' | 'footer', 'pagePresentation' | 'subjectPresentation' | 'actions'>;
        };
    }
}
```

With no second generic, `Current<'primary' | 'footer'>` retains the default shape.
Selected facts are required (with their meaningful nullable values); unselected
facts cannot be accessed through that declaration. The TypeScript selection must
match PHP configuration; it is not inferred from server code.

The file also exports `Subject`, `Breadcrumb`, `MenuItem`, `MenuItemKind`, `Action`,
`CurrentFeature`, `PagePresentation`, and `SubjectPresentation`. Base `Subject`
stays the four-field identity/link type; opt-in presentation enriches the current
subject only. The declarations are frontend-framework-neutral; the playground
exercises React, not separate Vue or Svelte paths.

## Development

Development, checks, and releases run from the Current monorepo; see its
`docs/monorepo.md`. This repository is a read-only split.

The private npm manifest owns TypeScript 5.9.3 compiler assertions, independently
of playground dependencies. From the monorepo, `composer test:types` first creates
fixtures from real PHP serialization, then compiles all eight feature combinations
with strict declaration checking, positive cases, and expected negative cases.
The PHP shape helper checks concrete DTOs; it does not interpret conditional types.
See the monorepo contributor guide for installation and the complete check gate.

See [the changelog](CHANGELOG.md) for changes.
