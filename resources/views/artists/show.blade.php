<x-layouts.app
    :title="$artist->seo_title ?: $artist->name.' - lyrics and songs'"
    :description="$artist->seo_description ?: (Str::limit(strip_tags((string) $artist->bio), 155) ?: 'Songs and lyrics by '.$artist->name.' on Rwandanlyrics.')"
    :robots="$artist->noindex ? 'noindex,follow' : 'index,follow'">
@push('head')
    <x-json-ld :data="\App\Support\SeoSchema::artist($artist)" />
@endpush
    <x-breadcrumbs :items="['Home' => route('home'), 'Artists' => route('artists.index'), $artist->name => null]" />

    <header class="overflow-hidden rounded-2xl border border-line">
        @if ($artist->banner)
            <img src="{{ $artist->banner->url() }}" alt="" class="h-40 w-full object-cover sm:h-56">
        @else
            <div class="h-24 bg-accent-100 sm:h-32" aria-hidden="true"></div>
        @endif
        <div class="flex flex-col gap-4 px-5 pb-6 sm:flex-row sm:items-end sm:px-8">
            <div class="-mt-12 shrink-0 sm:-mt-16">
                <x-avatar :media="$artist->image" :name="$artist->name" size="size-24 sm:size-32 text-4xl ring-4 ring-page" />
            </div>
            <div class="min-w-0">
                <h1>{{ $artist->name }}</h1>
                <p class="mt-1 text-sm text-muted">{{ $songs->total() }} {{ \Illuminate\Support\Str::plural('lyric', $songs->total()) }}, {{ $albums->count() }} {{ \Illuminate\Support\Str::plural('album', $albums->count()) }}</p>
                @if ($artist->genres->isNotEmpty())
        <ul class="mt-3 flex flex-wrap gap-2">
            @foreach ($artist->genres as $genre)
                <li><a href="{{ route('genres.show', $genre->slug) }}"><x-badge>{{ $genre->name }}</x-badge></a></li>
            @endforeach
        </ul>
    @endif
            </div>
        </div>
    </header>

    @if ($artist->bio)
        <p class="mt-6 max-w-2xl whitespace-pre-line">{{ $artist->bio }}</p>
    @endif

    @if ($albums->isNotEmpty())
        <section class="mt-8" aria-labelledby="albums">
            <h2 id="albums" class="mt-1">Albums</h2>
            <ul class="mt-2 md:grid md:grid-cols-2 md:gap-x-10">
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
        <h2 id="songs" class="mt-1">Lyrics</h2>
        @if ($songs->isEmpty())
            <div class="mt-2"><x-empty-state title="No lyrics published for this artist yet" /></div>
        @else
            <ul class="mt-2 md:grid md:grid-cols-2 md:gap-x-10">
                @foreach ($songs as $song)
                    <x-song-row :song="$song" />
                @endforeach
            </ul>
            <div class="mt-6">{{ $songs->links() }}</div>
        @endif
    </section>
</x-layouts.app>
