<div>
    <h1>{{ $probe['explicit']['title'] }}</h1>
    <pre data-probe>{{ json_encode($probe, JSON_THROW_ON_ERROR) }}</pre>
    <livewire:project-child :project-id="$projectId" :key="'child-'.$projectId" />
</div>
