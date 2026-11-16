<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ProjectExample;
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

    private function render(string $title, ?ProjectExample $project = null): Response
    {
        return Inertia::render('projects', [
            'title' => $title,
            'project' => $project === null ? null : ['id' => $project->id, 'name' => $project->name],
            'projects' => array_map(fn (ProjectExample $example): array => [
                'id' => $example->id,
                'name' => $example->name,
                'url' => route('projects.show', $example),
                'editUrl' => route('projects.edit', $example),
            ], ProjectExample::examples()),
        ]);
    }
}
