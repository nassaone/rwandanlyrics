@props(['song'])
<li class="border-b border-line py-3 last:border-0">
    <a href="{{ route('songs.show', $song->slug) }}" class="font-medium hover:text-accent-600">{{ $song->title }}</a>
    <p class="text-sm text-muted">{{ $song->artists->pluck('name')->join(', ') }}</p>
</li>
