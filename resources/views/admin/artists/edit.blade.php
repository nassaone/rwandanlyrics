<x-admin-layout title="Edit artist">
    <h1 class="text-2xl font-semibold">Edit artist</h1>
    @if ($artist->status === \App\Enums\PublicationStatus::Published && $artist->published_at?->lte(now()))
        <p class="mt-1 text-sm"><a class="text-accent-600 underline" href="{{ route('artists.show', $artist->slug) }}">View on the site</a></p>
    @endif
    <div class="mt-4">@include('admin.artists._form', ['action' => route('admin.artists.update', $artist)])</div>

    @can('delete', $artist)
        @if ($others->isNotEmpty())
            <form method="POST" action="{{ route('admin.artists.merge', $artist) }}" class="mt-10 space-y-4 border-t border-line pt-6"
                  onsubmit="return confirm('Merge this artist into the selected one? This cannot be undone.')">
                @csrf
                <h2 class="text-lg font-semibold">Merge duplicate</h2>
                <p class="text-sm text-muted">Moves this artist's songs, albums, genres and spellings into the artist you choose, then deletes this profile. Its public URL will redirect to the chosen artist.</p>
                <x-select name="target_id" label="Merge into" :options="$others" placeholder="Choose the artist to keep" required />
                <x-button type="submit" variant="secondary" class="text-red-700">Merge and delete this artist</x-button>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.artists.destroy', $artist) }}" class="mt-10 border-t border-line pt-6"
              onsubmit="return confirm('Delete this artist permanently?')">
            @csrf @method('DELETE')
            <x-button type="submit" variant="secondary" class="text-red-700">Delete artist</x-button>
        </form>
    @endcan
</x-admin-layout>
