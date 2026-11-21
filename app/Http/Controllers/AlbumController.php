<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Services\ViewStats;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function index(Request $request): View
    {
        $sort = in_array($request->query('sort'), ['az', 'trending'], true) ? $request->query('sort') : 'newest';
        $genres = Genre::orderBy('name')->get(['id', 'name', 'slug']);
        $genre = $genres->firstWhere('slug', $request->query('genre'));

        $query = Album::published()->with(['artists', 'cover']);
        if ($genre) {
            $query->whereHas('songs.genres', fn ($g) => $g->where('genres.id', $genre->id));
        }

        if ($sort === 'trending') {
            $ids = app(ViewStats::class)->trendingIds('album', null, 120);
            $found = $query->whereIn('albums.id', $ids ?: [0])->get()->keyBy('id');
            $albums = collect($ids)->map(fn ($id) => $found[$id] ?? null)->filter()->take(40)->values();
            $total = $albums->count();
        } else {
            if ($sort === 'az') {
                $query->orderBy('albums.title');
            } else {
                $query->orderByDesc('albums.release_date')->orderByDesc('albums.published_at');
            }
            $albums = $query->paginate(24)->withQueryString();
            $total = $albums->total();
        }

        $filtered = $genre || $sort !== 'newest';

        return view('albums.index', compact('albums', 'sort', 'genres', 'genre', 'total', 'filtered'));
    }

    public function show(string $slug): View
    {
        $album = Album::published()->where('albums.slug', $slug)->with('artists')->firstOrFail();

        $tracks = $album->songs()->publiclyVisible()->with('artists')->get();

        $linkableArtistIds = Artist::published()
            ->whereIn('artists.id', $album->artists->pluck('id'))
            ->pluck('artists.id')->all();

        return view('albums.show', compact('album', 'tracks', 'linkableArtistIds'));
    }
}
