<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request, SearchService $search): View
    {
        $q = SearchService::normalize($request->query('q'));

        if (! SearchService::isSearchable($q)) {
            return view('search.index', ['q' => $q, 'searched' => false, 'songs' => null, 'artists' => collect(), 'albums' => collect()]);
        }

        return view('search.index', [
            'q' => $q,
            'searched' => true,
            'songs' => $search->songs($q)->withQueryString(),
            'artists' => $search->artists($q),
            'albums' => $search->albums($q),
        ]);
    }
}
