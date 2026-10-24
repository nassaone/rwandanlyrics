<x-layouts.app title="Lyrics" description="Browse the latest Rwandan song lyrics on Rwandanlyrics.">
    <x-breadcrumbs :items="['Home' => route('home'), 'Lyrics' => null]" />
    <h1>Lyrics</h1>
    @if ($songs->isEmpty())
        <div class="mt-6"><x-empty-state title="No lyrics published yet" /></div>
    @else
        <ul class="mt-4 md:grid md:grid-cols-2 md:gap-x-10">
            @foreach ($songs as $song)
                <x-song-row :song="$song" />
            @endforeach
        </ul>
        <div class="mt-6">{{ $songs->links() }}</div>
    @endif
</x-layouts.app>
