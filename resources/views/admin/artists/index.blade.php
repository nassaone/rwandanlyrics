<x-admin-layout title="Artists">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Artists</h1>
        <x-link-button href="{{ route('admin.artists.create') }}">New artist</x-link-button>
    </div>
    <form method="GET" class="mt-4 flex flex-wrap gap-2" role="search">
        <label class="sr-only" for="q">Search names</label>
        <input id="q" name="q" value="{{ request('q') }}" placeholder="Search names" class="min-w-0 flex-1 min-h-11 rounded-lg border border-line px-3 py-2.5 text-base">
        <label class="sr-only" for="status">Status</label>
        <select id="status" name="status" class="rounded-md border border-line bg-page px-3 py-2 text-base">
            <option value="">Any status</option>
            @foreach (\App\Support\EnumOptions::for(\App\Enums\PublicationStatus::class) as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary">Filter</x-button>
    </form>

    @php($canBulk = auth()->user()->atLeast(\App\Enums\Role::Admin))

    <form method="POST" action="{{ route('admin.artists.bulk') }}" x-data="{ n: 0, scope: 'page' }"
          @submit="if (scope === 'all' && ! confirm('Apply this to all {{ $artists->total() }} artists matching the current filter?')) $event.preventDefault()">
        @csrf
        <input type="hidden" name="q" value="{{ request('q') }}">
        <input type="hidden" name="status" value="{{ request('status') }}">

        @if ($canBulk && $artists->count())
            <div class="mt-4 rounded-md border border-line bg-surface p-3">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" class="size-5"
                               @change="$root.querySelectorAll('input[name=\'ids[]\']').forEach(c => c.checked = $event.target.checked); n = $root.querySelectorAll('input[name=\'ids[]\']:checked').length">
                        Select all on this page
                    </label>
                    <span class="text-sm text-muted" x-text="n + ' selected'"></span>
                </div>
                <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm">
                    <label class="flex items-center gap-2"><input type="radio" value="page" x-model="scope"> Ticked artists</label>
                    <label class="flex items-center gap-2"><input type="radio" value="all" x-model="scope"> All {{ $artists->total() }} artists matching this filter</label>
                </div>
                <input type="hidden" name="scope" :value="scope">
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="submit" name="action" value="publish" class="min-h-11 rounded-md bg-accent-600 px-4 py-2 text-sm font-medium text-on-accent">Publish</button>
                    <button type="submit" name="action" value="unpublish" class="min-h-11 rounded-md border border-line px-4 py-2 text-sm hover:bg-page">Move to draft</button>
                </div>
                <p class="mt-2 text-xs text-muted">An artist page stays out of Google until the artist has at least one live song with lyrics.</p>
            </div>
        @endif

        <ul class="mt-4 divide-y divide-line rounded-md border border-line">
            @forelse ($artists as $artist)
                <li class="flex flex-wrap items-center justify-between gap-2 p-3">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($canBulk)
                            <input type="checkbox" name="ids[]" value="{{ $artist->id }}" class="size-5 shrink-0" aria-label="Select {{ $artist->name }}"
                                   @change="n = $root.querySelectorAll('input[name=\'ids[]\']:checked').length">
                        @endif
                        <a href="{{ route('admin.artists.edit', $artist) }}" class="font-medium hover:text-accent-600">{{ $artist->name }}</a>
                    </div>
                    <div class="flex gap-1">
                        <x-badge>{{ \Illuminate\Support\Str::headline($artist->status->value) }}</x-badge>
                        <x-badge>{{ \Illuminate\Support\Str::headline($artist->verification_status->value) }}</x-badge>
                    </div>
                </li>
            @empty
                <li class="p-3 text-muted">No artists match.</li>
            @endforelse
        </ul>
    </form>
    <div class="mt-6">{{ $artists->links() }}</div>
</x-admin-layout>
