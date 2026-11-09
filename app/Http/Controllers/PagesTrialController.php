<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ProjectExample;
use Inertia\Inertia;
use Inertia\Response;
use WavyKit\Pages\Laravel\Pages;

final class PagesTrialController extends Controller
{
    public function index(Pages $pages): Response
    {
        return $this->render($pages, 'Projects');
    }

    public function show(Pages $pages, ProjectExample $project): Response
    {
        return $this->render($pages, $project->name, $project);
    }

    public function edit(Pages $pages, ProjectExample $project): Response
    {
        return $this->render($pages, 'Edit '.$project->name, $project);
    }

    private function render(Pages $pages, string $title, ?ProjectExample $project = null): Response
    {
        return Inertia::render('pages-trial', [
            'title' => $title,
            'currentPageId' => $pages->page()?->id,
            'breadcrumbs' => $pages->breadcrumbs(),
            'primary' => $pages->navigation('primary'),
            'footer' => $pages->navigation('footer'),
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
