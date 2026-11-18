{{-- The docs sidebar: Current's derived menu over content/docs. --}}
<ul class="space-y-1">
    @foreach ($items as $item)
        <li>
            @if ($item->url === null)
                <p class="mt-6 mb-2 text-xs font-semibold tracking-wide text-zinc-900 uppercase dark:text-white">{{ $item->label }}</p>
            @else
                <a href="{{ $item->url }}"
                   @if ($item->current) aria-current="page" @endif
                   @class([
                       'block rounded-md px-3 py-1.5 text-sm transition',
                       'bg-accent-50 font-medium text-accent-700 dark:bg-accent-500/10 dark:text-accent-400' => $item->current,
                       'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-900 dark:hover:text-white' => ! $item->current,
                   ])>{{ $item->label }}</a>
            @endif

            @if ($item->children !== [])
                @include('partials.docs-menu', ['items' => $item->children])
            @endif
        </li>
    @endforeach
</ul>
