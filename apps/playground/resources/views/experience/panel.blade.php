<div class="page-heading">
    <h1>{{ $experience->title() }}</h1>
    <p>{{ $experience->description() ?? 'Explore your projects and their tasks.' }}</p>
    @if ($experience->subject() !== null)
        <p>Viewing {{ $experience->subject()->kind }} <strong>{{ $experience->subject()->label }}</strong> ({{ $experience->subject()->key() }}).</p>
    @endif
    @include('experience.actions', ['actions' => array_values(array_filter($experience->actions(), fn ($action) => $action->id !== 'project.rename'))])
</div>
@if ($project !== null)
    <section class="card">
        <h2>{{ $project['name'] }}</h2>
        <p class="help">{{ $experience->pageId() === 'projects.edit' ? 'Update the project name. Changes appear in every view.' : 'View project details, manage its name, or explore its tasks.' }}</p>
        <div class="actions">
            <a href="{{ $rendering->url(route('projects.show', $project['id'])) }}">View project</a>
            <a href="{{ $rendering->url(route('projects.edit', $project['id'])) }}">Edit project</a>
        </div>
        @if ($experience->pageId() === 'projects.edit')
            @php($rename = collect($experience->actions())->firstWhere('id', 'project.rename'))
            @if ($rename !== null)
                <form class="rename-form" action="{{ $rename->url }}" method="post" @if($live ?? false) wire:submit="saveName" wire:loading.attr="aria-busy" @if($rename->confirm) wire:confirm="{{ $rename->confirm }}" @endif @else data-confirm="{{ $rename->confirm }}" @endif>
                    @csrf
                    @include('experience.field', ['label' => 'Project name', 'name' => 'name', 'value' => ($live ?? false) ? $this->name : old('name', $project['name']), 'maxLength' => 100])
                    <button type="submit" @disabled(! $rename->enabled) @if($live ?? false) wire:loading.attr="disabled" @endif>Rename</button>
                </form>
                @if ($rename->reason !== null) <p class="help">{{ $rename->reason }}</p> @endif
                @if ($rename->confirm !== null) <p class="help">{{ $rename->confirm }}</p> @endif
            @endif
        @endif
        @if ($tasks !== [])
            <div class="project-tasks"><h3>Tasks</h3><ul class="task-links">
                @foreach ($tasks as $task)
                    <li><a href="{{ $rendering->url($task->url) }}">{{ $task->label }}</a></li>
                @endforeach
            </ul></div>
        @endif
    </section>
@endif
<section class="project-grid" aria-label="Projects">
    @foreach ($projects as $item)
        <a class="card" href="{{ $item['url'] }}"><h2>{{ $item['name'] }}</h2><p class="help">Explore project</p></a>
    @endforeach
</section>
