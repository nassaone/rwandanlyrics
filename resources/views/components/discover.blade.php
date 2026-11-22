@php
    $discover = app(\App\Services\Discover::class);
    $dSongs = $discover->songs(6);
    $dArtists = $discover->artists(8);
    $dGenres = $discover->genres(12);
@endphp
@if ($dSongs->isNotEmpty() || $dArtists->isNotEmpty() || $dGenres->isNotEmpty())
    <section class="border-t border-line" aria-labelledby="discover-heading">
        <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
            <h2 id="discover-heading" class="text-xl">Keep exploring</h2>
            <div class="mt-5 grid gap-8 md:grid-cols-3">
                @if ($dSongs->isNotEmpty())
                    <div>
                        <h3 class="text-base">New and trending lyrics</h3>
                        <ul class="mt-3 space-y-2 text-sm">
                            @foreach ($dSongs as $item)
                                <li>
                                    <a href="{{ route('songs.show', $item->slug) }}" class="font-medium text-ink hover:text-accent-600">{{ $item->title }}</a>
                                    <span class="text-muted">{{ $item->artists->pluck('name')->join(', ') }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-sm"><a href="{{ route('songs.index') }}" class="text-accent-600 hover:underline">Browse all lyrics</a></p>
                    </div>
                @endif
                @if ($dArtists->isNotEmpty())
                    <div>
                        <h3 class="text-base">Popular artists</h3>
                        <ul class="mt-3 space-y-2 text-sm">
                            @foreach ($dArtists as $item)
                                <li><a href="{{ route('artists.show', $item->slug) }}" class="font-medium text-ink hover:text-accent-600">{{ $item->name }}</a></li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-sm"><a href="{{ route('artists.index') }}" class="text-accent-600 hover:underline">All artists</a></p>
                    </div>
                @endif
                @if ($dGenres->isNotEmpty())
                    <div>
                        <h3 class="text-base">Browse genres</h3>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($dGenres as $item)
                                <li><a href="{{ route('genres.show', $item->slug) }}"><x-badge>{{ $item->name }}</x-badge></a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
