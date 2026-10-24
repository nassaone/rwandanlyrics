@props(['title' => null, 'description' => 'Discover Rwandan music, artists, albums and authorized lyrics.', 'robots' => 'index,follow', 'ogType' => 'website'])
@php $pageDescription = trim($__env->yieldPushContent('seo_description')) ?: $description; @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}{{ config('app.name') }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#1b6b45">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('og-default.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('og-default.png') }}">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:p-3 focus:bg-white">Skip to content</a>

    <header class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur" x-data="{ open: false }" @keydown.escape.window="open = false">
        <div class="flex h-1" aria-hidden="true"><span class="flex-1 bg-accent-600"></span><span class="flex-1 bg-sun"></span><span class="flex-1 bg-sky"></span></div>
        <div class="mx-auto max-w-5xl px-4 sm:px-6 h-14 flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="font-bold text-xl tracking-tight">Rwandan<span class="text-accent-600">lyrics</span></a>
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium" aria-label="Main">
                <x-nav-links />
            </nav>
            <button type="button" class="md:hidden inline-flex size-11 items-center justify-center rounded-lg -mr-2 hover:bg-surface" @click="open = !open" :aria-expanded="open" aria-label="Menu">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path x-show="!open" d="M4 6h16M4 12h16M4 18h16"/><path x-show="open" x-cloak d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <nav x-show="open" x-cloak class="md:hidden border-t border-line bg-white px-4 py-3 text-base font-medium" aria-label="Mobile">
            <x-nav-links block />
        </nav>
    </header>

    <div class="border-t border-line bg-surface">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 py-3"><x-search-form /></div>
    </div>

    <main id="main" class="flex-1 mx-auto w-full max-w-5xl px-4 sm:px-6 py-6 sm:py-10">
        @if (session('status'))
            <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
        @endif
        {{ $slot }}
    </main>

    <footer class="border-t border-line bg-surface text-sm text-muted">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 py-8 grid gap-6 sm:grid-cols-[1fr_auto] sm:items-start">
            <div>
                <p class="font-bold text-ink text-base">Rwandan<span class="text-accent-600">lyrics</span></p>
                <p class="mt-1 max-w-xs">Rwandan music, artists and authorized lyrics.</p>
                <p class="mt-3">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            </div>
            <nav class="grid grid-cols-2 gap-x-8 gap-y-1 sm:grid-cols-2" aria-label="Footer">
                <a href="{{ route('songs.index') }}" class="py-1.5 hover:text-accent-600">Lyrics</a>
                <a href="{{ route('legal.privacy') }}" class="py-1.5 hover:text-accent-600">Privacy</a>
                <a href="{{ route('artists.index') }}" class="py-1.5 hover:text-accent-600">Artists</a>
                <a href="{{ route('legal.copyright') }}" class="py-1.5 hover:text-accent-600">Copyright policy</a>
                <a href="{{ route('genres.index') }}" class="py-1.5 hover:text-accent-600">Genres</a>
                <a href="{{ route('legal.takedown') }}" class="py-1.5 hover:text-accent-600">Takedown requests</a>
                <span class="hidden sm:block"></span>
                <a href="{{ route('legal.corrections') }}" class="py-1.5 hover:text-accent-600">Report an error</a>
            </nav>
        </div>
    </footer>
</body>
</html>
