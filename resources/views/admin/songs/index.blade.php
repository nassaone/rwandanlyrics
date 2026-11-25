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

    <p class="mt-3 text-sm"><a href="{{ route('admin.songs.index', ['filter' => 'needs_lyrics']) }}" class="text-accent-600 underline">Show songs that still need lyrics</a></p>

    @php($canBulk = auth()->user()->atLeast(\App\Enums\Role::Admin))

    <form method="POST" action="{{ route('admin.songs.bulk') }}" x-data="{ n: 0, scope: 'page' }"
          @submit="if (scope === 'all' && ! confirm('Apply this to all {{ $songs->total() }} songs matching the current filter?')) $event.preventDefault()">
        @csrf
        <input type="hidden" name="q" value="{{ request('q') }}">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <input type="hidden" name="filter" value="{{ request('filter') }}">

        @if ($canBulk && $songs->count())
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
                    <label class="flex items-center gap-2"><input type="radio" value="page" x-model="scope"> Ticked songs</label>
                    <label class="flex items-center gap-2"><input type="radio" name="scope_all" value="all" x-model="scope"> All {{ $songs->total() }} songs matching this filter</label>
                </div>
                <input type="hidden" name="scope" :value="scope">
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="submit" name="action" value="publish" class="min-h-11 rounded-md bg-accent-600 px-4 py-2 text-sm font-medium text-on-accent">Publish + Authorize</button>
                    <button type="submit" name="action" value="authorize" class="min-h-11 rounded-md border border-line px-4 py-2 text-sm hover:bg-page">Authorize rights only</button>
                    <button type="submit" name="action" value="unpublish" class="min-h-11 rounded-md border border-line px-4 py-2 text-sm hover:bg-page">Move to draft</button>
                </div>
                <p class="mt-2 text-xs text-muted">Songs without lyrics go live too, but stay hidden from Google until lyrics are added. Authorize only songs whose lyrics you have the right to publish.</p>
            </div>
        @endif

        <ul class="mt-4 divide-y divide-line rounded-md border border-line">
            @forelse ($songs as $song)
                <li class="flex flex-wrap items-center justify-between gap-2 p-3">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($canBulk)
                            <input type="checkbox" name="ids[]" value="{{ $song->id }}" class="size-5 shrink-0" aria-label="Select {{ $song->title }}"
                                   @change="n = $root.querySelectorAll('input[name=\'ids[]\']:checked').length">
                        @endif
                        <div class="min-w-0">
                            <a href="{{ route('admin.songs.edit', $song) }}" class="font-medium hover:text-accent-600">{{ $song->title }}</a>
                            <p class="text-sm text-muted">{{ $song->artists->pluck('name')->join(', ') ?: 'No artist' }}</p>
                        </div>
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
    </form>
    <div class="mt-6">{{ $songs->links() }}</div>
</x-admin-layout>
