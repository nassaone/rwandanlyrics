<x-layouts.app title="Rwandan lyrics: songs, artists and albums" :search-band="false">
@push('head')
    <x-json-ld :data="\App\Support\SeoSchema::website()" />
@endpush
    <section class="rounded-2xl bg-surface px-5 py-8 sm:px-10 sm:py-12">
        <h1 class="text-4xl font-extrabold tracking-tight sm:text-6xl">Rwandan lyrics</h1>
        <div class="mt-4 flex h-1.5 w-24 overflow-hidden rounded-full" aria-hidden="true"><span class="flex-1 bg-accent-600"></span><span class="flex-1 bg-sun"></span><span class="flex-1 bg-sky"></span></div>
        <p class="mt-4 max-w-xl text-lg text-muted">Find the lyrics to Rwandan songs by artist, album and genre. Only authorized lyrics are published here.</p>
        <div class="mt-6 max-w-xl"><x-search-form /></div>
        <p class="mt-3 flex flex-wrap gap-x-6 text-sm font-medium">
            <a href="{{ route('songs.index') }}" class="inline-flex min-h-11 items-center text-accent-600 hover:underline">Browse all lyrics</a>
            <a href="{{ route('artists.index') }}" class="inline-flex min-h-11 items-center text-accent-600 hover:underline">Artists</a>
            <a href="{{ route('genres.index') }}" class="inline-flex min-h-11 items-center text-accent-600 hover:underline">Genres</a>
        </p>
    </section>

    @if ($latestSongs->isEmpty() && $artists->isEmpty() && $albums->isEmpty())
        <div class="mt-6">
            <x-empty-state title="No lyrics published yet" message="Content will appear here once the catalog is added." />
        </div>
    @endif

    @if ($popularSongs->isNotEmpty())
        <section class="mt-10" aria-labelledby="popular">
            <h2 id="popular" class="mt-1">Trending now</h2>
            <ul class="mt-2 md:grid md:grid-cols-2 md:gap-x-10">
                @foreach ($popularSongs as $song)
                    <x-song-row :song="$song" />
                @endforeach
            </ul>
        </section>
    @endif


    @if ($latestSongs->isNotEmpty())
        <section class="mt-10" aria-labelledby="latest">
            <h2 id="latest" class="mt-1">Latest lyrics</h2>
            <ul class="mt-2 md:grid md:grid-cols-2 md:gap-x-10">
                @foreach ($latestSongs as $song)
                    <x-song-row :song="$song" />
                @endforeach
            </ul>
            <a href="{{ route('songs.index') }}" class="mt-3 inline-flex min-h-11 items-center font-medium text-accent-600 hover:underline">All lyrics</a>
        </section>
    @endif

    <x-ad-slot name="home_middle" />

    @if ($artists->isNotEmpty())
        <section class="mt-10" aria-labelledby="artists">
            <h2 id="artists" class="mt-1">Artists</h2>
            <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($artists as $artist)
                    <li><a href="{{ route('artists.show', $artist->slug) }}" class="flex items-center gap-3 rounded-lg border border-line p-3 font-medium leading-snug hover:border-accent-600 hover:bg-accent-50"><x-avatar :media="$artist->image" :name="$artist->name" /><span class="min-w-0">{{ $artist->name }}</span></a></li>
                @endforeach
            </ul>
            <a href="{{ route('artists.index') }}" class="mt-3 inline-flex min-h-11 items-center font-medium text-accent-600 hover:underline">All artists</a>
        </section>
    @endif

    @if ($albums->isNotEmpty())
        <section class="mt-10" aria-labelledby="albums">
            <h2 id="albums" class="mt-1">Albums</h2>
            <ul class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($albums as $album)
                    <li class="flex items-center gap-4 rounded-lg border border-line p-3 hover:border-accent-600"><x-avatar :media="$album->cover" :name="$album->title" shape="square" size="size-16" /><div class="min-w-0">
                        <a href="{{ route('albums.show', $album->slug) }}" class="font-medium hover:text-accent-600">{{ $album->title }}</a>
                        <p class="text-sm text-muted">{{ $album->artists->pluck('name')->join(', ') }}</p></div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($genres->isNotEmpty())
        <section class="mt-10" aria-labelledby="genres">
            <h2 id="genres" class="mt-1">Genres</h2>
            <ul class="mt-2 flex flex-wrap gap-2">
                @foreach ($genres as $genre)
                    <li><a href="{{ route('genres.show', $genre->slug) }}"><x-badge class="px-3 py-1.5 text-sm">{{ $genre->name }}</x-badge></a></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
