# Current

One description of your Laravel application's experience: its pages, the
breadcrumbs and menus that read from them, the subjects users look at, and the
actions they can take.

Each page is defined once, with a stable ID, a place in the hierarchy, a label
that can come from the bound resource, and an optional association with an
existing named route. The ID is separate from the route, so a page keeps its
identity when a URL changes, and a page can exist without a route. Breadcrumbs
and menus read the same definitions, so labels, URLs, and current and active
states stay consistent. A menu can follow the page hierarchy, or arrange pages
its own way without changing their breadcrumbs.

Current describes; your application executes. Routes, controllers,
authorization, and rendering stay with the application, and everything here
returns plain data for Blade, Inertia, or any other frontend.

Package: `current/current`. Namespaces: `Current\Pages\` (pages),
`Current\Navigation\` (breadcrumbs and menus), `Current\Subjects\`
(subjects), `Current\Actions\` (actions), and `Current\Context`.
It was released as `wavykit/core` up to 0.2.0; the
first release as `current/current` is 0.3.0. While Current is on 0.x, minor
releases may change the public API; see [Versioning](#versioning).

## Requirements

PHP 8.3+, Laravel / Illuminate 13, and Composer 2 for development. No Livewire
or database requirement.

## Installation

Install with Composer:

```sh
composer require current/current
```

Laravel discovers `Current\CurrentServiceProvider` automatically. Register your
pages and menus as shown below; existing routes and controllers remain in
place.

## Pages

Register definitions in an application service provider's `boot()` method:

```php
use Current\Pages\Page;
use Current\Pages\PageRegistry;

public function boot(PageRegistry $pages): void
{
    $pages->register(
        Page::make('dashboard')->label('Dashboard')->route('dashboard'),
        Page::make('projects')->label('Projects')->route('projects.index')->children(
            Page::make('projects.create')
                ->label('Create project')
                ->route('projects.create'),
        ),
    );
}
```

These named routes belong to the application. Current never creates or
modifies routes, middleware, URLs, or access rules. Definitions may be
registered before routes finish loading; resolve output only after route
registration, and after model-binding middleware if route parameters are needed
for URLs.

Pages can be nested or contributed separately with `parent('projects')`.
IDs do not imply ancestry. `order(int)` controls sibling order, ascending;
ties retain declaration order. `icon(string)` stores an optional presentation
identifier that breadcrumbs, menus, or the host application can use.

Registration snapshots builders. The first registry read finalizes all
definitions; register everything before that read. Duplicate IDs, missing or
cyclic parents, conflicting parents, and duplicate route associations fail
clearly. Each page associates with at most one route and each route with at
most one page. Route-less pages are valid.

### Reading the current page

Read the current request through the `Current\Facades\Current` facade:

```php
use Current\Facades\Current;

$page = Current::page();
$id = Current::pageId();
$context = Current::context();

if (Current::is('projects.*')) {
    // The active page is a project page.
}
```

`page()` returns the immutable registered `PageDefinition` for the current
route, or null. It retains dynamic callbacks rather than executing them.
`pageId()` returns its ID. `is()` matches the active page's ID against one or
more patterns, with `*` as a wildcard, like Laravel's `routeIs()`; it checks
the active page only, not its ancestors. `context()` returns a
`Current\Context` with the active definition, bound route parameters, and
current user, which `Current::user()` also returns. `Current::subject()`
returns the subject the request is about; see [Subjects](#subjects).

`Current` only reads. To inject it instead, type-hint `Current\Experience`, the
class behind the facade. It holds no request state, so it is safe in queue
workers and Octane. Register pages and menus through `PageRegistry` and
`MenuRegistry`, as shown above.

Packages that build on the core read pages through `Current\Pages\Pages`,
which `Current` uses, and resolve page output through the same request:
`activeTrail($context)` returns the active page's ancestry, root first;
`label($page, $context)` and `url($page, $context)` resolve one page with the
rules below.

### Resource-aware labels and URLs

Labels may be strings or callbacks receiving `Current\Context`:

```php
use App\Models\Project;
use Current\Context;
use Current\Pages\Page;

Page::make('projects.show')
    ->route('projects.show')
    ->parent('projects')
    ->label(function (Context $context): string {
        $project = $context->parameter('project');

        if (! $project instanceof Project) {
            throw new \LogicException('Expected a bound project.');
        }

        return $project->name;
    })
    ->children(
        Page::make('projects.edit')->route('projects.edit')->label('Edit'),
    );
