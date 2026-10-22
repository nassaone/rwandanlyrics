<x-admin-layout title="Review submission">
    <p class="text-sm"><a class="text-accent-600 underline" href="{{ route('admin.submissions.index') }}">&larr; All submissions</a></p>
    <h1 class="mt-2 text-2xl font-semibold">{{ $submission->title }}</h1>
    <p class="text-sm text-muted">{{ $submission->type->label() }} from {{ $submission->user?->name ?? 'deleted user' }} &middot; <x-badge>{{ $submission->status->label() }}</x-badge></p>

    @if (session('status')) <x-alert type="success" class="mt-4">{{ session('status') }}</x-alert> @endif
    @if ($errors->any()) <x-alert type="error" class="mt-4">{{ $errors->first() }}</x-alert> @endif

    <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
        <div><dt class="text-muted">Artist as written</dt><dd>{{ $submission->artist_name }}</dd></div>
        <div><dt class="text-muted">Album</dt><dd>{{ $submission->album_title ?: 'None given' }}</dd></div>
        <div><dt class="text-muted">Language</dt><dd>{{ $submission->language?->name ?? 'Not given' }}</dd></div>
        <div><dt class="text-muted">Rights confirmed by contributor</dt><dd>{{ $submission->rights_confirmed ? 'Yes' : 'No' }}</dd></div>
        <div><dt class="text-muted">Rights holder</dt><dd>{{ $submission->rights_holder ?: 'Not given' }}</dd></div>
        <div><dt class="text-muted">Proof or permission</dt><dd class="break-words">{{ $submission->rights_source ?: 'Not given' }}</dd></div>
    </dl>
    @if ($submission->notes) <p class="mt-3 text-sm"><span class="text-muted">Contributor's note:</span> {{ $submission->notes }}</p> @endif

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        @if ($submission->song)
            <section>
                <h2 class="text-lg font-semibold">Current lyrics</h2>
                <div class="lyrics mt-2 rounded-md border border-line p-4 text-sm">{{ $submission->song->currentLyric?->body }}</div>
            </section>
        @endif
        <section>
            <h2 class="text-lg font-semibold">{{ $submission->song ? 'Proposed lyrics' : 'Submitted lyrics' }}</h2>
            <div class="lyrics mt-2 rounded-md border border-line p-4 text-sm">{{ $submission->lyrics }}</div>
        </section>
    </div>

    @if ($submission->isPending())
        <form method="POST" action="{{ route('admin.submissions.approve', $submission) }}" class="mt-8 space-y-4 border-t border-line pt-6">
            @csrf
            <h2 class="text-lg font-semibold">Approve</h2>
            @if ($submission->type === \App\Enums\SubmissionType::NewSong)
                <p class="text-sm text-muted">This creates a draft song with rights set to Pending. You must still set rights to Authorized and publish it. If the artist is not listed, create the artist first.</p>
                <x-select name="artist_id" label="Primary artist" :options="$artists" placeholder="Choose an artist" required />
            @else
                <p class="text-sm text-muted">This adds the proposed text as a new lyrics revision. The old text is kept in the history.</p>
            @endif
            <x-textarea name="review_notes" label="Note to the contributor (optional)" rows="2" />
            <x-button type="submit">Approve</x-button>
        </form>

        <form method="POST" action="{{ route('admin.submissions.reject', $submission) }}" class="mt-8 space-y-4 border-t border-line pt-6">
            @csrf
            <h2 class="text-lg font-semibold">Decline</h2>
            <x-textarea name="review_notes" label="Reason (the contributor will see this)" rows="3" required />
            <x-button type="submit" variant="secondary" class="text-red-700">Decline</x-button>
        </form>
    @else
        <p class="mt-8 border-t border-line pt-6 text-sm text-muted">
            Reviewed by {{ $submission->reviewer?->name ?? 'unknown' }} on {{ $submission->reviewed_at?->format('j M Y H:i') }}.
            @if ($submission->review_notes) Note: {{ $submission->review_notes }} @endif
            @if ($submission->resultSong) <a class="text-accent-600 underline" href="{{ route('admin.songs.edit', $submission->resultSong) }}">Open the song</a> @endif
        </p>
    @endif
</x-admin-layout>
