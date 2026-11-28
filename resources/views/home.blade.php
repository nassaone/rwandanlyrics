<x-layouts.app title="Rwandan lyrics: songs, artists and albums" :search-band="false">
@push('head')
    <x-json-ld :data="\App\Support\SeoSchema::website()" />
@endpush
    <section class="rounded-2xl bg-surface px-5 py-8 sm:px-10 sm:py-12">
        <div class="grid grid-cols-[minmax(0,1fr)] items-center gap-10 lg:grid-cols-[minmax(0,1fr)_17rem]">
            <div class="min-w-0">
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-6xl">Rwandan lyrics</h1>
                <div class="mt-4 flex h-1.5 w-24 overflow-hidden rounded-full" aria-hidden="true"><span class="flex-1 bg-accent-600"></span><span class="flex-1 bg-sun"></span><span class="flex-1 bg-sky"></span></div>
                <p class="mt-4 max-w-xl text-lg text-muted">Find the lyrics to Rwandan songs by artist, album and genre. Only authorized lyrics are published here.</p>
                <div class="mt-6 max-w-xl"><x-search-form /></div>
                <p class="mt-3 flex flex-wrap gap-x-6 text-sm font-medium">
                    <a href="{{ route('songs.index') }}" class="inline-flex min-h-11 items-center text-accent-600 hover:underline">Browse all lyrics</a>
                    <a href="{{ route('artists.index') }}" class="inline-flex min-h-11 items-center text-accent-600 hover:underline">Artists</a>
                    <a href="{{ route('genres.index') }}" class="inline-flex min-h-11 items-center text-accent-600 hover:underline">Genres</a>
                </p>
                @if ($counts['songs'] >= 10)
                    <ul class="mt-4 flex flex-wrap gap-x-8 gap-y-1 text-sm text-muted">
                        <li><span class="font-semibold text-ink">{{ number_format($counts['songs']) }}</span> lyrics</li>
                        <li><span class="font-semibold text-ink">{{ number_format($counts['artists']) }}</span> {{ \Illuminate\Support\Str::plural('artist', $counts['artists']) }}</li>
                        <li><span class="font-semibold text-ink">{{ number_format($counts['albums']) }}</span> {{ \Illuminate\Support\Str::plural('album', $counts['albums']) }}</li>
                    </ul>
                @endif
            </div>

            @if ($featured)
                @php($featuredCover = $featured->cover ?? $featured->album?->cover)
                <a href="{{ route('songs.show', $featured->slug) }}" class="group hidden rounded-2xl border border-line bg-page p-4 transition hover:border-accent-600 lg:block">
                    <p class="text-sm font-medium text-muted">{{ $popularSongs->isNotEmpty() ? 'Trending now' : 'Newest lyrics' }}</p>
                    <div class="mt-3"><x-avatar :media="$featuredCover" :name="$featured->title" shape="square" size="aspect-square h-auto w-full text-6xl" /></div>
                    <p class="mt-3 text-lg font-semibold group-hover:text-accent-600">{{ $featured->title }}</p>
                    <p class="text-sm text-muted">{{ $featured->artists->pluck('name')->join(', ') }}</p>
                    <span class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-md bg-accent-600 px-4 py-2 text-sm font-medium text-on-accent">Read lyrics</span>
                </a>
            @endif
        </div>
    </section>

    @if ($latestSongs->isEmpty() && $artists->isEmpty() && $albums->isEmpty())
        <div class="mt-6">
            <x-empty-state title="No lyrics published yet" message="Content will appear here once the catalog is added." />
        </div>
    @endif

    @if ($trending->isNotEmpty())
        <section class="mt-10" aria-labelledby="popular">
            <h2 id="popular" class="mt-1">Trending now</h2>
            <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($trending as $song)
                    @php($cover = $song->cover ?? $song->album?->cover)
                    <li>
                        <a href="{{ route('songs.show', $song->slug) }}" class="group flex h-full flex-col rounded-xl border border-line bg-page p-2.5 transition hover:border-accent-600 hover:bg-accent-50 sm:p-3">
                            <div class="relative">
                                <x-avatar :media="$cover" :name="$song->title" shape="square" size="aspect-square h-auto w-full text-4xl" />
                                <span class="absolute left-2 top-2 inline-flex size-7 items-center justify-center rounded-full bg-page text-xs font-bold text-ink shadow" aria-label="Rank {{ $loop->iteration }}">{{ $loop->iteration }}</span>
                            </div>
                            <div class="mt-3 min-w-0">
                                <p class="line-clamp-1 font-semibold group-hover:text-accent-600">{{ $song->title }}</p>
                                <p class="line-clamp-1 text-sm text-muted">{{ $song->artists->pluck('name')->join(', ') }}</p>
                            </div>
                        </a>
                    </li>
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
                    <li><a href="{{ route('artists.show', $artist->slug) }}" class="flex items-center gap-3 rounded-lg border border-line p-3 font-medium leading-snug hover:border-accent-600 hover:bg-accent-50"><x-avatar :media="$artist->image" :name="$artist->name" size="size-12" /><span class="min-w-0">{{ $artist->name }}</span></a></li>
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
            <a href="{{ route('genres.index') }}" class="mt-3 inline-flex min-h-11 items-center font-medium text-accent-600 hover:underline">All genres</a>
        </section>
    @endif
</x-layouts.app>
