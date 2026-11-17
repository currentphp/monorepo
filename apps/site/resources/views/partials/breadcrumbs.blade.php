@php
    use Current\Facades\Current;
@endphp
<nav aria-label="Breadcrumb" class="mb-6">
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
        @foreach (Current::breadcrumbs() as $crumb)
            <li class="flex items-center gap-1.5">
                @unless ($loop->first)
                    <span aria-hidden="true">/</span>
                @endunless
                @if ($crumb->current)
                    <span aria-current="page" class="text-zinc-900 dark:text-white">{{ $crumb->label }}</span>
                @elseif ($crumb->url !== null)
                    <a href="{{ $crumb->url }}" class="hover:text-zinc-900 dark:hover:text-white">{{ $crumb->label }}</a>
                @else
                    <span>{{ $crumb->label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