```

On the edit route, the detail ancestor uses the bound project to resolve its
label and URL. Ancestor callbacks see the same active page as the current
breadcrumb. `parameter('name')` returns the bound value or throws when the name
is absent. Labels must return strings.

If a child route uses `{item}` while an ancestor route uses `{project}`, provide
an explicit mapping on the ancestor:

```php
Page::make('projects.show')
    ->route('projects.show')
    ->routeParameters(fn (Context $context): array => [
        'project' => $context->parameter('item'),
    ]);
```

A mapping supplies the complete parameter source for that destination; it is not
merged with the active route parameters. Keys must be parameter names. Only keys
declared by the destination route are forwarded; extra keys never become query
strings. Laravel still handles bound-model route keys and URL encoding.

Callbacks run only when their output is requested, after Laravel's binding
middleware. They may run multiple times; keep them free of side effects and read
request data from the context rather than capturing a user or resource during
registration. Resolved values are not cached on the shared registries. Contexts
are request snapshots and should not be stored on application singletons.

An unregistered active route returns a null page. Missing route names raise
errors. Ancestor URL generation forwards matching destination path parameters
only. Missing required parameters raise an exception identifying the page and
route, with Laravel's URL-generation exception preserved as the cause.

### Titles and descriptions

A page's label is its short name in breadcrumbs and menus. For headings, the
document `<title>`, and meta descriptions, give it a title and a description:

```php
Page::make('projects.show')
    ->route('projects.show')
    ->label(fn (Context $context): string => $context->parameter('project')->name)
    ->title(fn (Context $context): string => 'Project '.$context->parameter('project')->name)
    ->description('Milestones, tasks, and people for one project.');
```

```php
use Current\Facades\Current;

Current::title();        // 'Project Apollo'; the label when no title is set
Current::description();  // the description, or null when the page has none
```

Both take a string or a callback receiving `Current\Context`, like labels. A
title callback must return a non-empty string; a description callback returns
a non-empty string or null for none. The description never falls back to the
label. Both readers return null when no page is active. Current adds no site
name or suffix: append your own, for example
`{{ Current::title() }} · Acme`. Breadcrumbs and menus keep using labels.

Packages that build on the core resolve any page with `Pages::title($page,
$context)` and `Pages::description($page, $context)`.

## Breadcrumbs

```php
use Current\Facades\Current;

$breadcrumbs = Current::breadcrumbs();
```

On `projects.create`, breadcrumbs are Projects → Create project. Each is a
`Current\Navigation\Breadcrumb` with `pageId`, `label`, nullable `url`,
`current`, and nullable `icon`, the same shape as menu items. Every breadcrumb
keeps its URL, including the current one, so the renderer decides whether to
link it; route-less ancestors have no URL. Labels are resolved plain strings,
and an unregistered active route returns empty breadcrumbs.

```blade
<nav aria-label="Breadcrumb">
    <ol>
        @foreach ($breadcrumbs as $crumb)
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

## Menus

### Derived menus

Use `derive()` for a menu that follows the page hierarchy, registered next to
your pages:

```php
use Current\Navigation\MenuRegistry;
use Current\Pages\PageRegistry;

public function boot(PageRegistry $pages, MenuRegistry $menus): void
{
    // ... register pages, including projects.show as above

    $menus->derive('primary', 'dashboard', 'projects')->except('projects.show');
}
```

Roots keep the supplied order; descendants use page sibling order. `except()`
omits pages and their subtrees from that menu only. Excluded pages keep their
breadcrumbs, can appear in other menus, and still make their ancestors active:
on a project's page, Projects is active even though the project is not listed.
Excluded branches do not run label or parameter callbacks. Exclusion is
presentation configuration, never an HTTP authorization rule. Overlapping
roots that would list a page twice are rejected.

To hide a page from some requests, add a callback with `exceptWhen()`:

```php
$menus->derive('primary', 'dashboard', 'projects', 'billing')
    ->exceptWhen('billing', fn (Context $context): bool => ! $context->user?->can('manageBilling'));
```

The page and its subtree are left out of that menu whenever the callback
returns true. The callback receives the request's `Context`, must return a
bool, and runs only when the menu reaches the page, before its label or URL;
callbacks below an excluded page never run. Several callbacks for one page
hide it when any returns true. Like `except()`, it changes only this menu:
breadcrumbs, other menus, and ancestor active state are unaffected, and it
never replaces an authorization check on the route.

