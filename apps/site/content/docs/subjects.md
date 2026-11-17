---
title: Subjects
description: Declare the records users look at, such as a project or an invoice, once, and link them to the pages that present them.
order: 2
---

A subject is a resource users look at and act on, such as a project or a task. You describe each kind once, with its class, its label, and the page that shows it. Pages that present a kind then take its label, and any object of that kind resolves to the same label and link anywhere: in a breadcrumb, a notification, or a search result.

## Declaring subjects

```php
use App\Models\Project;
use App\Models\Task;
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

- `presents('project')` reads the bound route parameter of the same name; `presents('task', parameter: 'id')` names another.
- A page that presents a subject and has no label of its own takes the subject's label, so breadcrumbs read Projects › Apollo › Fix login. An explicit label, such as "Edit", still wins.
- `page()` names the kind's one canonical page, which must present it. Pass a callback when the route needs more parameters than the object itself.

Subject classes must be routable (`UrlRoutable`, such as Eloquent models). A subject's identity is its kind plus its route key, such as `project:42`.

## Reading subjects

```php
use Current\Facades\Current;
use Current\Subjects\Subjects;

Current::subject();               // what the request is about, or null
app(Subjects::class)->of($task);  // any object's subject and canonical link
```

`Current::subject()` returns the active page's subject, or else the nearest ancestor's: on `projects.edit` it is the project. `of()` works anywhere Laravel can generate URLs, including queue jobs and notifications.

Both return a `Current\Subjects\SubjectItem` with `kind`, `id`, `label`, and `url`, which serializes to `{ kind, id, label, url }`.

> [!NOTE]
> Current never loads or queries subjects. It reads the objects your routes bind, or the ones you pass to `of()`.
