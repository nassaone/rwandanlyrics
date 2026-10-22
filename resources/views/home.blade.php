<x-layouts.app title="Rwandan music, artists and lyrics">
@push('head')
    <x-json-ld :data="\App\Support\SeoSchema::website()" />
@endpush
    <h1 class="text-2xl font-semibold">Rwandanlyrics</h1>
    <p class="mt-1 text-muted">Discover Rwandan music, artists, albums and authorized lyrics.</p>

    @if ($latestSongs->isEmpty() && $artists->isEmpty() && $albums->isEmpty())
        <div class="mt-6">
            <x-empty-state title="No lyrics published yet" message="Content will appear here once the catalog is added." />
        </div>
    @endif

    @if ($latestSongs->isNotEmpty())
        <section class="mt-8" aria-labelledby="latest">
            <h2 id="latest" class="text-lg font-semibold">Latest lyrics</h2>
            <ul class="mt-2">
                @foreach ($latestSongs as $song)
                    <x-song-row :song="$song" />
                @endforeach
            </ul>
            <a href="{{ route('songs.index') }}" class="mt-2 inline-block text-sm text-accent-600 underline">All lyrics</a>
        </section>
    @endif

    @if ($artists->isNotEmpty())
        <section class="mt-8" aria-labelledby="artists">
            <h2 id="artists" class="text-lg font-semibold">Artists</h2>
            <ul class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ($artists as $artist)
                    <li><a href="{{ route('artists.show', $artist->slug) }}" class="block rounded-md border border-line p-3 text-sm font-medium hover:border-accent-600">{{ $artist->name }}</a></li>
                @endforeach
            </ul>
            <a href="{{ route('artists.index') }}" class="mt-2 inline-block text-sm text-accent-600 underline">All artists</a>
        </section>
    @endif

    @if ($albums->isNotEmpty())
        <section class="mt-8" aria-labelledby="albums">
            <h2 id="albums" class="text-lg font-semibold">Albums</h2>
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

    @if ($genres->isNotEmpty())
        <section class="mt-8" aria-labelledby="genres">
            <h2 id="genres" class="text-lg font-semibold">Genres</h2>
            <ul class="mt-2 flex flex-wrap gap-2">
                @foreach ($genres as $genre)
                    <li><a href="{{ route('genres.show', $genre->slug) }}"><x-badge class="px-3 py-1.5 text-sm">{{ $genre->name }}</x-badge></a></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
