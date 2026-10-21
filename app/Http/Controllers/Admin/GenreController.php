<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenreRequest;
use App\Models\Genre;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class GenreController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Genre::class);

        return view('admin.genres.index', ['genres' => Genre::withCount('songs')->orderBy('name')->paginate(50)]);
    }

    public function store(GenreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Genre::class);

        $genre = Genre::create($request->safe()->only(['name', 'slug', 'description']));
        AuditLogger::record('genre.created', $genre);

        return redirect()->route('admin.genres.index')->with('status', 'Genre created.');
    }

    public function edit(Genre $genre): View
    {
        Gate::authorize('update', $genre);

        return view('admin.genres.edit', compact('genre'));
    }

    public function update(GenreRequest $request, Genre $genre): RedirectResponse
    {
        Gate::authorize('update', $genre);

        // The slug is part of a public URL, so it is never changed here.
        $genre->update($request->safe()->only(['name', 'description']));
        AuditLogger::record('genre.updated', $genre);

        return redirect()->route('admin.genres.index')->with('status', 'Genre saved.');
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        Gate::authorize('delete', $genre);

        AuditLogger::record('genre.deleted', $genre);
        $genre->delete();

        return redirect()->route('admin.genres.index')->with('status', 'Genre deleted.');
    }
}
