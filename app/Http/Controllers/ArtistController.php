<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Genre;
use App\Services\ViewStats;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function index(Request $request): View
    {
        $sort = in_array($request->query('sort'), ['newest', 'trending'], true) ? $request->query('sort') : 'az';
        $genres = Genre::orderBy('name')->get(['id', 'name', 'slug']);
        $genre = $genres->firstWhere('slug', $request->query('genre'));

        $query = Artist::published()->with('image');
        if ($genre) {
            $query->where(fn ($q) => $q
                ->whereHas('genres', fn ($g) => $g->where('genres.id', $genre->id))
                ->orWhereHas('songs.genres', fn ($g) => $g->where('genres.id', $genre->id)));
        }

        if ($sort === 'trending') {
            $ids = app(ViewStats::class)->trendingIds('artist', null, 120);
            $found = $query->whereIn('artists.id', $ids ?: [0])->get()->keyBy('id');
            $artists = collect($ids)->map(fn ($id) => $found[$id] ?? null)->filter()->take(40)->values();
            $total = $artists->count();
        } else {
            if ($sort === 'newest') {
                $query->latest('artists.published_at');
            } else {
                $query->orderBy('artists.name');
            }
            $artists = $query->paginate(24)->withQueryString();
            $total = $artists->total();
        }

        $filtered = $genre || $sort !== 'az';

        return view('artists.index', compact('artists', 'sort', 'genres', 'genre', 'total', 'filtered'));
    }

    public function show(string $slug): View
    {
        $artist = Artist::published()->where('artists.slug', $slug)->with('genres')->firstOrFail();

        $songs = $artist->songs()->publiclyVisible()->with(['artists.image', 'cover', 'album.cover'])
            ->latest('songs.published_at')->paginate(20)->withQueryString();

        $albums = $artist->albums()->published()->orderByDesc('albums.release_date')->get();

        return view('artists.show', compact('artist', 'songs', 'albums'));
    }
}
