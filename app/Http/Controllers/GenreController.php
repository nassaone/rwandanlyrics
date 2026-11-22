<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Genre;
use Illuminate\Contracts\View\View;

class GenreController extends Controller
{
    public function index(): View
    {
        $genres = Genre::whereHas('songs', fn ($q) => $q->publiclyVisible())
            ->withCount(['songs as visible_songs_count' => fn ($q) => $q->publiclyVisible()])
            ->orderBy('name')->get();

        return view('genres.index', compact('genres'));
    }

    public function show(string $slug): View
    {
        $genre = Genre::where('slug', $slug)->firstOrFail();

        $songs = $genre->songs()->publiclyVisible()->with(['artists.image', 'cover', 'album.cover'])
            ->latest('songs.published_at')->paginate(20)->withQueryString();

        $inGenre = fn ($q) => $q->publiclyVisible()->whereHas('genres', fn ($g) => $g->where('genres.id', $genre->id));

        $topArtists = Artist::published()
            ->whereHas('songs', $inGenre)
            ->withCount(['songs as genre_songs_count' => $inGenre])
            ->with('image')
            ->orderByDesc('genre_songs_count')->orderBy('artists.name')->limit(8)->get();

        $otherGenres = Genre::whereKeyNot($genre->id)
            ->whereHas('songs', fn ($q) => $q->publiclyVisible())
            ->orderBy('name')->limit(12)->get();

        return view('genres.show', compact('genre', 'songs', 'topArtists', 'otherGenres'));
    }
}
