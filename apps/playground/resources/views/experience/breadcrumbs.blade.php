<nav aria-label="Breadcrumb" class="breadcrumbs"><ol>
    @foreach ($experience->breadcrumbs() as $crumb)
        <li>
            @if (! $loop->first) <span aria-hidden="true">/</span> @endif
            @if ($crumb->url !== null && ! $crumb->current)
                <a href="{{ $rendering->url($crumb->url) }}">{{ $crumb->label }}</a>
            @else
                <span @if ($crumb->current) aria-current="page" @endif>{{ $crumb->label }}</span>
            @endif
        </li>
    @endforeach
</ol></nav>
