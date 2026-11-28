<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Services\ViewStats;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $withCovers = ['artists.image', 'cover', 'album.cover'];

        $latestSongs = Song::publiclyVisible()->with($withCovers)->latest('songs.published_at')->limit(10)->get();

        $artists = Artist::published()->with('image')
            ->latest('artists.published_at')->limit(8)->get();

        $albums = Album::published()
            ->with('artists', 'cover')->latest('albums.published_at')->limit(6)->get();

        $genres = Genre::whereHas('songs', fn ($q) => $q->publiclyVisible())
            ->withCount(['songs as visible_songs_count' => fn ($q) => $q->publiclyVisible()])
            ->orderByDesc('visible_songs_count')->orderBy('name')->limit(8)->get();

        $popularSongs = app(ViewStats::class)->trendingSongs();

        // The trending strip shows covers, so reload those songs with their images in one go.
        $ids = $popularSongs->pluck('id')->all();
        $trending = $ids
            ? Song::publiclyVisible()->with($withCovers)->whereIn('songs.id', $ids)->get()
                ->sortBy(fn ($s) => array_search($s->id, $ids))->values()
            : collect();

        $featured = $trending->first() ?? $latestSongs->first();

        $counts = [
            'songs' => Song::publiclyVisible()->count(),
            'artists' => Artist::published()->whereHas('songs', fn ($q) => $q->publiclyVisible())->count(),
            'albums' => Album::published()->whereHas('songs', fn ($q) => $q->publiclyVisible())->count(),
        ];

        return view('home', compact('latestSongs', 'popularSongs', 'trending', 'featured', 'counts', 'artists', 'albums', 'genres'));
    }
}
