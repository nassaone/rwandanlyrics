<x-layouts.app title="Artists" description="Browse Rwandan artists on Rwandanlyrics.">
    <x-breadcrumbs :items="['Home' => route('home'), 'Artists' => null]" />
    <h1>Artists</h1>
    @if ($artists->isEmpty())
        <div class="mt-6"><x-empty-state title="No artists published yet" /></div>
    @else
        <ul class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($artists as $artist)
                <li><a href="{{ route('artists.show', $artist->slug) }}" class="block rounded-lg border border-line p-4 font-medium leading-snug hover:border-accent-600 hover:bg-accent-50">{{ $artist->name }}</a></li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $artists->links() }}</div>
    @endif
</x-layouts.app>
