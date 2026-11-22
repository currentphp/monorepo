<?php

declare(strict_types=1);

namespace Prototype;

use Current\Actions\Actions;
use Current\Facades\Current;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class ProjectChild extends Component
{
    #[Locked]
    public int $projectId;

    public int $ticks = 0;

    public function tick(): void
    {
        $this->ticks++;
    }

    public function rename(string $name): void
    {
        $project = Project::query()->findOrFail($this->projectId);
        Gate::authorize('rename', $project);
        $project->update(['name' => $name]);
    }

    public function render(): View
    {
        $project = Project::query()->findOrFail($this->projectId);
        $probe = [
            'component' => 'child',
            'ambientRoute' => request()->route()?->getName(),
            'ambientPage' => Current::pageId(),
            'explicit' => (new PageReader('projects.edit', $project))->describe(),
            'objectActions' => app(Actions::class)->for($project),
            'ticks' => $this->ticks,
        ];
        file_put_contents(base_path('.runtime/events.jsonl'), json_encode($probe, JSON_THROW_ON_ERROR)."\n", FILE_APPEND);

        return view('child', ['probe' => $probe]);
    }
}
