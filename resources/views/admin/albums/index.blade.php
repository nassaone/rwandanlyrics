<x-admin-layout title="Albums">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Albums</h1>
        <x-link-button href="{{ route('admin.albums.create') }}">New album</x-link-button>
    </div>
    <form method="GET" class="mt-4 flex flex-wrap gap-2" role="search">
        <label class="sr-only" for="q">Search titles</label>
        <input id="q" name="q" value="{{ request('q') }}" placeholder="Search titles" class="min-w-0 flex-1 min-h-11 rounded-lg border border-line px-3 py-2.5 text-base">
        <x-button type="submit" variant="secondary">Search</x-button>
    </form>
    <ul class="mt-4 divide-y divide-line rounded-md border border-line">
        @forelse ($albums as $album)
            <li class="flex flex-wrap items-center justify-between gap-2 p-3">
                <div class="min-w-0">
                    <a href="{{ route('admin.albums.edit', $album) }}" class="font-medium hover:text-accent-600">{{ $album->title }}</a>
                    <p class="text-sm text-muted">{{ $album->artists->pluck('name')->join(', ') ?: 'No artist' }}</p>
                </div>
                <div class="flex gap-1">
                    <x-badge>{{ \Illuminate\Support\Str::headline($album->type->value) }}</x-badge>
                    <x-badge>{{ \Illuminate\Support\Str::headline($album->status->value) }}</x-badge>
                </div>
            </li>
        @empty
            <li class="p-3 text-muted">No albums match.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $albums->links() }}</div>
</x-admin-layout>
