<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Contracts\View\View;

class AlbumController extends Controller
{
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
