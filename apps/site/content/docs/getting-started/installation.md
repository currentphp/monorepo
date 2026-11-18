---
title: Installation
description: Add Current to a Laravel 13 application.
order: 1
---

## Requirements

- PHP 8.3 or later
- Laravel 13

Current needs no database, Livewire, or frontend build. It returns plain data, so it works with Blade, Inertia, or any other frontend.

## Install the package

```shell
composer require current/current
```

Laravel discovers `Current\CurrentServiceProvider` automatically. Nothing is published and there is no configuration file.

## Where definitions go

You describe your application in a service provider's `boot()` method, usually `AppServiceProvider`. Current reads three registries, which you can type-hint:

```php
use Current\Navigation\MenuRegistry;
use Current\Pages\PageRegistry;
use Current\Subjects\SubjectRegistry;

public function boot(PageRegistry $pages, MenuRegistry $menus, SubjectRegistry $subjects): void
{
    // Register pages, menus, and subjects here.
}
```

Keep registration in a provider that runs on every boot, including boots from cached routes. Don't put it inside a route file or a `routesAreCached()` check: Laravel's route cache stores routes, not Current's registries.

Next, [describe your pages](/docs/getting-started/pages).