### Explicit menus

Use `define()` when a menu needs its own nesting or ordering. Each
`MenuEntry` places a registered page or groups other entries:

```php
use Current\Navigation\MenuEntry;

$menus->define('footer',
    MenuEntry::page('privacy'),
    MenuEntry::group('your-account')->label('Your account')->children(
        MenuEntry::page('account')->label('Profile')->icon('user'),
    ),
);
```

Register the referenced pages separately, once. Each placement reuses its page's
route, label, and icon unless overridden. Placement labels can also be callbacks
receiving `Current\Context`.

Explicit entries follow their authored order and include only their declared
children. They do not import page descendants, and derived-menu exclusions do
not apply to them. A page placement may have children and remain a clickable
link. Moving a menu entry never changes page ancestry, breadcrumbs, or routes.

Menu names are unique across derived and explicit menus. A page may appear in
multiple menus, but only once within a given menu. Group IDs must also be unique
within a menu; a group may share an ID with a page because they are distinct
concepts. Missing page references and duplicate placements fail validation even
when their visibility callbacks would hide them.

### Request-time visibility

`visibleWhen()` applies to explicit page placements and groups:

```php
use Current\Context;

$menus->define('account',
    MenuEntry::page('account')->visibleWhen(
        fn (Context $context): bool => $context->user !== null,
    ),
);
```

`Context::$user` is Laravel's current request user, or null for a guest. For
policy-based visibility, call your application's Gate/policy checks from the
predicate. A predicate must return bool; exceptions and invalid results identify
the menu and entry. No user data or visibility result is cached on the shared
registry.

A hidden entry's label, destination URL, and descendants are not resolved, so
visibility can guard entries whose labels or URLs need unavailable resource
parameters. An empty group's label is not resolved. Hiding an entry never
enforces HTTP authorization: keep application middleware and policy checks on
routes and controllers.

### Reading menus

```php
use Current\Facades\Current;

$items = Current::menu('primary');
```

`menu()` returns `MenuItem` objects with nullable `pageId`, `label`, nullable
`url`, `current`, `active`, recursive `children`, nullable `icon`, and `kind`,
a `MenuItemKind` enum that serializes to `page` or `group`. As with
breadcrumbs, the current page keeps its URL. Presentation groups have no page ID or URL; route-less
pages have a null URL.

`current` identifies an exact page match. `active` includes that match and the
active page's ancestors, even when the active page is absent from the menu.
Explicit menu parents also become active when a visible child entry is active.
Groups are never current. Empty explicit groups are omitted; linked page entries
remain when all of their children are hidden. An unregistered active route
still renders global menus, with no active items. Unknown menus raise errors.

For example, create `resources/views/partials/menu.blade.php`:

```blade
<ul>
    @foreach ($items as $item)
        <li @class(['active' => $item->active])>
            @if ($item->url !== null)
                <a href="{{ $item->url }}" @if ($item->current) aria-current="page" @endif>
                    {{ $item->label }}
                </a>
            @else
                <span>{{ $item->label }}</span>
            @endif
            @if ($item->children !== [])
                @include('partials.menu', ['items' => $item->children])
            @endif
        </li>
    @endforeach
</ul>
```

Render it inside an appropriately labelled navigation element with
`@include('partials.menu', ['items' => \Current\Facades\Current::menu('primary')])`.

## Subjects

A subject is a resource users look at and act on, such as a project or a task.
Describe each kind once, with its class, its label, and the page that shows
it; pages that present a kind then take its label, and any object of that kind
resolves to the same label and link anywhere.

```php
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Subjects\Subject;
use Current\Subjects\SubjectRegistry;

public function boot(SubjectRegistry $subjects, PageRegistry $pages): void
{
    $subjects->register(
        Subject::make('project')->for(Project::class)
            ->label(fn (Project $project): string => $project->name)
            ->page('projects.show'),
        Subject::make('task')->for(Task::class)
            ->label(fn (Task $task): string => $task->title)
            ->page('tasks.show', fn (Task $task): array => ['project' => $task->project, 'task' => $task]),
    );

    $pages->register(
        Page::make('projects')->route('projects.index')->label('Projects')->children(
            Page::make('projects.show')->route('projects.show')->presents('project')->children(
                Page::make('projects.edit')->route('projects.edit')->label('Edit'),
                Page::make('tasks.show')->route('tasks.show')->presents('task'),
            ),
        ),
    );
}
```

