<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArtistRequest;
use App\Models\Artist;
use App\Models\Genre;
use App\Support\AdminDate;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ArtistController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Artist::class);

        $q = trim((string) $request->query('q'));

        $artists = Artist::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
            ->when(PublicationStatus::tryFrom((string) $request->query('status')), fn ($query, $status) => $query->where('status', $status->value))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.artists.index', compact('artists'));
    }

    public function create(): View
    {
        Gate::authorize('create', Artist::class);

        return view('admin.artists.create', ['artist' => new Artist, 'genres' => $this->genres()]);
    }

    public function store(ArtistRequest $request): RedirectResponse
    {
        Gate::authorize('create', Artist::class);

        $artist = $this->persist(new Artist, $request->validated());

        return redirect()->route('admin.artists.edit', $artist)->with('status', 'Artist created.');
    }

    public function edit(Artist $artist): View
    {
        Gate::authorize('update', $artist);

        $artist->load(['genres', 'aliases']);

        return view('admin.artists.edit', ['artist' => $artist, 'genres' => $this->genres()]);
    }

    public function update(ArtistRequest $request, Artist $artist): RedirectResponse
    {
        Gate::authorize('update', $artist);

        $this->persist($artist, $request->validated());

        return redirect()->route('admin.artists.edit', $artist)->with('status', 'Artist saved.');
    }

    public function destroy(Artist $artist): RedirectResponse
    {
        Gate::authorize('delete', $artist);

        AuditLogger::record('artist.deleted', $artist);
        $artist->delete();

        return redirect()->route('admin.artists.index')->with('status', 'Artist deleted.');
    }

    private function genres()
    {
        return Genre::orderBy('name')->get(['id', 'name']);
    }

    private function persist(Artist $artist, array $data): Artist
    {
        return DB::transaction(function () use ($artist, $data) {
            $isNew = ! $artist->exists;
            $oldStatus = $artist->status?->value;

            $attributes = Arr::only($data, ['name', 'slug', 'bio', 'country_code', 'website', 'status', 'verification_status', 'seo_title', 'seo_description']);
            $attributes['noindex'] = (bool) ($data['noindex'] ?? false);
            $attributes['published_at'] = AdminDate::fromInput($data['published_at'] ?? null);

            if (($attributes['status'] ?? null) === 'published' && $attributes['published_at'] === null) {
                $attributes['published_at'] = now();
            }
            if ((! $isNew && $artist->published_at !== null) || blank($attributes['slug'] ?? null)) {
                unset($attributes['slug']);
            }

            $artist->fill($attributes);
            $dirty = array_keys($artist->getDirty());
            $artist->save();

            $genreChanges = array_filter($artist->genres()->sync($data['genre_ids'] ?? []));
            $aliasesChanged = $this->syncAliases($artist, (string) ($data['aliases'] ?? ''));

            if ($isNew) {
                AuditLogger::record('artist.created', $artist, ['status' => $artist->status->value]);
            } elseif ($dirty || $genreChanges || $aliasesChanged) {
                AuditLogger::record('artist.updated', $artist, array_filter(['fields' => $dirty, 'genres_changed' => (bool) $genreChanges, 'aliases_changed' => $aliasesChanged]));
            }
            if (! $isNew && $artist->status->value !== $oldStatus) {
                AuditLogger::record('artist.status_changed', $artist, ['from' => $oldStatus, 'to' => $artist->status->value]);
            }

            return $artist;
        });
    }

    private function syncAliases(Artist $artist, string $text): bool
    {
        $aliases = collect(preg_split('/\R/', $text) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique(fn ($line) => Artist::normalize($line))
            ->reject(fn ($line) => Artist::normalize($line) === $artist->normalized_name)
            ->values()->all();

        $existing = $artist->aliases()->pluck('alias')->all();
        sort($aliases);
        sort($existing);

        if ($aliases === $existing) {
            return false;
        }

        $artist->aliases()->delete();
        foreach ($aliases as $alias) {
            $artist->aliases()->create(['alias' => $alias, 'approved' => true]);
        }

        return true;
    }
}
