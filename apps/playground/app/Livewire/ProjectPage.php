<?php

declare(strict_types=1);

namespace App\Livewire;

use App\ExperienceData;
use App\ProjectExample;
use App\Rendering;
use App\TaskExample;
use App\TaskPresentation;
use Current\Actions\Actions;
use Current\Facades\Current;
use Current\Subjects\Subjects;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Js;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('experience.layout', ['livewire' => true])]
final class ProjectPage extends Component
{
    // Persist identity, never presentation. The page ID is server-authored.
    #[Locked]
    public ?int $projectId = null;

    #[Locked]
    public string $pageId = 'projects.edit';

    #[Locked]
    public bool $nested = false;

    #[Locked]
    public ?int $taskId = null;

    public int $ticks = 0;

    public string $name = '';

    public function mount(?ProjectExample $project = null, ?TaskExample $task = null, bool $nested = false): void
    {
        $this->projectId = $project === null ? null : (int) $project->getKey();
        $this->pageId = Current::pageId() ?? 'projects.edit';
        $this->nested = $nested;
        $this->taskId = $task?->id;
        $this->name = $project->name ?? '';
    }

    public function tick(): void
    {
        $this->ticks++;
    }

    public function saveName(): void
    {
        $this->rename($this->name);
    }

    public function rename(string $name): void
    {
        $project = ProjectExample::query()->findOrFail($this->projectId);
        Gate::authorize('rename', $project);
        $this->name = trim($name);
        $data = validator(['name' => $this->name], ['name' => ['required', 'string', 'max:100']])->validate();
        $this->resetValidation('name');
        $project->update($data);
        session()->flash('status', 'Project renamed.');
    }

    public function render(): View
    {
        $project = $this->projectId === null ? null : ProjectExample::query()->findOrFail($this->projectId);
        if ($project !== null) {
            Gate::authorize('view', $project);
        }
        $parameters = $project === null ? [] : ['project' => $project];
        if ($project !== null && $this->taskId !== null) {
            $parameters['task'] = TaskExample::findExample($project, (string) $this->taskId) ?? abort(404);
        }
        $experience = Current::forPage($this->pageId, $parameters);
        $rendering = Rendering::Livewire;
        if (! $this->nested) {
            $this->js('document.title = '.Js::from(($experience->title() ?? config('app.name')).' - '.config('app.name')).';');
        }
        $probe = [
            'component' => $this->nested ? 'child' : 'parent',
            'ambientRoute' => request()->route()?->getName(),
            'ambientParameters' => array_keys(request()->route()?->parameters() ?? []),
            'ambientPage' => Current::pageId(),
            'ambientSubject' => Current::subject()?->key(),
            'ambientMenus' => Current::menu('primary'),
            'ambientActions' => Current::actions(),
            'explicit' => ExperienceData::from($experience),
            'objectTitle' => $project === null ? null : app(Subjects::class)->title($project),
            'objectActions' => $project === null ? [] : app(Actions::class)->for($project),
            'userId' => Auth::id(),
            'ticks' => $this->ticks,
        ];

        return view('experience.livewire', [
            'probe' => $probe,
            'isTaskPage' => $this->pageId === 'projects.tasks' || in_array($this->pageId, ['tasks.create', 'tasks.edit'], true),
            'taskData' => $project === null ? null : TaskPresentation::data($this->pageId, $project, $parameters['task'] ?? null, $rendering),
            'experience' => $experience,
            'project' => $project === null ? null : ['id' => $project->id, 'name' => $project->name],
            'tasks' => $project === null ? [] : array_map(fn (TaskExample $task) => app(Subjects::class)->of($task), TaskExample::forProject($project)),
            'projects' => array_map(fn (ProjectExample $item): array => [
                'id' => $item->id, 'name' => $item->name,
                'url' => $rendering->url(route('projects.show', $item)),
                'editUrl' => $rendering->url(route('projects.edit', $item)),
            ], ProjectExample::examples()),
        ])->title($experience->title() ?? config('app.name'));
    }
}
