<x-admin-layout title="Edit language">
    <h1 class="text-2xl font-semibold">Edit language</h1>
    <form method="POST" action="{{ route('admin.languages.update', $language) }}" class="mt-4 space-y-4">
        @csrf @method('PUT')
        <x-field name="name" label="Name" :value="$language->name" required />
        <x-field name="code" label="Code (optional)" :value="$language->code" maxlength="10" />
        <x-button type="submit">Save language</x-button>
    </form>
    @can('delete', $language)
        <form method="POST" action="{{ route('admin.languages.destroy', $language) }}" class="mt-10 border-t border-line pt-6"
              onsubmit="return confirm('Delete this language? Songs using it will be kept.')">
            @csrf @method('DELETE')
            <x-button type="submit" variant="secondary" class="text-red-700">Delete language</x-button>
        </form>
    @endcan
</x-admin-layout>
