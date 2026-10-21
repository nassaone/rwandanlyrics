<x-admin-layout title="Genres">
    <h1 class="text-2xl font-semibold">Genres</h1>
    <form method="POST" action="{{ route('admin.genres.store') }}" class="mt-4 space-y-4 rounded-md border border-line p-4">
        @csrf
        <h2 class="font-semibold">Add a genre</h2>
        <x-field name="name" label="Name" required />
        <x-textarea name="description" label="Description (optional)" rows="2" />
        <x-button type="submit">Add genre</x-button>
    </form>
    <ul class="mt-6 divide-y divide-line rounded-md border border-line">
        @forelse ($genres as $genre)
            <li class="flex items-center justify-between gap-2 p-3">
                <a href="{{ route('admin.genres.edit', $genre) }}" class="font-medium hover:text-accent-600">{{ $genre->name }}</a>
                <span class="text-sm text-muted">{{ $genre->songs_count }} song(s)</span>
            </li>
        @empty
            <li class="p-3 text-muted">No genres yet.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $genres->links() }}</div>
</x-admin-layout>
