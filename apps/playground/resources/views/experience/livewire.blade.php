<div data-example-content>
    @if (! $nested)
        @include('experience.feedback')
        @include('experience.breadcrumbs', ['rendering' => \App\Rendering::Livewire])
        @if ($isTaskPage)
            @include('experience.tasks', [...$taskData, 'rendering' => \App\Rendering::Livewire])
        @else
            @include('experience.panel', ['rendering' => \App\Rendering::Livewire, 'live' => true])
        @endif
    @endif
    <div hidden data-renderer-probe>
        <pre data-probe>{{ json_encode($probe, JSON_THROW_ON_ERROR) }}</pre>
        @if ($nested)<h2>{{ $experience->subjectTitle() }}</h2><p>{{ $experience->subjectDescription() }}</p>@endif
    @if (! $nested && $projectId !== null && $pageId === 'projects.edit')
        <livewire:project-page :project="\App\ProjectExample::query()->findOrFail($projectId)" :nested="true" :key="'child-'.$projectId" />
    @endif
    </div>
</div>
