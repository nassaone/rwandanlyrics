<x-layouts.app
    :title="$genre->name.' lyrics'"
    :description="$genre->description ?: 'Rwandan '.$genre->name.' songs and lyrics on Vocal Vibes Nation.'"
    :robots="$songs->isEmpty() ? 'noindex,follow' : 'index,follow'">
    <x-breadcrumbs :items="['Home' => route('home'), 'Genres' => route('genres.index'), $genre->name => null]" />
    <h1>{{ $genre->name }}</h1>
    @if ($genre->description)
        <p class="mt-2 text-muted">{{ $genre->description }}</p>
    @endif
    @if ($songs->isEmpty())
        <div class="mt-6"><x-empty-state title="No lyrics published in this genre yet" /></div>
    @else
        <ul class="mt-4">
            @foreach ($songs as $song)
                <x-song-row :song="$song" />
            @endforeach
        </ul>
        <div class="mt-6">{{ $songs->links() }}</div>
    @endif

    @if ($topArtists->isNotEmpty())
        <section class="mt-10" aria-labelledby="top-artists">
            <h2 id="top-artists" class="mt-1">Top artists in {{ $genre->name }}</h2>
            <ul class="mt-3 grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                @foreach ($topArtists as $item)
                    <li>
                        <a href="{{ route('artists.show', $item->slug) }}" class="flex items-center gap-3 rounded-xl border border-line p-3 hover:bg-accent-50">
                            <x-avatar :media="$item->image" :name="$item->name" size="size-10" />
                            <span class="min-w-0 font-medium">{{ $item->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($otherGenres->isNotEmpty())
        <section class="mt-10" aria-labelledby="related-genres">
            <h2 id="related-genres" class="mt-1">Related genres</h2>
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($otherGenres as $item)
                    <li><a href="{{ route('genres.show', $item->slug) }}"><x-badge>{{ $item->name }}</x-badge></a></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
