<x-admin-layout title="Songs">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Songs</h1>
        <x-link-button href="{{ route('admin.songs.create') }}">New song</x-link-button>
    </div>

    <form method="GET" class="mt-4 flex flex-wrap gap-2" role="search">
        <label class="sr-only" for="q">Search titles</label>
        <input id="q" name="q" value="{{ request('q') }}" placeholder="Search titles" class="min-w-0 flex-1 min-h-11 rounded-lg border border-line px-3 py-2.5 text-base">
        <label class="sr-only" for="status">Status</label>
        <select id="status" name="status" class="rounded-md border border-line bg-page px-3 py-2 text-base">
            <option value="">Any status</option>
            @foreach (\App\Support\EnumOptions::for(\App\Enums\PublicationStatus::class) as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary">Filter</x-button>
    </form>

    <ul class="mt-4 divide-y divide-line rounded-md border border-line">
        @forelse ($songs as $song)
            <li class="flex flex-wrap items-center justify-between gap-2 p-3">
                <div class="min-w-0">
                    <a href="{{ route('admin.songs.edit', $song) }}" class="font-medium hover:text-accent-600">{{ $song->title }}</a>
                    <p class="text-sm text-muted">{{ $song->artists->pluck('name')->join(', ') ?: 'No artist' }}</p>
                </div>
                <div class="flex flex-wrap gap-1">
                    <x-badge>{{ \Illuminate\Support\Str::headline($song->status->value) }}</x-badge>
                    <x-badge>Rights: {{ \Illuminate\Support\Str::headline($song->rights_status->value) }}</x-badge>
                    @if (\App\Support\Visibility::isLive($song))<x-badge>Live</x-badge>@endif
                </div>
            </li>
        @empty
            <li class="p-3 text-muted">No songs match.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $songs->links() }}</div>
</x-admin-layout>
