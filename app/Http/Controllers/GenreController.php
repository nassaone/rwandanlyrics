<?php

namespace App\Http\Controllers;

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

        $songs = $genre->songs()->publiclyVisible()->with('artists')
            ->latest('songs.published_at')->paginate(20)->withQueryString();

        return view('genres.show', compact('genre', 'songs'));
    }
}
