# Livewire: keep page context explicit

An initial full-page render runs on the application's route. Later Livewire
requests run on Livewire's update endpoint. Ambient `Current::pageId()` therefore
need not identify the original page during an update. Persistent route middleware
still handles binding and authorization; it does not turn that endpoint into the
original HTTP request.

Use the public `Current::forPage()` reader with a server-authored page ID and
fresh, server-resolved objects. The full-page component owns that page experience;
a child may create a separate reader for its own subject without overwriting it.
Core has no Livewire runtime dependency, and this guide needs no Current adapter.

Register the project subject and its detail/edit page hierarchy as in the core
README. The edit page can have its own title while inheriting the project as its
current subject. In your application, a component can follow this pattern:

```php
use App\Models\Project;
use Current\Facades\Current;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class EditProject extends Component
{
    #[Locked]
    public int $projectId;

    public function mount(Project $project): void
    {
        Gate::authorize('view', $project);
        $this->projectId = (int) $project->getKey();
    }

    // An application-authored operation, not a Current action dispatcher.
    public function rename(string $name): void
    {
        $project = Project::query()->findOrFail($this->projectId);
        Gate::authorize('rename', $project);
        $data = validator(['name' => $name], ['name' => ['required', 'string', 'max:100']])->validate();
        $project->update($data);
    }

    public function render(): View
    {
        $project = Project::query()->findOrFail($this->projectId);
        Gate::authorize('view', $project);

        return view('livewire.edit-project', [
            'experience' => Current::forPage('projects.edit', ['project' => $project]),
        ]);
    }
}
```

Register the full-page route with normal web/authentication middleware and the
application's view policy. For example, a route with `can:view,project` causes
Livewire's persistent authorization middleware to check page access again on
updates. Locked IDs protect the component's identity; they do not grant access.
Reload objects and authorize operations on the server, including child methods.
Current does not query models, refresh them, authenticate, or authorize execution.

The component view can use the same escaped rendering as the
[Blade guide](blade.md). Its described actions still target the application's
named HTTP routes: render those links/forms using the description's URL and
method. The `rename()` component method above is a separate example of an
application-owned Livewire operation; do not infer callable method names from
client-supplied action IDs. Either path validates and reauthorizes its mutation.

## Updates, freshness, and parent shells

Retain trusted identity, not resolved labels, action lists, or a Current reader.
Construct a fresh reader during rendering after loading current model state.
Subject presentation remains separate from page presentation:
`subjectTitle()` reads the project's own title-or-label even when `title()` is
"Edit project". Menus and action callbacks receive the explicit selected Context;
an ambient facade call inside those callbacks still describes the actual request.

A child-only request hydrates and rerenders that child. It does not automatically
rerender the parent's heading, breadcrumbs, menus, or actions. The application
must coordinate parent refreshes after changes that affect the page shell.
Do not infer browser event delivery or document-title updates from fresh server
reader output. Document metadata and action confirmation interactions remain
application-owned.

Missing/deleted subjects and denied access should follow the application's
normal 404/403 paths. A missing or wrong binding supplied to a known Current page
raises a resolution error. Do not turn failed restoration into empty successful
output or retain a stale action list after an error.

## Verification boundary

The playground real-HTTP suite exercises the versions locked in the playground
(currently Laravel 13.43.0 and Livewire 4.4.9),
PHP 8.5.13, and SQLite, with actual cookies, CSRF, and signed snapshots. It checks
initial renders, parent and child-only updates, same-update and external model
changes, disabled reasons, revoked mutation/view permissions, deleted subjects,
tampered identity, and sequential/bundled page isolation. It checks escaped UI rendering and native route execution; feature tests also
compile the Blade guide's standalone example directly.

This is a tested combination, not a version support matrix. Browser event wiring,
`wire:navigate`, document metadata, authentication transitions, tenant/scoped
bindings, soft deletes, and Octane/concurrent workers are not established by it.
Core's PHP 8.3–8.5 matrix is separate from the playground's PHP 8.5 CI check.
