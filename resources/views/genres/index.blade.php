<x-layouts.app title="Genres" description="Browse Rwandan music by genre on Rwandanlyrics.">
    <x-breadcrumbs :items="['Home' => route('home'), 'Genres' => null]" />
    <h1>Genres</h1>
    @if ($genres->isEmpty())
        <div class="mt-6"><x-empty-state title="No genres to show yet" /></div>
    @else
        <ul class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($genres as $genre)
                <li><a href="{{ route('genres.show', $genre->slug) }}" class="block rounded-lg border border-line p-4 font-medium leading-snug hover:border-accent-600 hover:bg-accent-50">{{ $genre->name }} <span class="text-muted">({{ $genre->visible_songs_count }})</span></a></li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
