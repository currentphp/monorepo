<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ProjectExample;
use App\Prototype\Actions\Actions;
use App\TaskExample;
use Current\Facades\Current;
use Current\Subjects\Subjects;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Task admin for the actions prototype (WK-008). Every button on these pages
 * comes from the action description; the routes enforce authorization and
 * never write, because the demo data is fixtures.
 */
final class TaskController extends Controller
{
    public function index(ProjectExample $project, Actions $actions, Subjects $subjects): Response
    {
        return Inertia::render('tasks', [
            'title' => Current::title(),
            'form' => null,
            'rows' => array_map(fn (TaskExample $task): array => [
                'subject' => $subjects->of($task),
                'actions' => $actions->for($task),
            ], TaskExample::forProject($project)),
        ]);
    }

    public function create(ProjectExample $project): Response
    {
        return $this->form(route('tasks.store', $project), 'post', '');
    }

    public function edit(ProjectExample $project, TaskExample $task): Response
    {
        return $this->form(route('tasks.update', [$project, $task]), 'put', $task->title);
    }

    public function store(ProjectExample $project): RedirectResponse
    {
        return $this->done($project, 'Prototype: the new task was not saved.');
    }

    public function update(ProjectExample $project, TaskExample $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        return $this->done($project, "Prototype: changes to “{$task->title}” were not saved.");
    }

    public function archive(ProjectExample $project, TaskExample $task): RedirectResponse
    {
        Gate::authorize('archive', $task);

        return $this->done($project, "Prototype: “{$task->title}” was not archived.");
    }

    private function form(string $url, string $method, string $value): Response
    {
        return Inertia::render('tasks', [
            'title' => Current::title(),
            'form' => ['url' => $url, 'method' => $method, 'value' => $value],
            'rows' => [],
        ]);
    }

    private function done(ProjectExample $project, string $message): RedirectResponse
    {
        return redirect()->route('tasks.index', $project)->with('status', $message);
    }
}
