@php($artistNames = $album->artists->pluck('name')->join(', '))
<x-layouts.app
    :title="$album->seo_title ?: $album->title.($artistNames ? ' - '.$artistNames : '')"
    :description="$album->seo_description ?: ($album->title.' tracklist and lyrics on Rwandanlyrics.')"
    :robots="$album->noindex ? 'noindex,follow' : 'index,follow'">
    <x-breadcrumbs :items="['Home' => route('home'), $album->title => null]" />

    <x-media-image :media="$album->cover" class="mb-4 max-h-80 rounded-md" />
    <h1 class="text-2xl font-semibold">{{ $album->title }}</h1>
    <p class="mt-1 text-muted">
        @foreach ($album->artists as $artist)
            @if (in_array($artist->id, $linkableArtistIds, true))
                <a href="{{ route('artists.show', $artist->slug) }}" class="text-accent-600 hover:underline">{{ $artist->name }}</a>@else{{ $artist->name }}@endif{{ $loop->last ? '' : ', ' }}
        @endforeach
    </p>
    <p class="mt-1 text-sm text-muted">
        {{ ucfirst($album->type->value) }}@if ($album->release_date) &middot; {{ $album->release_date->format('j F Y') }}@endif
    </p>

    @if ($album->description)
        <p class="mt-4 whitespace-pre-line">{{ $album->description }}</p>
    @endif

    <section class="mt-8" aria-labelledby="tracks">
        <h2 id="tracks" class="text-lg font-semibold">Tracklist</h2>
        @if ($tracks->isEmpty())
            <div class="mt-2"><x-empty-state title="No lyrics published for this album yet" /></div>
        @else
            <ol class="mt-2">
                @foreach ($tracks as $track)
                    <li class="flex gap-3 border-b border-line py-3 last:border-0">
                        <span class="w-6 text-right text-muted">{{ $track->track_number ?? $loop->iteration }}</span>
                        <a href="{{ route('songs.show', $track->slug) }}" class="font-medium hover:text-accent-600">{{ $track->title }}</a>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</x-layouts.app>
