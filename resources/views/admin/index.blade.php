<x-admin-layout title="Admin dashboard">
    <h1 class="text-2xl font-semibold">Dashboard</h1>
    <p class="mt-1 text-muted">Signed in as {{ auth()->user()->name }} ({{ auth()->user()->role->label() }}).</p>

    <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ([
            'Published songs' => $stats['published_songs'],
            'Live on the site' => $stats['live_songs'],
            'Draft songs' => $stats['draft_songs'],
            'Pending review' => $stats['pending_songs'],
            'Artists' => $stats['artists'],
            'Albums' => $stats['albums'],
        ] as $label => $value)
            <x-card><dt class="text-sm text-muted">{{ $label }}</dt><dd class="text-2xl font-semibold">{{ $value }}</dd></x-card>
        @endforeach
    </dl>

    @if ($warnings['rights_blocked'] || $warnings['no_lyrics'])
        <section class="mt-8" aria-labelledby="warnings">
            <h2 id="warnings" class="text-lg font-semibold">Needs attention</h2>
            <ul class="mt-2 space-y-2">
                @if ($warnings['rights_blocked'])
                    <li><x-alert type="error">{{ $warnings['rights_blocked'] }} published song(s) are hidden because rights are not authorized.
                        <a class="underline" href="{{ route('admin.songs.index', ['filter' => 'rights_blocked']) }}">Review</a></x-alert></li>
                @endif
                @if ($warnings['no_lyrics'])
                    <li><x-alert type="error">{{ $warnings['no_lyrics'] }} published song(s) have no lyrics.
                        <a class="underline" href="{{ route('admin.songs.index', ['filter' => 'no_lyrics']) }}">Review</a></x-alert></li>
                @endif
            </ul>
        </section>
    @endif

    <section class="mt-8" aria-labelledby="activity">
        <h2 id="activity" class="text-lg font-semibold">Recent activity</h2>
        <div class="mt-2"><x-audit-list :logs="$recent" /></div>
    </section>
</x-admin-layout>
