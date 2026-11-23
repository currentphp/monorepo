<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ExperienceData;
use App\Livewire\ProjectPage;
use App\ProjectExample;
use App\Rendering;
use App\TaskExample;
use App\TaskPresentation;
use Current\Facades\Current;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Task admin built on Current\Actions. Every button on these pages
 * comes from the action description; the routes enforce authorization and
 * never write, because the demo data is fixtures.
 */
final class TaskController extends Controller
{
    public function index(Request $request, ProjectExample $project): Response|View|HttpResponse
    {
        return $this->render($request, $project);
    }

    public function create(Request $request, ProjectExample $project): Response|View|HttpResponse
    {
        return $this->render($request, $project);
    }

    public function edit(Request $request, ProjectExample $project, TaskExample $task): Response|View|HttpResponse
    {
        return $this->render($request, $project, $task);
    }

    public function store(Request $request, ProjectExample $project): RedirectResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:100']]);

        return $this->done($project, 'Demo: the new task was not saved.');
    }

    public function update(Request $request, ProjectExample $project, TaskExample $task): RedirectResponse
    {
        Gate::authorize('update', $task);
        $request->validate(['title' => ['required', 'string', 'max:100']]);

        return $this->done($project, "Demo: changes to “{$task->title}” were not saved.");
    }

    public function archive(ProjectExample $project, TaskExample $task): RedirectResponse
    {
        Gate::authorize('archive', $task);

        return $this->done($project, "Demo: “{$task->title}” was not archived.");
    }

    private function render(Request $request, ProjectExample $project, ?TaskExample $task = null): Response|View|HttpResponse
    {
        $rendering = Rendering::forRequest($request);
        if ($rendering === Rendering::Json || $request->expectsJson()) {
            return response()->json(ExperienceData::from(Current::forPage(Current::pageId() ?? 'projects', $request->route()?->parameters() ?? [])));
        }
        if ($rendering === Rendering::Livewire) {
            return app(ProjectPage::class)();
        }
        $pageId = Current::pageId() ?? 'projects.tasks';
        $data = TaskPresentation::data($pageId, $project, $task, $rendering);

        return $rendering === Rendering::Blade
            ? view('experience.task-page', [...$data, 'experience' => Current::forPage($pageId, $request->route()?->parameters() ?? [])])
            : Inertia::render('tasks', $data);
    }

    private function done(ProjectExample $project, string $message): RedirectResponse
    {
        return redirect()->to(Rendering::forRequest(request())->url(route('tasks.index', $project)))->with('status', $message);
    }
}
