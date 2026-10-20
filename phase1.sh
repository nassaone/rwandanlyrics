#!/usr/bin/env bash
# Rwandanlyrics — Phase 1 (Laravel foundation). Run from the ROOT of the repo.
set -euo pipefail

echo "== Environment =="; php -v | head -1; composer --version | head -1; node -v; npm -v; git status --short | head -20

# 1. Scaffold Laravel without overwriting existing files
if [ -f artisan ]; then
  echo "Laravel already present — skipping scaffold."
else
  TMP=$(mktemp -d)
  composer create-project laravel/laravel "$TMP/app" --no-interaction
  cp -rn "$TMP/app/." .
  rm -rf "$TMP"
fi
composer install --no-interaction
[ -f .env ] || cp .env.example .env
php artisan key:generate --force

# 2. Branding + DB (SQLite dev-only; production target is MySQL)
sed -i 's/^APP_NAME=.*/APP_NAME=Rwandanlyrics/' .env .env.example
sed -i 's|^APP_URL=.*|APP_URL=http://localhost:8000|' .env.example
touch database/database.sqlite
grep -q '^\.env$' .gitignore || echo ".env" >> .gitignore

# 3. Frontend deps (Laravel 12 ships Tailwind 4 + Vite)
npm install
npm install -D alpinejs

# 4. Design tokens + JS
cat > resources/css/app.css <<'CSS'
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
  --font-sans: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  --color-accent-50: #f5f3ff;
  --color-accent-100: #ede9fe;
  --color-accent-600: #7c3aed;
  --color-accent-700: #6d28d9;
  --color-ink: #1f2937;
  --color-muted: #6b7280;
  --color-line: #e5e7eb;
  --color-surface: #fafafa;
}

@layer base {
  html { -webkit-text-size-adjust: 100%; }
  body { @apply bg-white text-ink antialiased; }
  :focus-visible { @apply outline-2 outline-offset-2 outline-accent-600; }
}

[x-cloak] { display: none !important; }
.lyrics { white-space: pre-line; line-height: 1.8; overflow-wrap: anywhere; }
CSS

cat > resources/js/app.js <<'JS'
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
JS

# 5. Layout + components
mkdir -p resources/views/components/layouts resources/views/errors tests/Feature

cat > resources/views/components/layouts/app.blade.php <<'BLADE'
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
                <a href="{{ route('home') }}" class="hover:text-accent-600">Home</a>
            </nav>
            <button type="button" class="md:hidden p-2 -mr-2" @click="open = !open" :aria-expanded="open" aria-label="Menu">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
        <nav x-show="open" x-cloak class="md:hidden border-t border-line px-4 py-3 text-sm" aria-label="Mobile">
            <a href="{{ route('home') }}" class="block py-2">Home</a>
        </nav>
        <div class="mx-auto max-w-5xl px-4 pb-3"><x-search-form /></div>
    </header>

    <main id="main" class="flex-1 mx-auto w-full max-w-5xl px-4 py-6">{{ $slot }}</main>

    <footer class="border-t border-line bg-surface text-sm text-muted">
        <div class="mx-auto max-w-5xl px-4 py-6">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
    </footer>
</body>
</html>
BLADE

cat > resources/views/components/search-form.blade.php <<'BLADE'
<form action="{{ url('/search') }}" method="GET" role="search" class="flex gap-2">
    <label for="q" class="sr-only">Search songs, artists, albums</label>
    <input id="q" name="q" type="search" maxlength="100" placeholder="Search songs, artists, albums"
           class="flex-1 min-w-0 rounded-md border border-line px-3 py-2 text-base">
    <x-button type="submit">Search</x-button>
</form>
BLADE

cat > resources/views/components/button.blade.php <<'BLADE'
@props(['type' => 'button', 'variant' => 'primary'])
<button type="{{ $type }}" {{ $attributes->class([
    'inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium min-h-10',
    'bg-accent-600 text-white hover:bg-accent-700' => $variant === 'primary',
    'border border-line bg-white hover:bg-surface' => $variant === 'secondary',
]) }}>{{ $slot }}</button>
BLADE

cat > resources/views/components/badge.blade.php <<'BLADE'
<span {{ $attributes->class('inline-block rounded bg-accent-50 text-accent-700 px-2 py-0.5 text-xs font-medium') }}>{{ $slot }}</span>
BLADE

cat > resources/views/components/card.blade.php <<'BLADE'
<div {{ $attributes->class('rounded-md border border-line bg-white p-4') }}>{{ $slot }}</div>
BLADE

cat > resources/views/components/alert.blade.php <<'BLADE'
@props(['type' => 'info'])
<div role="alert" {{ $attributes->class([
    'rounded-md border px-4 py-3 text-sm',
    'border-accent-100 bg-accent-50 text-accent-700' => $type === 'info',
    'border-red-200 bg-red-50 text-red-800' => $type === 'error',
    'border-green-200 bg-green-50 text-green-800' => $type === 'success',
]) }}>{{ $slot }}</div>
BLADE

cat > resources/views/components/empty-state.blade.php <<'BLADE'
@props(['title', 'message' => null])
<div class="rounded-md border border-dashed border-line p-8 text-center">
    <p class="font-medium">{{ $title }}</p>
    @if ($message)<p class="mt-1 text-sm text-muted">{{ $message }}</p>@endif
    {{ $slot }}
</div>
BLADE

# 6. Home page (honest empty state; no fabricated content)
cat > resources/views/home.blade.php <<'BLADE'
<x-layouts.app title="Rwandan music, artists and lyrics">
    <h1 class="text-2xl font-semibold">Rwandanlyrics</h1>
    <p class="mt-1 text-muted">Discover Rwandan music, artists, albums and authorized lyrics.</p>
    <div class="mt-6">
        <x-empty-state title="No lyrics published yet" message="Content will appear here once the catalog is added." />
    </div>
</x-layouts.app>
BLADE

# 7. Error pages
for code in 404 500 503; do
  case $code in 404) t="Page not found"; m="We couldn't find that page.";; 500) t="Something went wrong"; m="Please try again shortly.";; 503) t="Back soon"; m="We're doing maintenance.";; esac
  cat > resources/views/errors/$code.blade.php <<BLADE
<x-layouts.app title="$t" robots="noindex,nofollow">
    <h1 class="text-2xl font-semibold">$t</h1>
    <p class="mt-1 text-muted">$m</p>
    <p class="mt-4"><a class="text-accent-600 underline" href="{{ route('home') }}">Go to the homepage</a></p>
</x-layouts.app>
BLADE
done

# 8. Routes (/up health check is built into Laravel)
cat > routes/web.php <<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
PHP

# 9. Tests
cat > tests/Feature/HomePageTest.php <<'PHP'
<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders(): void
    {
        $this->get('/')->assertOk()->assertSee('Rwandanlyrics')->assertSee('No lyrics published yet');
    }

    public function test_unknown_page_returns_404(): void
    {
        $this->get('/does-not-exist')->assertNotFound()->assertSee('Page not found');
    }

    public function test_health_check_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}
PHP

# 10. Migrate, format, test, build
php artisan migrate --force
./vendor/bin/pint
php artisan test
npm run build

echo "== Done. If everything passed, run: =="
echo "git add -A && git status   # confirm .env is NOT listed"
echo "git commit -m 'Phase 1: Laravel foundation and light design shell'"
echo "Serve: php artisan serve --host=0.0.0.0 --port=8000"
