<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $latestSongs = Song::publiclyVisible()->with('artists')->latest('songs.published_at')->limit(10)->get();

        $artists = Artist::published()
            ->latest('artists.published_at')->limit(8)->get();

        $albums = Album::published()
            ->with('artists')->latest('albums.published_at')->limit(6)->get();

        $genres = Genre::whereHas('songs', fn ($q) => $q->publiclyVisible())->orderBy('name')->get();

        return view('home', compact('latestSongs', 'artists', 'albums', 'genres'));
    }
}
