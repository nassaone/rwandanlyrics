<x-admin-layout title="Edit album">
    <h1 class="text-2xl font-semibold">Edit album</h1>
    <p class="mt-1 text-sm text-muted">Add songs to this album from each song's page.</p>
    <div class="mt-4">@include('admin.albums._form', ['action' => route('admin.albums.update', $album)])</div>

    @can('delete', $album)
        <form method="POST" action="{{ route('admin.albums.destroy', $album) }}" class="mt-10 border-t border-line pt-6"
              onsubmit="return confirm('Delete this album? Its songs will be kept.')">
            @csrf @method('DELETE')
            <x-button type="submit" variant="secondary" class="text-red-700">Delete album</x-button>
        </form>
    @endcan
</x-admin-layout>
