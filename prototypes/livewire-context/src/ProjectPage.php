<?php

declare(strict_types=1);

namespace Prototype;

use Current\Actions\Actions;
use Current\Facades\Current;
use Current\Subjects\Subjects;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class ProjectPage extends Component
{
    // Persist identity, never presentation. The page ID is server-authored.
    #[Locked]
    public int $projectId;

    public int $ticks = 0;

    public function mount(Project $project): void
    {
        $this->projectId = (int) $project->getKey();
    }

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
        // Requery on EVERY render; a locked ID does not make an old model fresh.
        $project = Project::query()->findOrFail($this->projectId);
        $reader = new PageReader('projects.edit', $project);
        $probe = [
            'component' => 'parent',
            'ambientRoute' => request()->route()?->getName(),
            'ambientParameters' => array_keys(request()->route()?->parameters() ?? []),
            'ambientPage' => Current::pageId(),
            'ambientSubject' => Current::subject()?->key(),
            'ambientMenus' => Current::menu('primary'),
            'ambientActions' => Current::actions(),
            'explicit' => $reader->describe(),
            'independentReader' => (new PageReader('projects.show', Project::query()->findOrFail($this->projectId === 42 ? 77 : 42)))->describe(),
            'originalReaderAfterOther' => $reader->describe(),
            'objectTitle' => app(Subjects::class)->title($project),
            'objectActions' => app(Actions::class)->for($project),
            'userId' => Auth::id(),
            'ticks' => $this->ticks,
        ];
        file_put_contents(base_path('.runtime/events.jsonl'), json_encode($probe, JSON_THROW_ON_ERROR)."\n", FILE_APPEND);

        return view('project', ['probe' => $probe]);
    }
}
