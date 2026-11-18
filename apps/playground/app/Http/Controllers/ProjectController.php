<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ProjectExample;
use App\TaskExample;
use Current\Facades\Current;
use Current\Subjects\SubjectItem;
use Current\Subjects\Subjects;
use Inertia\Inertia;
use Inertia\Response;

/** Projects demo: resource pages whose titles, breadcrumbs, and menus come from Current. */
final class ProjectController extends Controller
{
    public function index(): Response
    {
        return $this->render();
    }

    public function show(ProjectExample $project): Response
    {
        return $this->render($project);
    }

    public function edit(ProjectExample $project): Response
    {
        return $this->render($project);
    }

    /** A task inside its project; the task is the page's subject. */
    public function task(ProjectExample $project, TaskExample $task): Response
    {
        return $this->render($project);
    }

    private function render(?ProjectExample $project = null): Response
    {
        return Inertia::render('projects', [
            // The page heading and document title: the page's title, else its label or subject.
            'title' => Current::title(),
            'project' => $project === null ? null : ['id' => $project->id, 'name' => $project->name],
            // Canonical subject links, built from the objects alone.
            'tasks' => $project === null ? [] : array_map(
                fn (TaskExample $task): SubjectItem => app(Subjects::class)->of($task),
                TaskExample::forProject($project),
            ),
            'projects' => array_map(fn (ProjectExample $example): array => [
                'id' => $example->id,
                'name' => $example->name,
                'url' => route('projects.show', $example),
                'editUrl' => route('projects.edit', $example),
            ], ProjectExample::examples()),
        ]);
    }
}
