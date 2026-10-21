<x-admin-layout title="Edit song">
    <h1 class="text-2xl font-semibold">Edit song</h1>
    @if (\App\Support\Visibility::isLive($song))
        <p class="mt-1 text-sm"><a class="text-accent-600 underline" href="{{ route('songs.show', $song->slug) }}">View on the site</a></p>
    @else
        <p class="mt-1 text-sm text-muted">This song is not visible to the public.</p>
    @endif
    <div class="mt-4">@include('admin.songs._form', ['action' => route('admin.songs.update', $song)])</div>

    @can('delete', $song)
        <form method="POST" action="{{ route('admin.songs.destroy', $song) }}" class="mt-10 border-t border-line pt-6"
              onsubmit="return confirm('Delete this song and its lyrics permanently?')">
            @csrf @method('DELETE')
            <x-button type="submit" variant="secondary" class="text-red-700">Delete song</x-button>
        </form>
    @endcan
</x-admin-layout>
