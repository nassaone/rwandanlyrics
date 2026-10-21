<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Contracts\View\View;

class ArtistController extends Controller
{
    public function index(): View
    {
        $artists = Artist::published()
            ->orderBy('artists.name')->paginate(24)->withQueryString();

        return view('artists.index', compact('artists'));
    }

    public function show(string $slug): View
    {
        $artist = Artist::published()->where('artists.slug', $slug)->with('genres')->firstOrFail();

        $songs = $artist->songs()->publiclyVisible()->with('artists')
            ->latest('songs.published_at')->paginate(20)->withQueryString();

        $albums = $artist->albums()->published()->orderByDesc('albums.release_date')->get();

        return view('artists.show', compact('artist', 'songs', 'albums'));
    }
}
