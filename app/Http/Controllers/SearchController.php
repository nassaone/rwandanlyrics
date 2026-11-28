<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request, SearchService $search): View
    {
        $q = SearchService::normalize($request->query('q'));

        if (! SearchService::isSearchable($q)) {
            return view('search.index', [
                'q' => $q, 'searched' => false, 'songs' => null,
                'artists' => collect(), 'albums' => collect(), 'lyricSongs' => collect(), 'suggestion' => null,
            ]);
        }

        $songs = $search->songs($q)->withQueryString();
        $artists = $search->artists($q);
        $albums = $search->albums($q);
        $lyricSongs = $search->lyricMatches($q, $songs->pluck('id')->all());

        $nothing = $songs->total() === 0 && $artists->isEmpty() && $albums->isEmpty() && $lyricSongs->isEmpty();

        return view('search.index', [
            'q' => $q,
            'searched' => true,
            'songs' => $songs,
            'artists' => $artists,
            'albums' => $albums,
            'lyricSongs' => $lyricSongs,
            'suggestion' => $nothing ? $search->suggest($q) : null,
        ]);
    }

    public function suggest(Request $request, SearchService $search): JsonResponse
    {
        $q = SearchService::normalize($request->query('q'));

        if (! SearchService::isSearchable($q)) {
            return response()->json(['songs' => [], 'artists' => []]);
        }

        return response()->json($search->quick($q));
    }
}
