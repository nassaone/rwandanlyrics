<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Contracts\View\View;

class SongController extends Controller
{
    public function index(): View
    {
        $songs = Song::publiclyVisible()->with('artists')
            ->latest('songs.published_at')->paginate(20)->withQueryString();

        return view('songs.index', compact('songs'));
    }

    public function show(string $slug): View
    {
        $song = Song::publiclyVisible()
            ->where('songs.slug', $slug)
            ->with(['artists', 'genres', 'language', 'currentLyric'])
            ->firstOrFail();

        $linkableArtistIds = Artist::published()
            ->whereIn('artists.id', $song->artists->pluck('id'))
            ->pluck('artists.id')->all();

        $album = $song->album_id ? Album::published()->find($song->album_id) : null;

        $artistIds = $song->artists->pluck('id');
        $genreIds = $song->genres->pluck('id');

        $related = Song::publiclyVisible()
            ->whereKeyNot($song->id)
            ->where(function ($query) use ($artistIds, $genreIds) {
                $query->whereHas('artists', fn ($q) => $q->whereIn('artists.id', $artistIds))
                    ->orWhereHas('genres', fn ($q) => $q->whereIn('genres.id', $genreIds));
            })
            ->with('artists')->latest('songs.published_at')->limit(6)->get();

        return view('songs.show', compact('song', 'linkableArtistIds', 'album', 'related'));
    }
}
