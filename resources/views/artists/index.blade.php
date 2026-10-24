<x-layouts.app title="Artists" description="Browse Rwandan artists on Rwandanlyrics.">
    <x-breadcrumbs :items="['Home' => route('home'), 'Artists' => null]" />
    <h1>Artists</h1>
    @if ($artists->isEmpty())
        <div class="mt-6"><x-empty-state title="No artists published yet" /></div>
    @else
        <ul class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($artists as $artist)
                <li><a href="{{ route('artists.show', $artist->slug) }}" class="flex items-center gap-3 rounded-lg border border-line p-3 font-medium leading-snug hover:border-accent-600 hover:bg-accent-50"><x-avatar :media="$artist->image" :name="$artist->name" /><span class="min-w-0">{{ $artist->name }}</span></a></li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $artists->links() }}</div>
    @endif
</x-layouts.app>
