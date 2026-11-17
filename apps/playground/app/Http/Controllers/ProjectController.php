<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ProjectExample;
use App\TaskExample;
use App\WavyKit\Subjects\SubjectRegistry;
use Inertia\Inertia;
use Inertia\Response;

/** Projects demo: resource pages whose breadcrumbs and menus come from WavyKit shared props. */
final class ProjectController extends Controller
{
    public function index(): Response
    {
        return $this->render('Projects');
    }

    public function show(ProjectExample $project): Response
    {
        return $this->render($project->name, $project);
    }

    public function edit(ProjectExample $project): Response
    {
        return $this->render('Edit '.$project->name, $project);
    }

    /** A task inside its project; the task is the page's subject (WK-019 prototype). */
    public function task(ProjectExample $project, TaskExample $task): Response
    {
        return $this->render($task->title, $project);
    }

    private function render(string $title, ?ProjectExample $project = null): Response
    {
        return Inertia::render('projects', [
            'title' => $title,
            'project' => $project === null ? null : ['id' => $project->id, 'name' => $project->name],
            // Canonical subject links, built from the objects alone (WK-019 prototype).
            'tasks' => $project === null ? [] : array_map(
                fn (TaskExample $task): array => app(SubjectRegistry::class)->of($task)->jsonSerialize(),
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
