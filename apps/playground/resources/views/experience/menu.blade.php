<ul class="menu">
    @foreach ($items as $item)
        <li>
            @if ($item->url !== null)
                <a href="{{ $rendering->url($item->url) }}" @if ($item->current) aria-current="page" @endif @if ($item->active) data-active="true" @endif>{{ $item->label }}</a>
            @else
                <strong>{{ $item->label }}</strong>
            @endif
            @if ($item->children !== []) @include('experience.menu', ['items' => $item->children]) @endif
        </li>
    @endforeach
</ul>
