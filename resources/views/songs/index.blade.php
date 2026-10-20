<x-layouts.app title="Lyrics" description="Browse the latest Rwandan song lyrics on Rwandanlyrics.">
    <x-breadcrumbs :items="['Home' => route('home'), 'Lyrics' => null]" />
    <h1 class="text-2xl font-semibold">Lyrics</h1>
    @if ($songs->isEmpty())
        <div class="mt-6"><x-empty-state title="No lyrics published yet" /></div>
    @else
        <ul class="mt-4">
            @foreach ($songs as $song)
                <x-song-row :song="$song" />
            @endforeach
        </ul>
        <div class="mt-6">{{ $songs->links() }}</div>
    @endif
</x-layouts.app>
