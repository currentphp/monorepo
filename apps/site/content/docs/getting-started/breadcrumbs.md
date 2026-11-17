---
title: Breadcrumbs
description: Render the trail to the current page, read from the page hierarchy.
order: 3
---

Breadcrumbs follow the page hierarchy, so there is nothing to configure:

```php
use Current\Facades\Current;

$breadcrumbs = Current::breadcrumbs();
```

On `projects.create`, the breadcrumbs are Projects › Create project. On a project's page with a [bound label](/docs/getting-started/pages#labels-from-the-bound-resource), they read Projects › Apollo.

## The breadcrumb shape

Each breadcrumb is a `Current\Navigation\Breadcrumb` with:

| Property | Type | Notes |
| --- | --- | --- |
| `pageId` | `string` | The page's stable ID. |
| `label` | `string` | Already resolved. |
| `url` | `?string` | Null for route-less pages. The current page keeps its URL. |
| `current` | `bool` | True for the last breadcrumb. |
| `icon` | `?string` | The page's icon, if any. |

Breadcrumbs and menu items share this shape, so one partial can render both.

## Rendering in Blade

```blade
<nav aria-label="Breadcrumb">
    <ol>
        @foreach (Current::breadcrumbs() as $crumb)
            <li>
                @if ($crumb->current)
                    <span aria-current="page">{{ $crumb->label }}</span>
                @elseif ($crumb->url !== null)
                    <a href="{{ $crumb->url }}">{{ $crumb->label }}</a>
                @else
                    <span>{{ $crumb->label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
```

A route without a registered page returns no breadcrumbs. The breadcrumbs at the top of this page come from exactly this code.

Next, build [menus](/docs/getting-started/menus).
