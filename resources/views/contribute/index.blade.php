<x-layouts.app title="My submissions" robots="noindex,nofollow">
    <h1>My submissions</h1>
    <p class="mt-1 text-sm text-muted">Suggest new songs or corrections. An editor reviews everything before it is published.</p>
    @if (session('status')) <x-alert type="success" class="mt-4">{{ session('status') }}</x-alert> @endif
    <p class="mt-4"><x-link-button href="{{ route('contribute.create') }}">Submit a song</x-link-button></p>

    @if ($submissions->isEmpty())
        <div class="mt-6"><x-empty-state title="No submissions yet" message="Your submissions will be listed here." /></div>
    @else
        <ul class="mt-6 divide-y divide-line rounded-md border border-line text-sm">
            @foreach ($submissions as $submission)
                <li class="flex items-center justify-between gap-3 p-3">
                    <div class="min-w-0">
                        <a class="font-medium hover:text-accent-600" href="{{ route('contribute.show', $submission) }}">{{ $submission->title }}</a>
                        <p class="text-muted">{{ $submission->artist_name }} &middot; {{ $submission->type->label() }} &middot; {{ $submission->created_at->format('j M Y') }}</p>
                    </div>
                    <x-badge>{{ $submission->status->label() }}</x-badge>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $submissions->links() }}</div>
    @endif
</x-layouts.app>
