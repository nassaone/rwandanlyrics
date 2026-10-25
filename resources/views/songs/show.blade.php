@php($artistNames = $song->artists->pluck('name')->join(', '))
<x-layouts.app
    :title="$song->seo_title ?: $song->title.' lyrics'.($artistNames ? ' - '.$artistNames : '')"
    :description="$song->seo_description ?: 'Lyrics of '.$song->title.($artistNames ? ' by '.$artistNames : '').' on Rwandanlyrics.'"
    :robots="$song->noindex ? 'noindex,follow' : 'index,follow'">
@push('head')
    <x-json-ld :data="\App\Support\SeoSchema::song($song)" />
@endpush
    <x-breadcrumbs :items="['Home' => route('home'), 'Lyrics' => route('songs.index'), $song->title => null]" />

    <article>
        <x-media-image :media="$song->cover" class="mb-4 max-h-80 rounded-md" />
    <h1>{{ $song->title }}</h1>

        <p class="mt-1 text-muted">
            @foreach ($song->artists as $artist)
                @if (in_array($artist->id, $linkableArtistIds, true))
                    <a href="{{ route('artists.show', $artist->slug) }}" class="text-accent-600 hover:underline">{{ $artist->name }}</a>@else{{ $artist->name }}@endif{{ $loop->last ? '' : ', ' }}
            @endforeach
        </p>

        <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted">
            @if ($album)
                <div><dt class="inline">Album:</dt> <dd class="inline"><a href="{{ route('albums.show', $album->slug) }}" class="text-accent-600 hover:underline">{{ $album->title }}</a></dd></div>
            @endif
            @if ($song->release_date)
                <div><dt class="inline">Released:</dt> <dd class="inline">{{ $song->release_date->format('j F Y') }}</dd></div>
            @endif
            @if ($song->language)
                <div><dt class="inline">Language:</dt> <dd class="inline">{{ $song->language->name }}</dd></div>
            @endif
            @if ($song->rights_holder)
                <div><dt class="inline">Rights holder:</dt> <dd class="inline">{{ $song->rights_holder }}</dd></div>
            @endif
        </dl>

        @if ($song->genres->isNotEmpty())
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($song->genres as $genre)
                    <li><a href="{{ route('genres.show', $genre->slug) }}"><x-badge>{{ $genre->name }}</x-badge></a></li>
                @endforeach
            </ul>
        @endif

        @if ($song->description)
            <p class="mt-4 whitespace-pre-line text-muted">{{ $song->description }}</p>
        @endif

        <x-ad-slot name="song_top" />

        <section class="mt-6" aria-labelledby="lyrics-heading">
            <h2 id="lyrics-heading" class="sr-only">Lyrics</h2>
            @if ($song->currentLyric)
                <div class="lyrics mt-2 border-l-4 border-sun bg-surface px-5 py-6 sm:px-8">{{ $song->currentLyric->body }}</div>
            @else
                <x-empty-state title="Lyrics are not available for this song" />
            @endif
        </section>

        <x-ad-slot name="song_bottom" />

        <div class="mt-6" x-data>
            <x-button variant="secondary" @click="navigator.share ? navigator.share({ title: document.title, url: location.href }) : navigator.clipboard.writeText(location.href)">Share</x-button>
        </div>
    <footer class="mt-8 flex flex-wrap gap-x-6 gap-y-1 border-t border-line pt-3 text-sm text-muted [&_a]:inline-flex [&_a]:min-h-11 [&_a]:items-center">
            <a href="{{ route('legal.corrections') }}" class="hover:text-accent-600">Report an error</a>
            @can('create', \App\Models\Submission::class)
                <a href="{{ route('contribute.create', ['song' => $song->slug]) }}" class="hover:text-accent-600">Suggest a correction</a>
            @endcan
            <a href="{{ route('legal.takedown') }}" class="hover:text-accent-600">Copyright and takedown requests</a>
        </footer>

    </article>

    @if ($related->isNotEmpty())
        <section class="mt-10" aria-labelledby="related">
            <h2 id="related" class="mt-1">Related songs</h2>
            <ul class="mt-2 md:grid md:grid-cols-2 md:gap-x-10">
                @foreach ($related as $item)
                    <x-song-row :song="$item" />
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
