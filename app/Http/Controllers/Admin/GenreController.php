<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenreRequest;
use App\Models\Genre;
use App\Support\AuditLogger;
use App\Support\Redirects;
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

        $oldSlug = $genre->slug;
        $data = $request->safe()->only(['name', 'slug', 'description']);
        if (blank($data['slug'] ?? null)) {
            unset($data['slug']);
        }

        $genre->update($data);
        AuditLogger::record('genre.updated', $genre);

        if ($genre->slug !== $oldSlug) {
            Redirects::record(route('genres.show', $oldSlug, false), route('genres.show', $genre->slug, false));
        }

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
