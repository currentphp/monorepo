# Blade: render the experience

Describe the project subject, its canonical detail page, and an edit child as in
the package README. For this example the application owns `projects.show`,
`projects.edit`, and a POST `projects.rename` route; Current never creates them.
An edit page may override its heading while its subject stays the project.

Resolve after route-model binding. The controller authorizes the bound project
and passes `Current::forPage('projects.edit', ['project' => $project])` to its
view as `$experience`. On an ordinary registered page, injecting the ambient
`Current\Experience` is also valid. Use the explicit reader when the selected
page differs from the transport route.

The following rendering example is compiled directly in the playground feature
tests. The application UI shares its own markup and stylesheet across renderers.
All authored presentation stays escaped, including labels, descriptions,
disabled reasons, and confirmation text. The application owns markup and styling.

```blade
<h1>{{ $experience->title() }}</h1>
@if ($experience->description() !== null)
    <p>{{ $experience->description() }}</p>
@endif
@if ($experience->subject() !== null)
    <h2>{{ $experience->subjectTitle() }}</h2>
    <p>{{ $experience->subjectDescription() }}</p>
@endif
<nav aria-label="Breadcrumbs">
    @foreach ($experience->breadcrumbs() as $crumb)
        @if ($crumb->url !== null)
            <a href="{{ $crumb->url }}" @if($crumb->current) aria-current="page" @endif>{{ $crumb->label }}</a>
        @else
            <span>{{ $crumb->label }}</span>
        @endif
    @endforeach
</nav>
<nav aria-label="Primary">
    @foreach ($experience->menu('primary') as $item)
        <a href="{{ $item->url }}" @if($item->current) aria-current="page" @endif>{{ $item->label }}</a>
    @endforeach
</nav>
@foreach ($experience->actions() as $action)
    @if ($action->current || $action->method === 'HEAD')
        @continue
    @endif
    @if ($action->method === 'GET')
        @if ($action->enabled)
            <a href="{{ $action->url }}">{{ $action->label }}</a>
        @else
            <span aria-disabled="true">{{ $action->label }}</span>
        @endif
    @else
        <form action="{{ $action->url }}" method="post">
            @csrf
            @if ($action->method !== 'POST')
                @method($action->method)
            @endif
            <label>New name <input name="name" required></label>
            <button type="submit" @disabled(! $action->enabled)>{{ $action->label }}</button>
        </form>
    @endif
    @if ($action->reason !== null)
        <p>{{ $action->reason }}</p>
    @endif
    @if ($action->confirm !== null)
        <p>{{ $action->confirm }}</p>
    @endif
@endforeach
```

This small primary menu is a flat explicit menu; applications render nested
menus recursively using each item's `children`, `kind`, `active`, and `current`.
The forms above illustrate the example's rename input, not a generic form builder.
Use action-specific fields in a real application. Displayed confirmation text is
not a confirmation dialog: wire an application-owned interaction if confirmation
is required. HEAD is a valid description method but is not a page navigation
button in this example.

The route must validate input and authorize again, regardless of a disabled
button. For the rename example:

```php
Route::post('/projects/{project}/rename', function (Request $request, Project $project) {
    Gate::authorize('rename', $project);
    $project->update($request->validate(['name' => ['required', 'string', 'max:100']]));
    if ($request->expectsJson()) {
        return response()->json(['id' => $project->getKey(), 'name' => $project->name]);
    }
    return redirect()->back(303, [], route('projects.show', $project))
        ->with('status', 'Project renamed.');
})->middleware('web')->name('projects.rename');
```

Browser submissions redirect after a successful write; JSON clients receive JSON.
Render flashed status and validation errors in the application layout.

Document `<title>` and metadata remain application-owned. Render the title as
escaped text in your layout. An unregistered ambient page has null presentation,
no breadcrumbs or current actions, and inactive menus; a missing or wrong binding
for a known subject raises a resolution error rather than silently retaining data.
The guide's rendering and route/CSRF/authorization behavior are checked with
Laravel 13.43.0 / PHP 8.5.13 and SQLite. There is no database requirement in core.
