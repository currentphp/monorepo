@props(['title' => null, 'description' => 'Current gives a Laravel application one description of its pages, breadcrumbs, menus, and subjects, which every part of the interface reads.'])
@php
    $fullTitle = $title === null ? 'Current: one description of your Laravel app’s experience' : $title.' · Current';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Current">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="theme-color" content="#0e7490">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="sitemap" type="application/xml" href="{{ route('sitemap') }}">
    <script>
        try {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark' || (theme === null && matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch {
            if (matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
        }
    </script>
    <link rel="stylesheet" href="/pagefind/pagefind-ui.css">
    <script src="/pagefind/pagefind-ui.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2 dark:focus:bg-zinc-900">Skip to content</a>

    @include('partials.top-bar')

    {{ $slot }}

    <footer class="border-t border-zinc-200 dark:border-zinc-800">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-zinc-500 sm:flex-row sm:justify-between sm:px-6 dark:text-zinc-400">
            <p>Current is MIT licensed. A <a href="https://wavy.dev" class="underline hover:text-zinc-800 dark:hover:text-zinc-200">Wavy</a> project.</p>
            <p>This site is built with Current. <a href="https://github.com/currentphp/monorepo/tree/main/apps/site" class="underline hover:text-zinc-800 dark:hover:text-zinc-200">See its source</a>.</p>
        </div>
    </footer>
</body>
</html>
