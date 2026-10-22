<x-admin-layout title="Submissions">
    <h1 class="text-2xl font-semibold">Submissions</h1>
    @if (session('status')) <x-alert type="success" class="mt-4">{{ session('status') }}</x-alert> @endif

    <nav class="mt-4 flex gap-4 text-sm" aria-label="Filter">
        @foreach (\App\Enums\SubmissionStatus::cases() as $case)
            <a href="{{ route('admin.submissions.index', ['status' => $case->value]) }}"
               @if ($case === $status) aria-current="page" @endif
               class="{{ $case === $status ? 'font-medium text-accent-700 underline' : 'hover:text-accent-600' }}">{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
        @endforeach
    </nav>

    @if ($submissions->isEmpty())
        <div class="mt-6"><x-empty-state title="Nothing here" /></div>
    @else
        <ul class="mt-6 divide-y divide-line rounded-md border border-line text-sm">
            @foreach ($submissions as $submission)
                <li class="flex items-center justify-between gap-3 p-3">
                    <div class="min-w-0">
                        <a class="font-medium hover:text-accent-600" href="{{ route('admin.submissions.show', $submission) }}">{{ $submission->title }}</a>
                        <p class="text-muted">{{ $submission->artist_name }} &middot; {{ $submission->type->label() }} &middot; from {{ $submission->user?->name ?? 'deleted user' }} &middot; {{ $submission->created_at->format('j M Y') }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $submissions->links() }}</div>
    @endif
</x-admin-layout>
