<x-admin-layout title="Edit genre">
    <h1 class="text-2xl font-semibold">Edit genre</h1>
    <form method="POST" action="{{ route('admin.genres.update', $genre) }}" class="mt-4 space-y-4">
        @csrf @method('PUT')
        <x-field name="name" label="Name" :value="$genre->name" required />
        <x-field name="slug_display" label="URL slug (cannot be changed)" :value="$genre->slug" readonly />
        <x-textarea name="description" label="Description" rows="3" :value="$genre->description" />
        <x-button type="submit">Save genre</x-button>
    </form>
    @can('delete', $genre)
        <form method="POST" action="{{ route('admin.genres.destroy', $genre) }}" class="mt-10 border-t border-line pt-6"
              onsubmit="return confirm('Delete this genre?')">
            @csrf @method('DELETE')
            <x-button type="submit" variant="secondary" class="text-red-700">Delete genre</x-button>
        </form>
    @endcan
</x-admin-layout>
