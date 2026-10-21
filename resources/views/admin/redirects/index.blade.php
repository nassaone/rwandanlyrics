<x-admin-layout title="Redirects">
    <h1 class="text-2xl font-semibold">Redirects</h1>
    <p class="mt-1 text-sm text-muted">Changing a published slug adds a redirect automatically. Add your own for old links.</p>

    @if (session('status'))
        <x-alert type="success" class="mt-4">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('admin.redirects.store') }}" class="mt-6 space-y-4 rounded-md border border-line p-4">
        @csrf
        <h2 class="text-lg font-semibold">Add a redirect</h2>
        <x-field name="from_path" label="Old path (for example /songs/old-name)" required />
        <x-field name="to_path" label="New path (for example /songs/new-name)" required />
        <x-button type="submit">Add redirect</x-button>
    </form>

    <form method="GET" action="{{ route('admin.redirects.index') }}" class="mt-6 flex gap-2" role="search">
        <label for="q" class="sr-only">Search redirects</label>
        <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Search paths" class="min-w-0 flex-1 rounded-md border border-line px-3 py-2 text-base">
        <x-button type="submit" variant="secondary">Search</x-button>
    </form>

    @if ($redirects->isEmpty())
        <div class="mt-6"><x-empty-state title="No redirects" /></div>
    @else
        <ul class="mt-6 divide-y divide-line rounded-md border border-line text-sm">
            @foreach ($redirects as $redirect)
                <li class="flex items-start justify-between gap-3 p-3">
                    <div class="min-w-0 break-words">
                        <span class="font-medium">{{ $redirect->from_path }}</span>
                        <span class="text-muted">goes to</span>
                        <span class="font-medium">{{ $redirect->to_path }}</span>
                        <span class="text-xs text-muted">({{ $redirect->status_code }})</span>
                    </div>
                    @can('delete', $redirect)
                        <form method="POST" action="{{ route('admin.redirects.destroy', $redirect) }}" onsubmit="return confirm('Remove this redirect?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-700 underline">Remove</button>
                        </form>
                    @endcan
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $redirects->links() }}</div>
    @endif
</x-admin-layout>
