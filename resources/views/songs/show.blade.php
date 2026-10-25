@php($artistNames = $song->artists->pluck('name')->join(', '))
@php($hasInfo = $album || $song->track_number || $song->release_date || $song->language || $song->rights_holder)
<x-layouts.app
    :title="$song->seo_title ?: $song->title.' lyrics'.($artistNames ? ' - '.$artistNames : '')"
    :description="$song->seo_description ?: ('Lyrics of '.$song->title.($artistNames ? ' by '.$artistNames : '').' on Rwandanlyrics.')"
    :robots="$song->noindex ? 'noindex,follow' : 'index,follow'">
    @push('head')
        <x-json-ld :data="\App\Support\SeoSchema::song($song)" />
    @endpush
    <x-breadcrumbs :items="['Home' => route('home'), 'Lyrics' => route('songs.index'), $song->title => null]" />

    <article class="lg:grid lg:grid-cols-[minmax(0,1fr)_19rem] lg:gap-10">
        <div class="min-w-0">
            <header class="flex items-start gap-4 sm:gap-5">
                <x-avatar :media="$song->cover" :name="$song->title" shape="square" size="size-20 sm:size-28 text-3xl" />
                <div class="min-w-0">
                    <h1>{{ $song->title }}</h1>
                    <p class="mt-1 text-muted">
                        @foreach ($song->artists as $artist)
                            @if (in_array($artist->id, $linkableArtistIds, true))
                                <a href="{{ route('artists.show', $artist->slug) }}" class="text-accent-600 hover:underline">{{ $artist->name }}</a>@else{{ $artist->name }}@endif{{ $loop->last ? '' : ', ' }}
                        @endforeach
                    </p>
                    @if ($song->genres->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($song->genres as $genre)
                                <li><a href="{{ route('genres.show', $genre->slug) }}"><x-badge>{{ $genre->name }}</x-badge></a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </header>

            @if ($song->description)
                <p class="mt-5 max-w-2xl whitespace-pre-line text-muted">{{ $song->description }}</p>
            @endif

            @if ($song->youtube_id)
                <section class="mt-6" aria-labelledby="video-heading">
                    <h2 id="video-heading" class="sr-only">Video</h2>
                    <div class="aspect-video overflow-hidden rounded-xl border border-line bg-surface">
                        <iframe class="size-full" src="https://www.youtube-nocookie.com/embed/{{ $song->youtube_id }}?rel=0" title="{{ $song->title }} on YouTube" loading="lazy" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                    <p class="mt-2 text-sm"><a href="{{ \App\Support\YouTube::watchUrl($song->youtube_id) }}" target="_blank" rel="noopener noreferrer" class="text-accent-600 hover:underline">Open on YouTube</a></p>
                </section>
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
        </div>

        <aside class="mt-8 space-y-6 lg:mt-0 lg:sticky lg:top-24 lg:self-start" aria-label="Song information and reporting">
            @if ($hasInfo)
                <section class="rounded-xl border border-line p-5" aria-labelledby="about-heading">
                    <h2 id="about-heading" class="text-base">About this song</h2>
                    <dl class="mt-3 divide-y divide-line text-sm">
                        @if ($album)
                            <div class="flex justify-between gap-4 py-2">
                                <dt class="text-muted">Album</dt>
                                <dd class="text-right"><a href="{{ route('albums.show', $album->slug) }}" class="text-accent-600 hover:underline">{{ $album->title }}</a></dd>
                            </div>
                        @endif
                        @if ($song->track_number)
                            <div class="flex justify-between gap-4 py-2"><dt class="text-muted">Track</dt><dd>{{ $song->track_number }}</dd></div>
                        @endif
                        @if ($song->release_date)
                            <div class="flex justify-between gap-4 py-2"><dt class="text-muted">Released</dt><dd>{{ $song->release_date->format('j F Y') }}</dd></div>
                        @endif
                        @if ($song->language)
                            <div class="flex justify-between gap-4 py-2"><dt class="text-muted">Language</dt><dd>{{ $song->language->name }}</dd></div>
                        @endif
                        @if ($song->rights_holder)
                            <div class="flex justify-between gap-4 py-2"><dt class="text-muted">Rights holder</dt><dd class="text-right">{{ $song->rights_holder }}</dd></div>
                        @endif
                    </dl>
                </section>
            @endif

            <section class="rounded-xl border border-line bg-surface p-5" aria-labelledby="report-heading">
                <h2 id="report-heading" class="text-base">Something wrong with these lyrics?</h2>
                <p class="mt-1 text-sm text-muted">Tell us about a mistake or send the corrected text. Rights holders can ask for removal on the takedown page.</p>
                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap lg:flex-col">
                    <a href="{{ route('legal.corrections') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-line bg-page px-4 py-2 text-sm font-medium hover:bg-accent-50">Report an error</a>
                    @can('create', \App\Models\Submission::class)
                        <a href="{{ route('contribute.create', ['song' => $song->slug]) }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-line bg-page px-4 py-2 text-sm font-medium hover:bg-accent-50">Suggest a correction</a>
                    @endcan
                    <a href="{{ route('legal.takedown') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-line bg-page px-4 py-2 text-sm font-medium hover:bg-accent-50">Copyright and takedown requests</a>
                </div>
            </section>
        </aside>
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
