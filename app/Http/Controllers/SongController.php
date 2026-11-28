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
            ->with(['artists.image', 'genres', 'language', 'currentLyric'])
            ->firstOrFail();

        $linkableArtistIds = Artist::published()
            ->whereIn('artists.id', $song->artists->pluck('id'))
            ->pluck('artists.id')->all();

        $album = $song->album_id ? Album::published()->find($song->album_id) : null;

        $genreIds = $song->genres->pluck('id');

        $cards = fn () => Song::publiclyVisible()
            ->whereKeyNot($song->id)
            ->with(['artists.image', 'cover', 'album.cover'])
            ->withExists('currentLyric')
            ->orderByDesc('current_lyric_exists')
            ->latest('songs.published_at');

        $moreArtist = $song->artists->first(fn ($a) => in_array($a->id, $linkableArtistIds, true));
        $moreFromArtist = $moreArtist
            ? $cards()->whereHas('artists', fn ($q) => $q->where('artists.id', $moreArtist->id))->limit(6)->get()
            : collect();

        $related = $genreIds->isEmpty() ? collect() : $cards()
            ->whereNotIn('songs.id', $moreFromArtist->pluck('id'))
            ->whereHas('genres', fn ($q) => $q->whereIn('genres.id', $genreIds))
            ->limit(6)->get();

        $latest = $cards()
            ->whereNotIn('songs.id', $moreFromArtist->pluck('id')->merge($related->pluck('id')))
            ->limit(6)->get();

        return view('songs.show', compact('song', 'linkableArtistIds', 'album', 'moreArtist', 'moreFromArtist', 'related', 'latest'));
    }
}
