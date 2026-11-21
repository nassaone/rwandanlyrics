<x-layouts.app title="Artists" description="Browse Rwandan artists on Vocal Vibes Nation." :robots="$filtered ? 'noindex,follow' : 'index,follow'">
    <x-breadcrumbs :items="['Home' => route('home'), 'Artists' => null]" />
    <h1>Artists</h1>
    <p class="mt-1 text-sm text-muted">
        {{ number_format($total) }} {{ \Illuminate\Support\Str::plural('artist', $total) }}@if ($genre) in {{ $genre->name }}@endif
        @if ($sort === 'trending') · based on views in the last 14 days @endif
    </p>
    <x-list-filters route="artists.index" :tabs="['az' => 'A to Z', 'newest' => 'Newest', 'trending' => 'Trending']" :sort="$sort" default="az" :genres="$genres" :genre="$genre" />
    @if ($artists->isEmpty())
        <div class="mt-6">
            <x-empty-state :title="$filtered ? ($sort === 'trending' && ! $genre ? 'Nothing is trending yet' : 'No artists match these filters') : 'No artists published yet'" />
            @if ($filtered)
                <p class="mt-3 text-center"><a href="{{ route('artists.index') }}" class="text-accent-600 hover:underline">Clear filters</a></p>
            @endif
        </div>
    @else
        <ul class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($artists as $artist)
                <li><a href="{{ route('artists.show', $artist->slug) }}" class="flex items-center gap-3 rounded-lg border border-line p-3 font-medium leading-snug hover:border-accent-600 hover:bg-accent-50"><x-avatar :media="$artist->image" :name="$artist->name" /><span class="min-w-0">{{ $artist->name }}</span></a></li>
            @endforeach
        </ul>
        @if ($artists instanceof \Illuminate\Contracts\Pagination\Paginator && $artists->hasPages())
            <div class="mt-6">{{ $artists->links() }}</div>
        @endif
    @endif
</x-layouts.app>
