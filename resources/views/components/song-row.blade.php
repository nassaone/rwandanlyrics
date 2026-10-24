@props(['song'])
<li class="border-b border-line last:border-0">
    <a href="{{ route('songs.show', $song->slug) }}" class="block -mx-2 rounded-lg px-2 py-3 hover:bg-accent-50">
        <span class="block font-medium text-ink">{{ $song->title }}</span>
        <span class="block text-sm text-muted">{{ $song->artists->pluck('name')->join(', ') }}</span>
    </a>
</li>
