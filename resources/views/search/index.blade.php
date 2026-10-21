<x-layouts.app :title="$searched ? 'Search results for '.$q : 'Search'" robots="noindex,follow">
    <h1 class="text-2xl font-semibold">{{ $searched ? 'Results for "'.$q.'"' : 'Search' }}</h1>

    @if (! $searched)
        <p class="mt-2 text-muted">
            @if ($q === '')
                Type a song, artist or album name in the box above.
            @else
                Please type at least {{ \App\Services\SearchService::MIN_LENGTH }} characters.
            @endif
        </p>
    @else
        @php $none = $songs->total() === 0 && $artists->isEmpty() && $albums->isEmpty(); @endphp

        @if ($none)
            <div class="mt-6">
                <x-empty-state title="No results" message="Try a different spelling, or search for just the artist name." />
            </div>
        @endif

        @if ($artists->isNotEmpty())
            <section class="mt-8" aria-labelledby="artists-results">
                <h2 id="artists-results" class="text-lg font-semibold">Artists</h2>
                <ul class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($artists as $artist)
                        <li><a href="{{ route('artists.show', $artist->slug) }}" class="block rounded-md border border-line p-3 text-sm font-medium hover:border-accent-600">{{ $artist->name }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($albums->isNotEmpty())
            <section class="mt-8" aria-labelledby="albums-results">
                <h2 id="albums-results" class="text-lg font-semibold">Albums</h2>
                <ul class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($albums as $album)
                        <li class="rounded-md border border-line p-3">
                            <a href="{{ route('albums.show', $album->slug) }}" class="font-medium hover:text-accent-600">{{ $album->title }}</a>
                            <p class="text-sm text-muted">{{ $album->artists->pluck('name')->join(', ') }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($songs->total() > 0)
            <section class="mt-8" aria-labelledby="songs-results">
                <h2 id="songs-results" class="text-lg font-semibold">Songs ({{ $songs->total() }})</h2>
                <ul class="mt-2">
                    @foreach ($songs as $song)
                        <x-song-row :song="$song" />
                    @endforeach
                </ul>
                <div class="mt-6">{{ $songs->links() }}</div>
            </section>
        @endif
    @endif
</x-layouts.app>
