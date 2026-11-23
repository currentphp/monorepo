@php($rendering = \App\Rendering::forRequest(request()))
<header class="masthead">
    <a class="brand" href="{{ route('home') }}">{{ config('app.name') }}</a>
    <span class="help">Demo mode · no account required</span>
</header>
<nav aria-label="Primary">@include('experience.menu', ['items' => \Current\Facades\Current::menu('primary')])</nav>
<nav class="rendering-switcher" aria-label="Rendering choices">
    @foreach ($rendering->choices(request()) as $choice)
        <a href="{{ $choice['url'] }}" @if ($choice['current']) aria-current="page" @endif>{{ $choice['label'] }}</a>
    @endforeach
</nav>
