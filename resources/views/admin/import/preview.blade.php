<x-admin-layout title="Import">
    @php($s = $plan['summary'])
    <div class="space-y-6">
        <h1 class="text-2xl font-bold">Preview import</h1>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([['Songs to create', $s['songs']], ['New artists', $s['new_artists']], ['New genres', $s['new_genres']], ['Skipped (already exist)', $s['skipped']], ['Rows with errors', $s['errors']], ['Warnings', $s['warnings']]] as [$label, $value])
                <div class="rounded-xl border border-line bg-surface p-4">
                    <div class="text-2xl font-bold">{{ $value }}</div>
                    <div class="text-sm text-muted">{{ $label }}</div>
                </div>
            @endforeach
        </div>

        @if ($plan['ignored'])
            <p class="text-sm text-muted">These columns are not used and were ignored: {{ implode(', ', $plan['ignored']) }}.</p>
        @endif

        <p class="text-sm">Everything is saved as a <strong>draft</strong> with rights status "unknown". Rows with errors are skipped.</p>

        <div class="flex flex-wrap items-center gap-3">
            @if ($s['songs'] + $s['artist_rows'] > 0)
                <form method="post" action="{{ route('admin.import.commit') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-full bg-accent-600 px-5 font-medium text-on-accent hover:bg-accent-700">Import {{ $s['songs'] }} {{ \Illuminate\Support\Str::plural('song', $s['songs']) }} and {{ $s['new_artists'] }} {{ \Illuminate\Support\Str::plural('artist', $s['new_artists']) }}</button>
                </form>
            @else
                <p class="font-medium">Nothing new to import.</p>
            @endif
            <a href="{{ route('admin.import.index') }}" class="text-accent-600 hover:underline">Cancel</a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-line">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface">
                    <tr><th class="p-2">Row</th><th class="p-2">Artist</th><th class="p-2">Song</th><th class="p-2">Result</th><th class="p-2">Notes</th></tr>
                </thead>
                <tbody>
                    @foreach ($plan['rows'] as $row)
                        <tr class="border-t border-line align-top">
                            <td class="p-2 text-muted">{{ $row['line'] }}</td>
                            <td class="p-2">{{ $row['artist'] }}</td>
                            <td class="p-2">{{ $row['title'] }}</td>
                            <td class="p-2">
                                @switch($row['action'])
                                    @case('song')<span class="rounded px-2 py-0.5 text-xs font-medium bg-accent-100 text-accent-700">Create song</span>@break
                                    @case('artist')<span class="rounded px-2 py-0.5 text-xs font-medium bg-accent-100 text-accent-700">Create artist</span>@break
                                    @case('skip')<span class="rounded border border-line px-2 py-0.5 text-xs text-muted">Skip</span>@break
                                    @default<span class="rounded px-2 py-0.5 text-xs font-medium bg-ink text-page">Error</span>
                                @endswitch
                            </td>
                            <td class="p-2">
                                @foreach ($row['errors'] as $m)<div class="font-medium">{{ $m }}</div>@endforeach
                                @foreach ($row['warnings'] as $m)<div class="text-muted">Warning: {{ $m }}</div>@endforeach
                                @foreach ($row['notes'] as $m)<div class="text-muted">{{ $m }}</div>@endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
