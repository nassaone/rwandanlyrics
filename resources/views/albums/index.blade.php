<x-layouts.app title="Albums" description="Browse Rwandan albums on Vocal Vibes Nation." :robots="$filtered ? 'noindex,follow' : 'index,follow'">
    <x-breadcrumbs :items="['Home' => route('home'), 'Albums' => null]" />
    <h1>Albums</h1>
    <p class="mt-1 text-sm text-muted">
        {{ number_format($total) }} {{ \Illuminate\Support\Str::plural('album', $total) }}@if ($genre) in {{ $genre->name }}@endif
        @if ($sort === 'trending') · based on views in the last 14 days @endif
    </p>
    <x-list-filters route="albums.index" :tabs="['newest' => 'Newest', 'trending' => 'Trending', 'az' => 'A to Z']" :sort="$sort" default="newest" :genres="$genres" :genre="$genre" />

    @if ($albums->isEmpty())
        <div class="mt-6">
            <x-empty-state :title="$filtered ? ($sort === 'trending' && ! $genre ? 'Nothing is trending yet' : 'No albums match these filters') : 'No albums published yet'" />
            @if ($filtered)
                <p class="mt-3 text-center"><a href="{{ route('albums.index') }}" class="text-accent-600 hover:underline">Clear filters</a></p>
            @endif
        </div>
    @else
        <ul class="mt-4 md:grid md:grid-cols-2 md:gap-x-10">
            @foreach ($albums as $album)
                <li class="border-b border-line last:border-0">
                    <a href="{{ route('albums.show', $album->slug) }}" class="-mx-2 flex items-center gap-3 rounded-lg px-2 py-3 hover:bg-accent-50">
                        <x-avatar :media="$album->cover" :name="$album->title" shape="square" size="size-14" />
                        <span class="min-w-0">
                            <span class="block font-medium text-ink">{{ $album->title }}</span>
                            <span class="block text-sm text-muted">{{ $album->artists->pluck('name')->join(', ') }}@if ($album->release_date) · {{ $album->release_date->format('Y') }}@endif</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($albums instanceof \Illuminate\Contracts\Pagination\Paginator && $albums->hasPages())
            <div class="mt-6">{{ $albums->links() }}</div>
        @endif
    @endif
</x-layouts.app>
