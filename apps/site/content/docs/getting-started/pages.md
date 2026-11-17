---
title: Pages
description: Describe each place in your application once, with a stable ID, a parent, a label, and an optional route.
order: 2
---

A page is a place in your application. It has a stable ID, a place in the hierarchy, a label, and optionally a named route. The ID is separate from the route, so a page keeps its identity when a URL changes, and a page can exist without a route.

## Registering pages

```php
use Current\Pages\Page;
use Current\Pages\PageRegistry;

public function boot(PageRegistry $pages): void
{
    $pages->register(
        Page::make('dashboard')->label('Dashboard')->route('dashboard'),
        Page::make('projects')->label('Projects')->route('projects.index')->children(
            Page::make('projects.create')->label('Create project')->route('projects.create'),
        ),
    );
}
```

The named routes belong to your application. Current never creates or changes routes, middleware, URLs, or access rules.

- Nest pages with `children()`, or contribute them separately with `parent('projects')`. IDs don't imply ancestry: `projects.create` is a child because it is nested, not because of its name.
- `order(int)` sorts siblings, ascending; ties keep declaration order.
- `icon(string)` stores an optional identifier that your views interpret.
- A page without a route is valid. It groups pages in breadcrumbs and menus without a link.

Registration is validated when the registry is first read. Duplicate IDs, missing or cyclic parents, and a route used by two pages fail with a clear message.

## Labels from the bound resource

A label can be a callback that receives `Current\Context`, so it can use route-model binding:

```php
use App\Models\Project;
use Current\Context;

Page::make('projects.show')
    ->route('projects.show')
    ->parent('projects')
    ->label(fn (Context $context): string => $context->parameter('project')->name);
```

On the project's edit page, the parent's label and URL resolve from the same bound project. Callbacks run only when their output is needed, after binding, and may run more than once, so keep them free of side effects.

When a child route names its parameter differently from its ancestor's, map it with `routeParameters()`:

```php
Page::make('projects.show')
    ->route('projects.show')
    ->routeParameters(fn (Context $context): array => [
        'project' => $context->parameter('item'),
    ]);
```

> [!TIP]
> For pages that show a record, such as a project, declare the record as a [subject](/docs/subjects) instead of writing a label callback on every page that shows it.

## Reading the current page

```php
use Current\Facades\Current;

Current::page();             // the active PageDefinition, or null
Current::pageId();           // its ID
Current::is('projects.*');   // match the active page's ID, like routeIs()
Current::context();          // the active page, bound parameters, and user
```

The facade only reads. To inject it, type-hint `Current\Experience`. It holds no request state, so it's safe in queue workers and Octane.

Next, render [breadcrumbs](/docs/getting-started/breadcrumbs).
