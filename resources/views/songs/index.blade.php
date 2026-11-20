<x-layouts.app title="Lyrics" description="Browse the latest Rwandan song lyrics on Vocal Vibes Nation.">
    @php
        $tabs = ['newest' => 'Newest', 'trending' => 'Trending', 'az' => 'A to Z'];
        $link = fn ($sortKey, $genreSlug) => route('songs.index', array_filter([
            'sort' => $sortKey === 'newest' ? null : $sortKey,
            'genre' => $genreSlug,
        ]));
        $filtered = $genre || $sort !== 'newest';
    @endphp

    <x-breadcrumbs :items="['Home' => route('home'), 'Lyrics' => null]" />
    <h1>Lyrics</h1>
    <p class="mt-1 text-sm text-muted">
        {{ number_format($total) }} {{ \Illuminate\Support\Str::plural('song', $total) }}@if ($genre) in {{ $genre->name }}@endif
        @if ($sort === 'trending') · based on views in the last 14 days @endif
    </p>

    <nav aria-label="Sort lyrics" class="mt-4 flex flex-wrap gap-2">
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

    <x-ad-slot name="list_top" />

    @if ($songs->isEmpty())
        <div class="mt-6">
            <x-empty-state :title="$filtered ? ($sort === 'trending' && ! $genre ? 'Nothing is trending yet' : 'No lyrics match these filters') : 'No lyrics published yet'" />
            @if ($filtered)
                <p class="mt-3 text-center"><a href="{{ route('songs.index') }}" class="text-accent-600 hover:underline">Clear filters</a></p>
            @endif
        </div>
    @else
        <ul class="mt-4 md:grid md:grid-cols-2 md:gap-x-10">
            @foreach ($songs as $song)
                <x-song-row :song="$song" />
            @endforeach
        </ul>
        @if ($songs instanceof \Illuminate\Contracts\Pagination\Paginator && $songs->hasPages())
            <div class="mt-6">{{ $songs->links() }}</div>
        @endif
    @endif
</x-layouts.app>
