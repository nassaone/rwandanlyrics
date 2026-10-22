<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\ViewCounter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Counts a view of a song, artist or album page after the response is sent. */
class CountViews
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->eligible($request, $response)) {
            $data = $response->original->getData();
            foreach (['song' => Song::class, 'artist' => Artist::class, 'album' => Album::class] as $type => $class) {
                if (($data[$type] ?? null) instanceof $class) {
                    $request->attributes->set('view_target', [$type, $data[$type]->id]);
                    break;
                }
            }
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $target = $request->attributes->get('view_target');
        if (! $target) {
            return;
        }

        try {
            app(ViewCounter::class)->record($target[0], (int) $target[1], (string) $request->ip(), (string) $request->userAgent());
        } catch (\Throwable $e) {
            report($e); // counting must never break a page
        }
    }

    private function eligible(Request $request, Response $response): bool
    {
        if (! config('rwandanlyrics.analytics.enabled', true)
            || ! $request->isMethod('GET')
            || $response->getStatusCode() !== 200
            || ! (($response->original ?? null) instanceof View)) {
            return false;
        }

        // Respect Do Not Track and Global Privacy Control.
        if ($request->header('DNT') === '1' || $request->header('Sec-GPC') === '1') {
            return false;
        }

        foreach (['Purpose', 'Sec-Purpose', 'X-Moz'] as $header) {
            if (str_contains(strtolower((string) $request->header($header)), 'prefetch')) {
                return false;
            }
        }

        if (! ViewCounter::isHuman($request->userAgent())) {
            return false;
        }

        $user = $request->user();

        return ! ($user && $user->atLeast(Role::Editor));
    }
}
