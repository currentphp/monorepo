@php
    use Current\Facades\Current;
@endphp
<header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6">
        <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-white">
            Current<span class="text-accent-600 dark:text-accent-400">.</span>
        </a>

        <nav aria-label="Main" class="hidden sm:block">
            <ul class="flex gap-5 text-sm">
                @foreach (Current::menu('top') as $item)
                    <li>
                        <a href="{{ $item->url }}"
                           @if ($item->current) aria-current="page" @endif
                           @class([
                               'font-medium transition',
                               'text-accent-700 dark:text-accent-400' => $item->active,
                               'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => ! $item->active,
                           ])>{{ $item->label }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="ml-auto flex items-center gap-4">
            <div id="search" class="hidden w-56 md:block"></div>

            {{-- External links live here until menus support them (board card WK-016). --}}
            <a href="https://github.com/currentphp/monorepo" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">GitHub</a>
            <a href="https://packagist.org/packages/current/current" class="hidden text-sm text-zinc-600 hover:text-zinc-900 sm:inline dark:text-zinc-400 dark:hover:text-white">Packagist</a>

            <button type="button" data-theme-toggle class="rounded-md p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white" aria-label="Toggle dark mode">
                <svg class="size-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75 9.75 9.75 0 0 1 8.25 6c0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25 9.75 9.75 0 0 0 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
                <svg class="hidden size-5 dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
            </button>
        </div>
    </div>
</header>
