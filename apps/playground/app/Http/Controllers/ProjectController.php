<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ExperienceData;
use App\Livewire\ProjectPage;
use App\ProjectExample;
use App\Rendering;
use App\TaskExample;
use Current\Facades\Current;
use Current\Subjects\SubjectItem;
use Current\Subjects\Subjects;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Projects demo: resource pages whose titles, breadcrumbs, and menus come from Current. */
final class ProjectController extends Controller
{
    public function index(Request $request): Response|View|HttpResponse
    {
        return $this->render($request);
    }

    public function show(Request $request, ProjectExample $project): Response|View|HttpResponse
    {
        Gate::authorize('view', $project);

        return $this->render($request, $project);
    }

    public function edit(Request $request, ProjectExample $project): Response|View|HttpResponse
    {
        Gate::authorize('view', $project);

        return $this->render($request, $project);
    }

    public function experience(ProjectExample $project): HttpResponse
    {
        Gate::authorize('view', $project);

        return response()->json(ExperienceData::from(Current::forPage('projects.show', ['project' => $project])));
    }

    public function rename(Request $request, ProjectExample $project): HttpResponse
    {
        Gate::authorize('rename', $project);
        $project->update($request->validate(['name' => ['required', 'string', 'max:100']]));
        if ($request->expectsJson()) {
            return response()->json(['id' => $project->getKey(), 'name' => $project->name]);
        }

        return redirect()->back(303, [], route('projects.show', $project))->with('status', 'Project renamed.');
    }

    /** A task inside its project; the task is the page's subject. */
    public function task(Request $request, ProjectExample $project, TaskExample $task): Response|View|HttpResponse
    {
        return $this->render($request, $project);
    }

    private function render(Request $request, ?ProjectExample $project = null): Response|View|HttpResponse
    {
        $rendering = Rendering::forRequest($request);
        if ($rendering === Rendering::Json || $request->expectsJson()) {
            return response()->json(ExperienceData::from(Current::forPage(Current::pageId() ?? 'projects', $request->route()?->parameters() ?? [])));
        }
        if ($rendering === Rendering::Livewire) {
            return app(ProjectPage::class)();
        }

        $data = [
            'project' => $project === null ? null : ['id' => $project->id, 'name' => $project->name],
            // Canonical subject links, built from the objects alone.
            'tasks' => $project === null ? [] : array_map(
                fn (TaskExample $task): SubjectItem => app(Subjects::class)->of($task),
                TaskExample::forProject($project),
            ),
            'projects' => array_map(fn (ProjectExample $example): array => [
                'id' => $example->id,
                'name' => $example->name,
                'url' => $rendering->url(route('projects.show', $example)),
                'editUrl' => $rendering->url(route('projects.edit', $example)),
            ], ProjectExample::examples()),
        ];

        return $rendering === Rendering::Blade
            ? view('experience.page', [...$data, 'experience' => Current::forPage(Current::pageId() ?? 'projects', $request->route()?->parameters() ?? [])])
            : Inertia::render('projects', $data);
    }
}
