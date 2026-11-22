@php
    $discover = app(\App\Services\Discover::class);
    $dSongs = $discover->songs(6);
    $dArtists = $discover->artists(8);
    $dGenres = $discover->genres(8);
@endphp
@if ($dSongs->isNotEmpty() || $dArtists->isNotEmpty() || $dGenres->isNotEmpty())
    <section class="border-t border-line" aria-labelledby="discover-heading">
        <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
            <h2 id="discover-heading" class="text-2xl">Keep exploring</h2>

            @if ($dSongs->isNotEmpty())
                <div class="mt-8">
                    <div class="flex items-baseline justify-between gap-4">
                        <h3 class="text-lg">New and trending lyrics</h3>
                        <a href="{{ route('songs.index') }}" class="shrink-0 text-sm text-accent-600 hover:underline">Browse all lyrics</a>
                    </div>
                    <ul class="mt-2 md:grid md:grid-cols-2 md:gap-x-10">
                        @foreach ($dSongs as $item)
                            <x-song-row :song="$item" />
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($dArtists->isNotEmpty())
                <div class="mt-10">
                    <div class="flex items-baseline justify-between gap-4">
                        <h3 class="text-lg">Popular artists</h3>
                        <a href="{{ route('artists.index') }}" class="shrink-0 text-sm text-accent-600 hover:underline">All artists</a>
                    </div>
                    <ul class="mt-4 flex flex-wrap gap-3">
                        @foreach ($dArtists as $item)
                            <li>
                                <a href="{{ route('artists.show', $item->slug) }}" class="flex items-center gap-2.5 rounded-full border border-line bg-page py-1.5 pl-1.5 pr-4 text-sm font-medium text-ink hover:bg-accent-50">
                                    <x-avatar :media="$item->image" :name="$item->name" size="size-9 text-sm" />
                                    <span>{{ $item->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($dGenres->isNotEmpty())
                <div class="mt-10">
                    <div class="flex items-baseline justify-between gap-4">
                        <h3 class="text-lg">Browse by genre</h3>
                        <a href="{{ route('genres.index') }}" class="shrink-0 text-sm text-accent-600 hover:underline">All genres</a>
                    </div>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($dGenres as $item)
                            <li><a href="{{ route('genres.show', $item->slug) }}"><x-badge>{{ $item->name }}</x-badge></a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>
@endif