- `presents('project')` reads the bound route parameter of the same name;
  `presents('task', parameter: 'id')` names another. A page that presents a
  subject and has no label of its own takes the subject's label, so
  breadcrumbs read Projects › Apollo › Fix login; an explicit label, such as
  "Edit", still wins.
- `page()` names the kind's one canonical page, which must present it. By
  default the route parameter named after the kind receives the object;
  otherwise map the object to the route's parameters. A kind without a page
  has no URL.
- Subject classes must be routable (`UrlRoutable`, such as Eloquent models):
  identity is the kind plus the route key, `project:42`. Two kinds cannot
  share a class or a class hierarchy.

Subject declaration validity is shared by page, subject, and action readers:
classes must be routable, and presenting routes must declare their subject
parameter. Complete all registration before reading, even when reading only a
page ID or an explicit page title. These checks never evaluate callbacks.
When a reader needs a presented object, it must be an instance of the kind's
registered class; a wrong binding raises `ResolutionFailed` naming the page,
parameter, expected class, and actual class before subject or action callbacks run.

Read subjects as `Current\Subjects\SubjectItem` objects with `kind`, `id`,
`label`, nullable `url`, and `key()`; they serialize to
`{ kind, id, label, url }`:

```php
use Current\Facades\Current;
use Current\Subjects\Subjects;

Current::subject();               // what the request is about, or null
app(Subjects::class)->of($task);  // any object's subject and canonical link
```

`Current::subject()` is the active page's subject, or else the nearest ancestor
page's that presents one: on `projects.edit` it is the project. It is null
outside a request or when no page in the trail presents a subject. `of()` works
anywhere Laravel can generate URLs, including queue jobs and notifications.
Current never loads or queries subjects; it reads objects your routes bind.

### Subject titles, descriptions, and icons

A kind can also give its subjects a title for headings, a description, and an
icon:

```php
Subject::make('project')->for(Project::class)
    ->label(fn (Project $project): string => $project->name)
    ->title(fn (Project $project): string => "Project {$project->name}")
    ->description(fn (Project $project): ?string => $project->summary)
    ->icon('folder')
    ->page('projects.show');
```

- A page that presents a subject takes its title and description unless it
  sets its own: its title is the page's explicit title, otherwise the subject's
  title falling back to the subject's label; the description is the page's,
  then the subject's, or null. On a project's page, `Current::title()` is "Project Apollo" while the
  breadcrumb still reads "Apollo".
- A subject's title falls back to its label; its description never does.
- A page that presents a subject and has no icon of its own takes the kind's
  icon in breadcrumbs and menus.
- For any object, `app(Subjects::class)->title($task)` and `description($task)`
  run only those callbacks. `SubjectItem` stays `{ kind, id, label, url }`, so
  lists of subjects don't resolve titles they don't show.

Page overrides do not replace the current subject's own presentation. A page
below a presenting page keeps its own title and description, while
`Current::subject()` and `Current::actions()` use the nearest presenting ancestor.
Reading a title resolves only its title callback or label fallback, without
resolving a subject URL, description, or actions. Callbacks may run repeatedly
and must remain free of side effects.

## Actions

An action is something a user can do to a subject or on a page, such as
archiving a task or creating one. Current **describes** actions and never runs
them: each action names your own route, which performs and authorizes it.
Forms, confirmation dialogs, and progress stay in your interface.

```php
use Current\Actions\Action;
use Current\Actions\ActionRegistry;
use Current\Context;

public function boot(ActionRegistry $actions): void
{
    $actions->register(
        // A page's action: offered while that page is current.
        Action::make('create')->on('projects.tasks')
            ->label('New task')->icon('plus')
            ->route('tasks.create'),

        // A subject kind's actions: offered for each subject of that kind.
        Action::make('edit')->for('task')
            ->label('Edit')->order(1)
            ->route('tasks.edit', 'GET', fn (Task $task): array => ['project' => $task->project, 'task' => $task])
            ->can('update'),
        Action::make('archive')->for('task')
            ->label('Archive')->order(2)->destructive()
            ->confirm(fn (Task $task): string => "Archive “{$task->title}”?")
            ->route('tasks.archive', 'POST', fn (Task $task): array => ['project' => $task->project, 'task' => $task])
            ->visibleWhen(fn (Task $task, Context $context): bool => $context->user !== null)
            ->can('archive'),
    );
}
```

