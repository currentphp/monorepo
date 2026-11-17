@php
    use Current\Facades\Current;
@endphp
<x-layouts.site :title="$guide->title" :description="$guide->description">
    <div class="mx-auto flex max-w-7xl gap-10 px-4 sm:px-6">
        <aside class="hidden w-60 shrink-0 lg:block" aria-label="Documentation">
            <nav class="sticky top-16 max-h-[calc(100vh-4rem)] overflow-y-auto py-8 pr-2">
                @include('partials.docs-menu', ['items' => Current::menu('docs')])
            </nav>
        </aside>

        <main id="content" class="min-w-0 flex-1 py-8">
            <details class="mb-6 rounded-lg border border-zinc-200 lg:hidden dark:border-zinc-800">
                <summary class="cursor-pointer px-4 py-3 text-sm font-medium">Menu</summary>
                <nav class="px-3 pb-4" aria-label="Documentation">
                    @include('partials.docs-menu', ['items' => Current::menu('docs')])
                </nav>
            </details>

            @include('partials.breadcrumbs')

            <article data-pagefind-body>
                @if ($guide->banner !== null)
                    <p class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200" role="note">
                        {{ $guide->banner }}
                    </p>
                @endif

                <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $guide->title }}</h1>
                <p class="mt-3 text-lg text-zinc-600 dark:text-zinc-400">{{ $guide->description }}</p>

                <div class="prose mt-8 max-w-none prose-zinc dark:prose-invert prose-headings:scroll-mt-20 prose-a:text-accent-700 dark:prose-a:text-accent-400 prose-code:before:content-none prose-code:after:content-none">
                    {!! $rendered->html !!}
                </div>
            </article>

            {{-- Previous and next come from the docs menu until Current has a reader for them (board card WK-037). --}}
            @if ($sequence->previous !== null || $sequence->next !== null)
                <nav aria-label="Previous and next" class="mt-12 grid gap-4 border-t border-zinc-200 pt-6 sm:grid-cols-2 dark:border-zinc-800">
                    @if ($sequence->previous !== null)
                        <a href="{{ $sequence->previous->url }}" rel="prev" class="rounded-lg border border-zinc-200 p-4 hover:border-accent-500 dark:border-zinc-800">
                            <span class="block text-xs text-zinc-500">Previous</span>
                            <span class="font-medium">{{ $sequence->previous->label }}</span>
                        </a>
                    @else
                        <span></span>
                    @endif
                    @if ($sequence->next !== null)
                        <a href="{{ $sequence->next->url }}" rel="next" class="rounded-lg border border-zinc-200 p-4 text-right hover:border-accent-500 dark:border-zinc-800">
                            <span class="block text-xs text-zinc-500">Next</span>
                            <span class="font-medium">{{ $sequence->next->label }}</span>
                        </a>
                    @endif
                </nav>
            @endif

            <p class="mt-8 text-sm">
                <a href="https://github.com/currentphp/monorepo/edit/main/apps/site/content/{{ \Illuminate\Support\Str::after($guide->file, base_path('content/')) }}" class="text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Edit this page on GitHub</a>
            </p>
        </main>

        @if (count($rendered->headings) > 1)
            <aside class="hidden w-52 shrink-0 xl:block" aria-label="On this page">
                <nav class="sticky top-16 py-8 text-sm">
                    <p class="mb-3 font-semibold text-zinc-900 dark:text-white">On this page</p>
                    <ul class="space-y-2">
                        @foreach ($rendered->headings as $heading)
                            <li @class(['pl-3' => $heading['level'] === 3])>
                                <a href="#{{ $heading['id'] }}" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ $heading['text'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        @endif
    </div>
</x-layouts.site>
