<x-admin-layout title="Media">
    <h1 class="text-2xl font-semibold">Media</h1>
    <p class="mt-1 text-sm text-muted">Images uploaded for songs, albums and artists. Deleting an image removes it from anything that used it.</p>

    @if (session('status'))
        <x-alert type="success" class="mt-4">{{ session('status') }}</x-alert>
    @endif

    @if ($media->isEmpty())
        <div class="mt-6"><x-empty-state title="No images yet" message="Upload covers and artist photos from the song, album and artist forms." /></div>
    @else
        <ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
            @foreach ($media as $item)
                <li class="rounded-md border border-line p-3 text-sm">
                    <img src="{{ $item->url() }}" alt="{{ $item->alt_text }}" width="160" height="160" loading="lazy" class="aspect-square w-full rounded object-cover">
                    <p class="mt-2 break-words">{{ $item->alt_text }}</p>
                    <p class="mt-1 text-xs text-muted">{{ $item->width }} x {{ $item->height }} px</p>
                    <div class="mt-2">
                        @if ($used->contains($item->id))
                            <x-badge>In use</x-badge>
                        @else
                            <span class="text-xs text-muted">Not used</span>
                        @endif
                    </div>
                    @can('delete', $item)
                        <form method="POST" action="{{ route('admin.media.destroy', $item) }}" class="mt-2"
                              onsubmit="return confirm('Delete this image?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-700 underline">Delete</button>
                        </form>
                    @endcan
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $media->links() }}</div>
    @endif
</x-admin-layout>
