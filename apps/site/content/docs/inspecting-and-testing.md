---
title: Inspecting and testing
description: List and check your description with Artisan, and assert what a request resolved in your tests.
order: 4
---

## Artisan commands

Four commands describe and check what's registered, without making a request:

```shell
php artisan current:pages      # the page tree: ID, label, route, icon
php artisan current:menus      # every menu as a tree; or: current:menus primary
php artisan current:subjects   # subject kinds, their classes and pages
php artisan current:check      # validate everything; exits 1 on any problem
```

Labels from callbacks are listed as `(dynamic)`: the commands never run your callbacks. Add `--json` to `current:pages`, `current:menus`, or `current:subjects` for machine-readable output.

`current:check` reports duplicate or cyclic pages, unknown parents and routes, invalid menu references, and subject problems. Run it in CI:

```shell
php artisan current:check
```

## Test assertions

Add `Current\Testing\InteractsWithCurrent` to your test case, make a request, then assert what Current resolved for it:

```php
use Current\Testing\InteractsWithCurrent;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithCurrent;
}
```

```php
$this->actingAs($admin)->get('/projects/42')->assertOk();

$this->assertCurrentPage('projects.show')
    ->assertBreadcrumbs(['Projects', 'Apollo'])
    ->assertCurrentSubject('project', '42')
    ->assertMenuHas('primary', 'admin')
    ->assertMenuActive('primary', 'projects');

$this->actingAs($member)->get('/projects/42');

$this->assertMenuMissing('primary', 'admin');
```

| Assertion | Checks |
| --- | --- |
| `assertCurrentPage(?string $id)` | The active page; pass null for none. |
| `assertBreadcrumbs(array $labels)` | Breadcrumb labels, root first. |
| `assertMenuHas($menu, $pageId)` | The menu shows the page at any depth. |
| `assertMenuMissing($menu, $pageId)` | The menu doesn't show it, as when `visibleWhen()` hides it. |
| `assertMenuActive($menu, $pageId)` | The page is shown and active. |
| `assertCurrentSubject(?string $kind, ?string $id = null)` | The current subject; pass null for none. |

In Pest, add the trait with `pest()->use(InteractsWithCurrent::class)`. Failure messages include what was actually resolved.

> [!TIP]
> This site's own tests use these assertions on every guide. See [its tests](https://github.com/currentphp/monorepo/tree/main/apps/site/tests).
