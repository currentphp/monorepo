---
title: Upgrading from WavyKit
description: Move from wavykit/core and wavykit/inertia to current/current and current/inertia.
order: 5
---

Current was named WavyKit until November 2026. The `wavykit/core` and `wavykit/inertia` packages are abandoned on Packagist in favour of `current/current` and `current/inertia`. Version numbers continue, so `current/current` 0.3.0 follows `wavykit/core` 0.2.0, and `current/inertia` 0.2.0 follows `wavykit/inertia` 0.1.0.

## Swap the packages

```shell
composer remove wavykit/core wavykit/inertia
composer require current/current current/inertia
```

Leave out `inertia` if you didn't use it.

## Rename in your code

| WavyKit | Current |
| --- | --- |
| `WavyKit\` namespace | `Current\` namespace |
| `WavyKit\Current` (class behind the facade) | `Current\Experience` |
| `WavyKit\Facades\Current` | `Current\Facades\Current` |
| `WavyKitServiceProvider` | `Current\CurrentServiceProvider` (discovered automatically) |
| `WavyKit\Testing\InteractsWithWavyKit` | `Current\Testing\InteractsWithCurrent` |
| `WavyKitException` | `Current\Exceptions\CurrentException` |
| `wavykit:pages`, `wavykit:menus`, `wavykit:check` | `current:pages`, `current:menus`, `current:check` |
| `WavyKit\Inertia\WavyKitProps` | `Current\Inertia\CurrentProps` |
| TypeScript `WavyKit<…>` from `wavykit.d.ts` | `Current<…>` from `current.d.ts` |

A search and replace of `WavyKit\` to `Current\`, followed by the class names above, covers most applications.

## Update the TypeScript path

```json
{
    "compilerOptions": {
        "paths": {
            "@current/inertia": ["./vendor/current/inertia/resources/types/current.d.ts"]
        }
    }
}
```

The guides now share the prop under the key `current`. You can keep your existing key; only the class and type names changed.

## What else is new

The same releases added [subjects](/docs/subjects), the `subject` field in the Inertia prop, `current:subjects`, and `assertCurrentSubject()`, and made derived menus much faster on large page trees.