- Each action uses exactly one of `for()` (a subject kind) or `on()` (a page),
  and needs a `label()` and a `route()`. Its ID is the kind or page and its
  name, such as `task.archive`, and must be unique.
- `route($name, $method = 'GET', $parameters = null)`: a subject action passes
  the subject as the parameter named after its kind unless the callback maps
  it; a page action forwards the current request's parameters. Only
  parameters the route declares are used.
- Callbacks receive the subject then the `Context` for subject actions, and
  the `Context` alone for page actions.
- `visibleWhen()` returns a bool; false leaves the action out entirely.
- `can($ability)` checks `Gate::inspect($ability, $subject)` for the current
  user, and `enabledWhen()` returns a bool or an
  `Illuminate\Auth\Access\Response`. Either disables the action; a denied
  response's message becomes its reason. `can()` runs first, and the first
  denial wins. A guest denied by a policy that requires a user gets no reason.
- `icon()`, `confirm()` (text for your own dialog), `destructive()`, and
  `order()` are presentation only.

Hiding or disabling an action is presentation, never authorization: your
route must still authorize, as in the example's policy abilities.

### Reading actions

```php
use Current\Actions\Actions;
use Current\Facades\Current;

Current::actions();                 // the current subject's actions, then the current page's
app(Actions::class)->for($task);    // one object's actions, for a table row
```

Both return visible actions only, as `Current\Actions\ActionItem` objects
with `id`, `name`, `label`, `url`, `method`, `icon`, `destructive`, `enabled`,
`reason`, `confirm`, and `current`, which serialize to that object shape.
`Current::actions()` uses the current subject as `Current::subject()` does, so
a task's actions also appear on pages below the task's page; `current` marks a
GET action whose target is the current request, such as Edit on the edit
page, so you can hide or disable it.

## Errors

Every exception implements `Current\Exceptions\CurrentException` and extends
`LogicException`:

- `InvalidDefinition`: a page or menu declaration is invalid, was registered
  after the registries were read, or references an unknown route, parent, or
  page.
- `ResolutionFailed`: output could not be resolved for the request, such as a
  failing or invalid label, route-parameter, or visibility callback, a missing
  route parameter, a URL generation failure, or an unknown menu. The original
  exception is kept as the previous exception.

Messages name the page, menu, or entry involved.

## Lifecycle and route caching

Menu registration snapshots builders. The first menu read finalizes the page
registry, then validates and freezes menus; register everything before that
read. A failed menu validation leaves menus open for correction. Calling
`except()` after menus are resolved fails.

Keep page and menu registration in a service provider that runs on every boot,
including boots that restore cached routes. Do not place it inside a
`! $app->routesAreCached()` condition or rely on executing a route file to
register definitions. Laravel's route cache stores route actions, not the
registries, and label, parameter, and visibility callbacks are never serialized
into it.

The integration fixture boots a small application in separate PHP processes,
generates the cache with the real `route:cache` command, then boots again from
cached routes. It compares page lookup, breadcrumbs, derived and explicit
menus, bound-resource labels and URLs, and user visibility across consecutive
requests. It also checks that building the cache does not evaluate page labels.

## Inspecting definitions

Four Artisan commands describe and check what is registered, without a
request:

```sh
php artisan current:pages          # the page tree: ID, label, route, icon
php artisan current:menus          # every menu as a tree; or: current:menus primary
php artisan current:subjects       # subject kinds: class, canonical page, presenting pages
php artisan current:actions        # actions by subject kind and page: route, method, rules
php artisan current:check          # validate everything; exits 1 on any problem
```

`current:subjects` shows which kinds have a title, a description, or an icon.
`current:actions` shows each action's route and method and which rules it has;
`current:check` also reports actions on unknown kinds, pages, or routes, methods
a route does not accept, and subject actions whose route lacks the kind's
parameter without a mapping.
Labels that come from callbacks are listed as `(dynamic)`; the commands never
run label, parameter, or visibility callbacks. `current:menus` shows whether a
menu is derived (with its roots, `except()` exclusions, and `exceptWhen()`
pages, marked "hidden when") or defined, which
entries are groups, and which have a `visibleWhen()` rule.

`current:check` reports duplicate or cyclic pages, unknown parents, unknown
routes, and invalid menu references, and suits CI. It lists every unknown route
at once; other definition errors stop at the first one, so fix it and run the
check again. The listing commands run the same checks first and fail the same
way.

