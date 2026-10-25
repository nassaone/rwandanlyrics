@props(['title'])
@php
    $items = [
        'Dashboard' => ['admin.dashboard', 'admin.dashboard'],
        'Songs' => ['admin.songs.index', 'admin.songs.*'],
        'Artists' => ['admin.artists.index', 'admin.artists.*'],
        'Albums' => ['admin.albums.index', 'admin.albums.*'],
        'Genres' => ['admin.genres.index', 'admin.genres.*'],
        'Languages' => ['admin.languages.index', 'admin.languages.*'],
        'Submissions' => ['admin.submissions.index', 'admin.submissions.*'],
        'Analytics' => ['admin.analytics.index', 'admin.analytics.*'],
        'Media' => ['admin.media.index', 'admin.media.*'],
        'Redirects' => ['admin.redirects.index', 'admin.redirects.*'],
    ];
    if (auth()->user()->atLeast(\App\Enums\Role::Admin)) {
        $items['Users'] = ['admin.users.index', 'admin.users.*'];
        $items['Activity'] = ['admin.audit.index', 'admin.audit.*'];
    }
    if (auth()->user()->atLeast(\App\Enums\Role::SuperAdmin)) {
        $items['Settings'] = ['admin.settings.index', 'admin.settings.*'];
    }
@endphp
<x-layouts.app :title="$title" robots="noindex,nofollow">
    <nav class="-mx-4 mb-6 flex gap-5 overflow-x-auto border-b border-line px-4 text-sm" aria-label="Admin">
        @foreach ($items as $label => [$route, $pattern])
            <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif
               class="whitespace-nowrap border-b-2 py-3 {{ request()->routeIs($pattern) ? 'border-accent-600 font-medium text-accent-700' : 'border-transparent hover:text-accent-600' }}">{{ $label }}</a>
        @endforeach
    </nav>
    {{ $slot }}
</x-layouts.app>
