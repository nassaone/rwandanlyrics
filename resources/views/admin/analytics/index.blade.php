<x-admin-layout title="Analytics">
    <h1 class="text-2xl font-semibold">Analytics</h1>
    <p class="mt-1 text-sm text-muted">First-party counts: one view per visitor per page per day. Bots, prefetches, staff and visitors who send Do Not Track are not counted. No IP addresses or cookies are stored.</p>

    <nav class="mt-4 flex gap-4 text-sm" aria-label="Period">
        @foreach ([7, 30, 90] as $option)
            <a href="{{ route('admin.analytics.index', ['days' => $option]) }}" @if ($option === $days) aria-current="page" @endif
               class="{{ $option === $days ? 'font-medium text-accent-700 underline' : 'hover:text-accent-600' }}">Last {{ $option }} days</a>
        @endforeach
    </nav>

    <p class="mt-6 text-lg">Total page views: <strong>{{ number_format($total) }}</strong></p>

    @foreach (['Songs' => [$songs, 'admin.songs.edit'], 'Artists' => [$artists, 'admin.artists.edit'], 'Albums' => [$albums, 'admin.albums.edit']] as $heading => [$rows, $editRoute])
        <section class="mt-8" aria-labelledby="top-{{ strtolower($heading) }}">
            <h2 id="top-{{ strtolower($heading) }}" class="text-lg font-semibold">Top {{ strtolower($heading) }}</h2>
            @if ($rows->isEmpty())
                <p class="mt-2 text-sm text-muted">No views recorded in this period yet.</p>
            @else
                <ol class="mt-2 divide-y divide-line rounded-md border border-line text-sm">
                    @foreach ($rows as $row)
                        <li class="flex items-center justify-between gap-3 p-3">
                            <a class="font-medium hover:text-accent-600" href="{{ route($editRoute, $row['model']) }}">{{ $row['model']->title ?? $row['model']->name }}</a>
                            <span class="text-muted">{{ number_format($row['views']) }} views</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    @endforeach
</x-admin-layout>
