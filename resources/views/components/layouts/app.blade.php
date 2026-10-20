@props(['title' => null, 'description' => 'Discover Rwandan music, artists, albums and authorized lyrics.', 'robots' => 'index,follow'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}{{ config('app.name') }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $description }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:p-3 focus:bg-white">Skip to content</a>

    <header class="border-b border-line bg-white" x-data="{ open: false }">
        <div class="mx-auto max-w-5xl px-4 h-14 flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="font-bold text-lg tracking-tight">Rwandan<span class="text-accent-600">lyrics</span></a>
            <nav class="hidden md:flex items-center gap-5 text-sm" aria-label="Main">
                <a href="{{ route('songs.index') }}" class="hover:text-accent-600">Lyrics</a>
                <a href="{{ route('artists.index') }}" class="hover:text-accent-600">Artists</a>
                <a href="{{ route('genres.index') }}" class="hover:text-accent-600">Genres</a>
            </nav>
            <button type="button" class="md:hidden p-2 -mr-2" @click="open = !open" :aria-expanded="open" aria-label="Menu">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
        <nav x-show="open" x-cloak class="md:hidden border-t border-line px-4 py-2 text-sm" aria-label="Mobile">
            <a href="{{ route('songs.index') }}" class="block py-3">Lyrics</a>
            <a href="{{ route('artists.index') }}" class="block py-3">Artists</a>
            <a href="{{ route('genres.index') }}" class="block py-3">Genres</a>
        </nav>
        <div class="mx-auto max-w-5xl px-4 pb-3"><x-search-form /></div>
    </header>

    <main id="main" class="flex-1 mx-auto w-full max-w-5xl px-4 py-6">{{ $slot }}</main>

    <footer class="border-t border-line bg-surface text-sm text-muted">
        <div class="mx-auto max-w-5xl px-4 py-6 flex flex-wrap gap-x-5 gap-y-2 justify-between">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
            <nav class="flex gap-4" aria-label="Footer">
                <a href="{{ route('songs.index') }}" class="hover:text-accent-600">Lyrics</a>
                <a href="{{ route('artists.index') }}" class="hover:text-accent-600">Artists</a>
                <a href="{{ route('genres.index') }}" class="hover:text-accent-600">Genres</a>
            </nav>
        </div>
    </footer>
</body>
</html>
