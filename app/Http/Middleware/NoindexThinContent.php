<?php

namespace App\Http\Middleware;

use App\Models\Album;
use App\Models\Artist;
use Closure;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Artist and album pages that have no publicly visible songs are thin content:
 * visitors may see them, search engines are told not to index them.
 */
class NoindexThinContent
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $original = $response->original ?? null;
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || ! $original instanceof View) {
            return $response;
        }

        $data = $original->getData();
        $thin = false;

        if (($data['artist'] ?? null) instanceof Artist) {
            $thin = ! $data['artist']->songs()->publiclyVisible()->exists();
        } elseif (($data['album'] ?? null) instanceof Album) {
            $thin = ! $data['album']->songs()->publiclyVisible()->exists();
        }

        if ($thin) {
            $response->headers->set('X-Robots-Tag', 'noindex, follow');
        }

        return $response;
    }
}
