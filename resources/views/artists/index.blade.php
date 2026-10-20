<x-layouts.app title="Artists" description="Browse Rwandan artists on Rwandanlyrics.">
    <x-breadcrumbs :items="['Home' => route('home'), 'Artists' => null]" />
    <h1 class="text-2xl font-semibold">Artists</h1>
    @if ($artists->isEmpty())
        <div class="mt-6"><x-empty-state title="No artists published yet" /></div>
    @else
        <ul class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($artists as $artist)
                <li><a href="{{ route('artists.show', $artist->slug) }}" class="block rounded-md border border-line p-3 text-sm font-medium hover:border-accent-600">{{ $artist->name }}</a></li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $artists->links() }}</div>
    @endif
</x-layouts.app>
