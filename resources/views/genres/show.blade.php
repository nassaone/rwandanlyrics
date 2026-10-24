<x-layouts.app
    :title="$genre->name.' lyrics'"
    :description="$genre->description ?: 'Rwandan '.$genre->name.' songs and lyrics on Rwandanlyrics.'"
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
</x-layouts.app>
