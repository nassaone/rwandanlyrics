@props(['song'])
@php
    // Uses only images that were eager-loaded by the controller, so a row never triggers its own query.
    $thumb = $song->relationLoaded('cover') ? $song->cover : null;
    if (! $thumb && $song->relationLoaded('album') && $song->album && $song->album->relationLoaded('cover')) {
        $thumb = $song->album->cover;
    }
    if (! $thumb && $song->relationLoaded('artists')) {
        $first = $song->artists->first();
        $thumb = $first && $first->relationLoaded('image') ? $first->image : null;
    }
@endphp
<li class="border-b border-line last:border-0">
    <a href="{{ route('songs.show', $song->slug) }}" class="-mx-2 flex items-center gap-3 rounded-lg px-2 py-3 hover:bg-accent-50">
        <x-avatar :media="$thumb" :name="$song->title" shape="square" size="size-12" />
        <span class="min-w-0">
            <span class="block font-medium text-ink">{{ $song->title }}@if ($song->youtube_id)<svg class="ml-1.5 inline size-4 align-[-2px] text-accent-600" viewBox="0 0 24 24" fill="currentColor" role="img" aria-label="Has video"><path d="M8 5v14l11-7z"/></svg>@endif</span>
            <span class="block text-sm text-muted">{{ $song->artists->pluck('name')->join(', ') }}</span>
        </span>
    </a>
</li>
