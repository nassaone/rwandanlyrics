<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Services\ViewStats;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SongController extends Controller
{
    public function index(Request $request): View
    {
        $sort = in_array($request->query('sort'), ['trending', 'az'], true) ? $request->query('sort') : 'newest';
        $genres = Genre::orderBy('name')->get(['id', 'name', 'slug']);
        $genre = $genres->firstWhere('slug', $request->query('genre'));

        if ($sort === 'trending') {
            $songs = app(ViewStats::class)->trendingSongs(null, 40);

            if ($genre) {
                $songs = $songs->load('genres:id')
                    ->filter(fn ($song) => $song->genres->contains('id', $genre->id))
                    ->values();
            }

            $total = $songs->count();
        } else {
            $query = Song::publiclyVisible()->with(['artists.image', 'cover', 'album.cover']);

            if ($genre) {
                $query->whereHas('genres', fn ($q) => $q->where('genres.id', $genre->id));
            }

            $sort === 'az' ? $query->orderBy('songs.title') : $query->latest('songs.published_at');

            $songs = $query->paginate(20)->withQueryString();
            $total = $songs->total();
        }

        return view('songs.index', compact('songs', 'sort', 'genres', 'genre', 'total'));
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
            ->with(['artists.image', 'cover', 'album.cover'])->latest('songs.published_at')->limit(6)->get();

        return view('songs.show', compact('song', 'linkableArtistIds', 'album', 'related'));
    }
}