Add `--json` to `current:pages`, `current:menus`, or `current:subjects` for
machine-readable output.
Pages are nested under `children`, with `label` null and `dynamicLabel` true
for callback labels, and the same for `title`/`dynamicTitle` and
`description`/`dynamicDescription` (a null `title` that is not dynamic means
the title is the label); menus have `name`, `kind` (`derived` or `defined`),
`roots`, `except`, `exceptWhen`, and nested `entries`, where `visibility` is
true for entries with a `visibleWhen()` or `exceptWhen()` rule.

## Testing

Add `Current\Testing\InteractsWithCurrent` to your application's `TestCase`,
make a request, then assert what Current resolved for it:

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
    ->assertMenuHas('primary', 'admin')
    ->assertMenuActive('primary', 'projects');

$this->actingAs($member)->get('/projects/42');
$this->assertMenuMissing('primary', 'admin');
```

- `assertCurrentPage(?string $id)` checks the active page; pass null to assert
  that no page is active.
- `assertBreadcrumbs(array $labels)` checks the breadcrumb labels, root first.
- `assertMenuHas()` and `assertMenuMissing()` check whether a menu shows a page
  at any depth, which is how `visibleWhen()` rules are tested.
  `assertMenuActive()` checks that the page is shown and active.
- `assertCurrentSubject(?string $kind, ?string $id = null)` checks the current
  subject's kind, and its ID when given; pass null to assert there is none.
- `assertActionHas()`, `assertActionMissing()`, `assertActionEnabled()`, and
  `assertActionDisabled(string $id, ?string $reason = null)` check the
  request's actions by ID, such as `task.archive`.
- `assertTitle(string $title)` checks the active page's title, which falls
  back to its label; `assertDescription(?string $description)` checks its
  description, with null for none.

The assertions read the last request through `Current\Facades\Current`, so
callbacks see that request's bound models and user. Failure messages include
the actual labels or the menu as it was resolved. In Pest, add the trait with
`pest()->use(InteractsWithCurrent::class)` or `uses(...)`. PHPUnit is required,
as in any Laravel test suite; the trait is never loaded outside tests.

## Development

The core is developed from the Current monorepo root, which installs the
development tools once and links this package through a path repository:

```sh
composer install
composer check current
```

The package keeps its own `phpunit.xml`, `phpstan.neon`, and `pint.json`.
`composer check current` validates this manifest, checks Pint formatting, runs
PHPStan at maximum level on source, and executes Pest unit, Testbench feature,
and route-cache integration tests. `composer format core` applies formatting.
The root `composer.lock` is local and ignored; fresh checkouts resolve
dependencies within the declared constraints.

## Verification limits

Tests cover static and bound-resource output, route-less ancestors, parameter
mapping, derived and explicit menus, exclusions, visibility, validation errors,
request isolation, and fresh-process route-cache round trips. The suite is also
checked against a lowest-installable dependency resolution. That older
dependency set emits a Symfony Translation deprecation under PHP 8.5;
diagnostics remain visible and are not suppressed.

Monorepo CI runs the checks on PHP 8.3, 8.4, and 8.5 with lowest and stable
resolutions. PHP 8.3 remains the declared minimum and dependency-resolution
platform. The private Current playground, a Laravel 13 / Inertia 3 / React
application, consumes the package and checks that its output is identical with
and without cached routes. These are observed checks, not a production-readiness
guarantee.

## Versioning

The initial release is intended to remain on 0.x while the public API is refined.
Use minor releases for breaking changes during 0.x and patch releases for
compatible fixes. At 1.0 and later, breaking public API changes require a major
release. The monorepo's `CONTRIBUTING.md` defines what counts as public API:
anything marked `@internal` may change in any release. Releases are tagged `current/vMAJOR.MINOR.PATCH` on the Current monorepo, and
the split workflow pushes each one to the read-only `currentphp/current` split
repository as `vMAJOR.MINOR.PATCH`. Versions 0.1.0 and 0.2.0 were released as
`wavykit/core` from the earlier lockstep tags. Composer derives versions from those tags, so `composer.json`
does not contain a version field. The release process is documented in the
monorepo's `docs/monorepo.md`; contribute there, not to the split repository.

See [the changelog](CHANGELOG.md) for release changes. The
[first release checklist](docs/release-checklist.md) records how `v0.1.0` was
prepared.
