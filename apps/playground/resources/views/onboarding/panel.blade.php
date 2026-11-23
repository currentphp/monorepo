<div class="page-heading"><h1>{{ $experience->title() }}</h1><p>{{ $experience->description() ?? 'A session-only signup simulation. Use the sample values; no account is created and no email is sent.' }}</p></div>
<nav aria-label="Onboarding steps" class="card">@include('experience.menu', ['items' => $steps])</nav>
<section class="card">
    @if ($step === 'intro')
        <h2>A small flow with changing context.</h2><p>Complete a demo profile, prepare a workspace, and review the result. Watch steps appear and action availability change. Reset whenever you want to start again.</p>
    @endif
    @php($submit = collect($experience->actions())->first(fn ($action) => $action->method === 'POST'))
    @if ($submit !== null)
        <form class="task-form" action="{{ $rendering->url($submit->url) }}" method="post" @if ($live ?? false) wire:submit="submit" wire:loading.attr="aria-busy" @if ($submit->confirm) wire:confirm="{{ $submit->confirm }}" @endif @else data-confirm="{{ $submit->confirm }}" @endif>
            @csrf
            @if ($step === 'profile')
                @include('experience.field', ['label' => 'Demo name', 'name' => 'name', 'value' => ($live ?? false) ? $this->name : old('name', $profile['name'] ?? 'Alex Taylor'), 'maxLength' => 80, 'autocomplete' => 'off'])
                @include('experience.field', ['label' => 'Example email', 'name' => 'email', 'type' => 'email', 'value' => ($live ?? false) ? $this->email : old('email', $profile['email'] ?? 'alex@example.test'), 'maxLength' => 120, 'autocomplete' => 'off'])
            @endif
            @if ($step === 'workspace')
                @include('experience.field', ['label' => 'Workspace name', 'name' => 'workspaceName', 'value' => ($live ?? false) ? $this->workspaceName : old('workspaceName', $workspaceName), 'maxLength' => 80, 'autocomplete' => 'off'])
            @endif
            @if ($step === 'review')
                <h2>{{ $experience->subjectTitle() }}</h2>
                <p class="help">{{ $profile === null ? 'Complete your demo profile first.' : 'Prepared for '.$profile['name'].' ('.$profile['email'].').' }}</p>
                <p>{{ $complete ? 'Your simulation is complete. Open Projects or reset to try another path.' : 'Finishing unlocks the Projects action below.' }}</p>
            @endif
            <button type="submit" @disabled(! $submit->enabled) @if ($live ?? false) wire:loading.attr="disabled" @endif>{{ $submit->label }}</button>
            @if ($submit->reason !== null) <p class="help">{{ $submit->reason }}</p> @endif
        </form>
    @endif
    @include('experience.actions', ['actions' => array_values(array_filter($experience->actions(), fn ($action) => $action->method === 'GET'))])
</section>
<form action="{{ $resetUrl }}" method="post">@csrf<button type="submit">Reset simulation</button></form>
