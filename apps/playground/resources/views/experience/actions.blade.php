@php($visibleActions = array_values(array_filter($actions, fn ($action) => ! $action->current && $action->method !== 'HEAD')))
@if ($visibleActions !== [])
<ul class="actions">
    @foreach ($visibleActions as $action)
        <li data-action-id="{{ $action->id }}">
            @if ($action->method === 'GET')
                @if ($action->enabled)
                    <a class="button-link" href="{{ $rendering->url($action->url) }}" @if($action->destructive) data-destructive="true" @endif @if($action->reason) aria-describedby="{{ $action->id }}-reason" @endif @if($action->confirm) data-confirm="{{ $action->confirm }}" @endif>{{ $action->label }}</a>
                @else
                    <button type="button" class="button-link" disabled @if($action->destructive) data-destructive="true" @endif @if($action->reason) aria-describedby="{{ $action->id }}-reason" @endif>{{ $action->label }}</button>
                @endif
            @else
                <form action="{{ $rendering->url($action->url) }}" method="post" data-confirm="{{ $action->confirm }}">
                    @csrf
                    @if ($action->method !== 'POST') @method($action->method) @endif
                    <button type="submit" @disabled(! $action->enabled) @if($action->destructive) data-destructive="true" @endif @if($action->reason) aria-describedby="{{ $action->id }}-reason" @endif>{{ $action->label }}</button>
                </form>
            @endif
            @if ($action->reason) <p id="{{ $action->id }}-reason" class="help">{{ $action->reason }}</p> @endif
            @if ($action->confirm) <p class="help">{{ $action->confirm }}</p> @endif
        </li>
    @endforeach
</ul>
@endif
