<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublicationStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArtistRequest;
use App\Http\Requests\Admin\MergeArtistRequest;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\User;
use App\Services\MediaService;
use App\Support\AdminDate;
use App\Support\AuditLogger;
use App\Support\Redirects;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ArtistController extends Controller
{
    public function __construct(private MediaService $media) {}

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

        $artist = $this->persist(new Artist, $request->validated(), $request->user());

        return redirect()->route('admin.artists.edit', $artist)->with('status', 'Artist created.');
    }

    public function edit(Request $request, Artist $artist): View
    {
        Gate::authorize('update', $artist);

        $artist->load(['genres', 'aliases', 'image', 'banner']);

        $others = $request->user()->atLeast(Role::Admin)
            ? Artist::where('id', '!=', $artist->id)->orderBy('name')->pluck('name', 'id')
            : collect();

        return view('admin.artists.edit', ['artist' => $artist, 'genres' => $this->genres(), 'others' => $others]);
    }

    public function update(ArtistRequest $request, Artist $artist): RedirectResponse
    {
        Gate::authorize('update', $artist);

        $this->persist($artist, $request->validated(), $request->user());

        return redirect()->route('admin.artists.edit', $artist)->with('status', 'Artist saved.');
    }

    public function destroy(Artist $artist): RedirectResponse
    {
        Gate::authorize('delete', $artist);

        AuditLogger::record('artist.deleted', $artist);
        $artist->delete();

        return redirect()->route('admin.artists.index')->with('status', 'Artist deleted.');
    }

    public function merge(MergeArtistRequest $request, Artist $artist): RedirectResponse
    {
        Gate::authorize('delete', $artist);

        $target = Artist::findOrFail($request->validated('target_id'));
        $name = $artist->name;
        $moved = $this->mergeInto($artist, $target, $request->user());

        return redirect()->route('admin.artists.edit', $target)
            ->with('status', "Merged \"{$name}\" into this artist ({$moved} song(s) moved).");
    }

    private function genres()
    {
        return Genre::orderBy('name')->get(['id', 'name']);
    }

    private function persist(Artist $artist, array $data, User $actor): Artist
    {
        return DB::transaction(function () use ($artist, $data, $actor) {
            $isNew = ! $artist->exists;
            $oldStatus = $artist->status?->value;
            $oldSlug = $artist->slug;
            $hadPublicUrl = ! $isNew && $artist->published_at !== null;

            $attributes = Arr::only($data, ['name', 'slug', 'bio', 'country_code', 'website', 'status', 'verification_status', 'seo_title', 'seo_description']);
            $attributes['noindex'] = (bool) ($data['noindex'] ?? false);
            $attributes['published_at'] = AdminDate::fromInput($data['published_at'] ?? null);

            if (($attributes['status'] ?? null) === 'published' && $attributes['published_at'] === null) {
                $attributes['published_at'] = now();
            }
            if (blank($attributes['slug'] ?? null)) {
                unset($attributes['slug']);
            }

            $this->media->apply($artist, 'image_id', $data['image'] ?? null, $data['image_alt'] ?? null, (bool) ($data['remove_image'] ?? false), $actor);
            $this->media->apply($artist, 'banner_id', $data['banner'] ?? null, $data['banner_alt'] ?? null, (bool) ($data['remove_banner'] ?? false), $actor);

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
            if ($hadPublicUrl && $artist->slug !== $oldSlug) {
                Redirects::record(route('artists.show', $oldSlug, false), route('artists.show', $artist->slug, false), $actor);
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

    /** Moves everything from $source to $target, keeps the old names as aliases, redirects the old URL. */
    private function mergeInto(Artist $source, Artist $target, User $actor): int
    {
        return DB::transaction(function () use ($source, $target, $actor) {
            $targetSongs = DB::table('song_artist')->where('artist_id', $target->id)->pluck('song_id');
            $moved = DB::table('song_artist')->where('artist_id', $source->id)->whereNotIn('song_id', $targetSongs)->count();
            DB::table('song_artist')->where('artist_id', $source->id)->whereIn('song_id', $targetSongs)->delete();
            DB::table('song_artist')->where('artist_id', $source->id)->update(['artist_id' => $target->id]);

            $targetAlbums = DB::table('album_artist')->where('artist_id', $target->id)->pluck('album_id');
            DB::table('album_artist')->where('artist_id', $source->id)->whereIn('album_id', $targetAlbums)->delete();
            DB::table('album_artist')->where('artist_id', $source->id)->update(['artist_id' => $target->id]);

            $targetGenres = DB::table('genre_artist')->where('artist_id', $target->id)->pluck('genre_id');
            DB::table('genre_artist')->where('artist_id', $source->id)->whereIn('genre_id', $targetGenres)->delete();
            DB::table('genre_artist')->where('artist_id', $source->id)->update(['artist_id' => $target->id]);

            $names = array_merge([$source->name], $source->aliases()->pluck('alias')->all());
            foreach ($names as $name) {
                $normalized = Artist::normalize($name);
                if ($normalized === $target->normalized_name || $target->aliases()->where('normalized_alias', $normalized)->exists()) {
                    continue;
                }
                $target->aliases()->create(['alias' => $name, 'approved' => true]);
            }

            if ($source->published_at !== null) {
                Redirects::record(route('artists.show', $source->slug, false), route('artists.show', $target->slug, false), $actor);
            }

            AuditLogger::record('artist.merged', $target, ['merged_name' => $source->name, 'merged_id' => $source->id, 'songs_moved' => $moved], $actor);
            $source->delete();

            return $moved;
        });
    }
}
