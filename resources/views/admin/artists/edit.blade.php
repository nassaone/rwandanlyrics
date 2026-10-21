<x-admin-layout title="Edit artist">
    <h1 class="text-2xl font-semibold">Edit artist</h1>
    @if ($artist->status === \App\Enums\PublicationStatus::Published && $artist->published_at?->lte(now()))
        <p class="mt-1 text-sm"><a class="text-accent-600 underline" href="{{ route('artists.show', $artist->slug) }}">View on the site</a></p>
    @endif
    <div class="mt-4">@include('admin.artists._form', ['action' => route('admin.artists.update', $artist)])</div>

    @can('delete', $artist)
        <form method="POST" action="{{ route('admin.artists.destroy', $artist) }}" class="mt-10 border-t border-line pt-6"
              onsubmit="return confirm('Delete this artist permanently?')">
            @csrf @method('DELETE')
            <x-button type="submit" variant="secondary" class="text-red-700">Delete artist</x-button>
        </form>
    @endcan
</x-admin-layout>
