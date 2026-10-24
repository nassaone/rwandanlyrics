<x-admin-layout title="Artists">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Artists</h1>
        <x-link-button href="{{ route('admin.artists.create') }}">New artist</x-link-button>
    </div>
    <form method="GET" class="mt-4 flex flex-wrap gap-2" role="search">
        <label class="sr-only" for="q">Search names</label>
        <input id="q" name="q" value="{{ request('q') }}" placeholder="Search names" class="min-w-0 flex-1 min-h-11 rounded-lg border border-line px-3 py-2.5 text-base">
        <x-button type="submit" variant="secondary">Search</x-button>
    </form>
    <ul class="mt-4 divide-y divide-line rounded-md border border-line">
        @forelse ($artists as $artist)
            <li class="flex flex-wrap items-center justify-between gap-2 p-3">
                <a href="{{ route('admin.artists.edit', $artist) }}" class="font-medium hover:text-accent-600">{{ $artist->name }}</a>
                <div class="flex gap-1">
                    <x-badge>{{ \Illuminate\Support\Str::headline($artist->status->value) }}</x-badge>
                    <x-badge>{{ \Illuminate\Support\Str::headline($artist->verification_status->value) }}</x-badge>
                </div>
            </li>
        @empty
            <li class="p-3 text-muted">No artists match.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $artists->links() }}</div>
</x-admin-layout>
