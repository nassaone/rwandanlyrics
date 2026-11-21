@props(['route', 'tabs', 'sort', 'default', 'genres', 'genre' => null])
@php
    $link = fn ($sortKey, $genreSlug) => route($route, array_filter([
        'sort' => $sortKey === $default ? null : $sortKey,
        'genre' => $genreSlug,
    ]));
@endphp
<nav aria-label="Sort" class="mt-4 flex flex-wrap gap-2">
    @foreach ($tabs as $key => $label)
        <a href="{{ $link($key, $genre?->slug) }}"
           @if ($sort === $key) aria-current="true" @endif
           class="inline-flex min-h-11 items-center rounded-full px-4 text-sm font-medium {{ $sort === $key ? 'bg-accent-600 text-on-accent' : 'border border-line text-ink hover:bg-accent-50' }}">{{ $label }}</a>
    @endforeach
</nav>
@if ($genres->isNotEmpty())
    <nav aria-label="Filter by genre" class="-mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
        <a href="{{ $link($sort, null) }}"
           @if (! $genre) aria-current="true" @endif
           class="inline-flex min-h-9 shrink-0 items-center rounded-full px-3 text-sm {{ ! $genre ? 'bg-sun text-on-sun font-medium' : 'border border-line text-muted hover:text-ink' }}">All genres</a>
        @foreach ($genres as $g)
            <a href="{{ $link($sort, $g->slug) }}"
               @if ($genre && $genre->id === $g->id) aria-current="true" @endif
               class="inline-flex min-h-9 shrink-0 items-center rounded-full px-3 text-sm {{ $genre && $genre->id === $g->id ? 'bg-sun text-on-sun font-medium' : 'border border-line text-muted hover:text-ink' }}">{{ $g->name }}</a>
        @endforeach
    </nav>
@endif
