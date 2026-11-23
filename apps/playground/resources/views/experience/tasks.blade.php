<div class="page-heading"><h1>{{ $experience->title() }}</h1>@include('experience.actions', ['actions' => $experience->actions()])</div>
@if ($form !== null)
    <form action="{{ $form['url'] }}" method="post" class="card task-form">
        @csrf
        @if ($form['method'] !== 'post') @method($form['method']) @endif
        @include('experience.field', ['label' => 'Title', 'name' => 'title', 'value' => old('title', $form['value']), 'maxLength' => 100, 'live' => false])
        <button type="submit">Save</button>
    </form>
@else
    <ul class="card task-links">
        @foreach ($rows as $row)
            <li class="task-row">
                <a href="{{ $rendering->url($row['subject']->url) }}">{{ $row['subject']->label }}</a>
                @include('experience.actions', ['actions' => $row['actions']])
            </li>
        @endforeach
    </ul>
@endif
