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
        $latestSongs = Song::publiclyVisible()->with('artists')->latest('songs.published_at')->limit(10)->get();

        $artists = Artist::published()->with('image')
            ->latest('artists.published_at')->limit(8)->get();

        $albums = Album::published()
            ->with('artists', 'cover')->latest('albums.published_at')->limit(6)->get();

        $genres = Genre::whereHas('songs', fn ($q) => $q->publiclyVisible())->orderBy('name')->get();

        $popularSongs = app(ViewStats::class)->popularSongs();

        return view('home', compact('latestSongs', 'popularSongs', 'artists', 'albums', 'genres'));
    }
}
