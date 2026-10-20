<x-layouts.app
    :title="$artist->seo_title ?: $artist->name.' - lyrics and songs'"
    :description="$artist->seo_description ?: (Str::limit(strip_tags((string) $artist->bio), 155) ?: 'Songs and lyrics by '.$artist->name.' on Rwandanlyrics.')"
    :robots="$artist->noindex ? 'noindex,follow' : 'index,follow'">
    <x-breadcrumbs :items="['Home' => route('home'), 'Artists' => route('artists.index'), $artist->name => null]" />

    <h1 class="text-2xl font-semibold">{{ $artist->name }}</h1>

    @if ($artist->genres->isNotEmpty())
        <ul class="mt-3 flex flex-wrap gap-2">
            @foreach ($artist->genres as $genre)
                <li><a href="{{ route('genres.show', $genre->slug) }}"><x-badge>{{ $genre->name }}</x-badge></a></li>
            @endforeach
        </ul>
    @endif

    @if ($artist->bio)
        <p class="mt-4 whitespace-pre-line">{{ $artist->bio }}</p>
    @endif

    @if ($albums->isNotEmpty())
        <section class="mt-8" aria-labelledby="albums">
            <h2 id="albums" class="text-lg font-semibold">Albums</h2>
            <ul class="mt-2">
                @foreach ($albums as $album)
                    <li class="border-b border-line py-3 last:border-0">
                        <a href="{{ route('albums.show', $album->slug) }}" class="font-medium hover:text-accent-600">{{ $album->title }}</a>
                        @if ($album->release_date)<span class="text-sm text-muted"> ({{ $album->release_date->format('Y') }})</span>@endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mt-8" aria-labelledby="songs">
        <h2 id="songs" class="text-lg font-semibold">Lyrics</h2>
        @if ($songs->isEmpty())
            <div class="mt-2"><x-empty-state title="No lyrics published for this artist yet" /></div>
        @else
            <ul class="mt-2">
                @foreach ($songs as $song)
                    <x-song-row :song="$song" />
                @endforeach
            </ul>
            <div class="mt-6">{{ $songs->links() }}</div>
        @endif
    </section>
</x-layouts.app>
