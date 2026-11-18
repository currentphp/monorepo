---
title: Menus
description: Build menus that follow the page tree, or arrange pages your own way, with consistent active states.
order: 4
---

A menu reads the same pages as breadcrumbs, so labels, links, and active states always agree. There are two kinds: derived menus follow the page hierarchy, and explicit menus arrange pages their own way.

## Derived menus

```php
use Current\Navigation\MenuRegistry;

public function boot(MenuRegistry $menus): void
{
    $menus->derive('primary', 'dashboard', 'projects')->except('projects.show');
}
```

Roots keep the order you give; their descendants follow page order. `except()` leaves pages and their subtrees out of this menu only. An excluded page still makes its ancestors active: on a project's page, Projects is active even though the project isn't listed.

The sidebar of this site is a derived menu over the guides.

## Explicit menus

Use `define()` when a menu needs its own nesting or order:

```php
use Current\Navigation\MenuEntry;

$menus->define('footer',
    MenuEntry::page('privacy'),
    MenuEntry::group('your-account')->label('Your account')->children(
        MenuEntry::page('account')->label('Profile')->icon('user'),
    ),
);
```

Each entry places a registered page, reusing its route, label, and icon unless you override them, or groups other entries. Moving an entry never changes the page's ancestry or breadcrumbs.

## Visibility

Explicit entries can depend on the request:

```php
use Current\Context;

MenuEntry::page('admin')->visibleWhen(
    fn (Context $context): bool => $context->user?->can('viewAdmin') ?? false,
);
```

> [!WARNING]
> Hiding an entry is presentation, never authorization. Keep your routes protected by middleware, gates, or policies.

## Rendering

```php
$items = Current::menu('primary');
```

Each item is a `Current\Navigation\MenuItem` with `pageId`, `label`, `url`, `current`, `active`, `children`, `icon`, and `kind` (`page` or `group`). `current` marks the exact page; `active` also marks its ancestors and groups with an active child.

```blade
<ul>
    @foreach (Current::menu('primary') as $item)
        <li>
            <a href="{{ $item->url }}" @if ($item->current) aria-current="page" @endif
               @class(['font-semibold' => $item->active])>{{ $item->label }}</a>
        </li>
    @endforeach
</ul>
```

Next, declare the [subjects](/docs/subjects) your pages show.
