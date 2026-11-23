<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="application-name" content="{{ config('app.name') }}">
    <title>{{ $title ?? 'Projects' }} - {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/native.ts'])
    @if ($livewire ?? false) @livewireStyles @endif
</head>
<body class="playground">
    @php($rendering = \App\Rendering::forRequest(request()))
    <a href="#main-content" class="skip-link">Skip to content</a>
    <div class="shell">
        @include('experience.header')
        <main id="main-content" tabindex="-1">{{ $slot }}</main>
        <footer><nav aria-label="Footer">@include('experience.menu', ['items' => \Current\Facades\Current::menu('footer')])</nav></footer>
    </div>
    @if ($livewire ?? false) @livewireScripts @endif
</body>
</html>
