<x-layouts.site>
    <main id="content">
        <section class="border-b border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-28">
                <p class="text-sm font-medium text-accent-700 dark:text-accent-400">For Laravel 13</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                    One description of your app’s experience
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                    Where users are, how they move around, and what they’re looking at usually ends up scattered across route names,
                    breadcrumb arrays, menu config, and frontend code. Current describes your pages once, and breadcrumbs, menus,
                    and your frontend all read the same description.
                </p>
                <div class="mx-auto mt-8 max-w-md rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 font-mono text-sm dark:border-zinc-800 dark:bg-zinc-900">
                    composer require current/current
                </div>
                <div class="mt-8 flex justify-center gap-3">
                    <a href="{{ route('docs.getting-started.installation') }}" class="rounded-lg bg-accent-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-accent-700">Get started</a>
                    <a href="https://github.com/currentphp/monorepo" class="rounded-lg border border-zinc-300 px-5 py-2.5 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">GitHub</a>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6">
            <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">Describe pages once</h2>
            <p class="mt-3 max-w-2xl text-zinc-600 dark:text-zinc-400">
                Each page has a stable ID, a place in the hierarchy, a label, and an optional named route. Your routes and controllers stay as they are.
            </p>
            <div class="prose mt-6 max-w-none dark:prose-invert">
                {!! app(\App\Docs\MarkdownRenderer::class)->render(<<<'MD'
                ```php
                $pages->register(
                    Page::make('projects')->label('Projects')->route('projects.index')->children(
                        Page::make('projects.show')
                            ->route('projects.show')
                            ->label(fn (Context $context) => $context->parameter('project')->name),
                    ),
                );

                $menus->derive('primary', 'dashboard', 'projects');
                ```
                MD)->html !!}
            </div>
        </section>

        <section class="mx-auto max-w-5xl px-4 pb-20 sm:px-6">
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ([
                    ['Breadcrumbs and menus', 'Breadcrumbs follow the page tree. Menus follow it too, or arrange pages their own way, with current and active states that always agree.', 'docs.getting-started.breadcrumbs'],
                    ['Subjects', 'The records users look at, such as a project or an invoice, declared once and linked to the pages that present them.', 'docs.subjects'],
                    ['Inertia', 'One shared prop with the current page, breadcrumbs, menus, and subject, plus TypeScript types for your React or Vue pages.', 'docs.inertia'],
                    ['Inspect and test', 'Artisan commands list and check the description, and test assertions check what a request resolved.', 'docs.inspecting-and-testing'],
                ] as [$heading, $text, $route])
                    <a href="{{ route($route) }}" class="rounded-xl border border-zinc-200 p-6 transition hover:border-accent-500 dark:border-zinc-800">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $heading }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
                    </a>
                @endforeach
            </div>
            <p class="mt-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                Current describes; your application executes. Routes, controllers, authorization, and rendering stay yours.
            </p>
        </section>
    </main>
</x-layouts.site>
