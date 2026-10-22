<x-layouts.app title="Submission" robots="noindex,nofollow">
    <p class="text-sm"><a class="text-accent-600 underline" href="{{ route('contribute.index') }}">&larr; My submissions</a></p>
    <h1 class="mt-2 text-2xl font-semibold">{{ $submission->title }}</h1>
    <p class="text-sm text-muted">{{ $submission->artist_name }} &middot; {{ $submission->type->label() }} &middot; <x-badge>{{ $submission->status->label() }}</x-badge></p>
    @if (session('status')) <x-alert type="success" class="mt-4">{{ session('status') }}</x-alert> @endif
    @if ($submission->review_notes)
        <x-alert class="mt-4"><strong>Editor's note:</strong> {{ $submission->review_notes }}</x-alert>
    @endif
    <h2 class="mt-6 text-lg font-semibold">Lyrics you sent</h2>
    <div class="lyrics mt-2 rounded-md border border-line p-4">{{ $submission->lyrics }}</div>
</x-layouts.app>
