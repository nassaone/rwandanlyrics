<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SeoController extends Controller
{
    public const PER_SITEMAP = 1000;

    public function robots(): Response
    {
        if (config('app.env') !== 'production') {
            // Never let staging, Codespaces or local copies be indexed.
            $body = "User-agent: *\nDisallow: /\n";
        } else {
            $body = "User-agent: *\n"
                ."Disallow: /admin\nDisallow: /account\nDisallow: /contribute\nDisallow: /contributor\n"
                ."Disallow: /login\nDisallow: /register\nDisallow: /forgot-password\nDisallow: /reset-password\nDisallow: /search\n\n"
                .'Sitemap: '.url('/sitemap.xml')."\n";
        }

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function index(): Response
    {
        $entries = '';
        foreach (['pages', 'songs', 'artists', 'albums', 'genres'] as $type) {
            $total = $type === 'pages' ? 1 : $this->query($type)->count();
            if ($total === 0) {
                continue;
            }
            $pages = (int) max(1, ceil($total / self::PER_SITEMAP));
            for ($page = 1; $page <= $pages; $page++) {
                $entries .= '<sitemap><loc>'.e(url("/sitemaps/{$type}-{$page}.xml")).'</loc></sitemap>';
            }
        }

        return $this->xml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$entries.'</sitemapindex>');
    }

    public function show(string $type, int $page): Response
    {
        $urls = '';

        if ($type === 'pages') {
            $paths = ['/'];
            foreach (['songs.index', 'artists.index', 'albums.index', 'genres.index', 'legal.privacy', 'legal.copyright', 'legal.takedown', 'legal.corrections'] as $name) {
                if (Route::has($name)) {
                    $paths[] = route($name, [], false);
                }
            }
            foreach ($paths as $path) {
                $urls .= '<url><loc>'.e(url($path)).'</loc></url>';
            }
        } else {
            $models = $this->query($type)->orderBy('id')->forPage($page, self::PER_SITEMAP)->get();
            abort_if($models->isEmpty() && $page > 1, 404);
            $route = ['songs' => 'songs.show', 'artists' => 'artists.show', 'albums' => 'albums.show', 'genres' => 'genres.show'][$type];
            foreach ($models as $model) {
                $urls .= '<url><loc>'.e(route($route, $model->slug)).'</loc>'
                    .($model->updated_at ? '<lastmod>'.$model->updated_at->toAtomString().'</lastmod>' : '')
                    .'</url>';
            }
        }

        return $this->xml('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$urls.'</urlset>');
    }

    /** Only publicly visible, indexable content. Artists/albums need at least one visible song. */
    private function query(string $type)
    {
        return match ($type) {
            'songs' => Song::publiclyVisible()->where('songs.noindex', false),
            'artists' => Artist::published()->where('artists.noindex', false)->whereHas('songs', fn ($q) => $q->publiclyVisible()),
            'albums' => Album::published()->where('albums.noindex', false)->whereHas('songs', fn ($q) => $q->publiclyVisible()),
            'genres' => Genre::whereHas('songs', fn ($q) => $q->publiclyVisible()),
        };
    }

    private function xml(string $body): Response
    {
        return response('<?xml version="1.0" encoding="UTF-8"?>'.$body, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
