<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AlbumRequest;
use App\Models\Album;
use App\Models\Artist;
use App\Support\AdminDate;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AlbumController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Album::class);

        $q = trim((string) $request->query('q'));

        $albums = Album::query()->with('artists')
            ->when($q !== '', fn ($query) => $query->where('title', 'like', '%'.$q.'%'))
            ->when(PublicationStatus::tryFrom((string) $request->query('status')), fn ($query, $status) => $query->where('status', $status->value))
            ->latest('updated_at')->latest('id')->paginate(25)->withQueryString();

        return view('admin.albums.index', compact('albums'));
    }

    public function create(): View
    {
        Gate::authorize('create', Album::class);

        return view('admin.albums.create', ['album' => new Album, 'artists' => $this->artists()]);
    }

    public function store(AlbumRequest $request): RedirectResponse
    {
        Gate::authorize('create', Album::class);

        $album = $this->persist(new Album, $request->validated());

        return redirect()->route('admin.albums.edit', $album)->with('status', 'Album created.');
    }

    public function edit(Album $album): View
    {
        Gate::authorize('update', $album);

        $album->load('artists');

        return view('admin.albums.edit', ['album' => $album, 'artists' => $this->artists()]);
    }

    public function update(AlbumRequest $request, Album $album): RedirectResponse
    {
        Gate::authorize('update', $album);

        $this->persist($album, $request->validated());

        return redirect()->route('admin.albums.edit', $album)->with('status', 'Album saved.');
    }

    public function destroy(Album $album): RedirectResponse
    {
        Gate::authorize('delete', $album);

        AuditLogger::record('album.deleted', $album);
        $album->delete();

        return redirect()->route('admin.albums.index')->with('status', 'Album deleted. Its songs were kept.');
    }

    private function artists()
    {
        return Artist::orderBy('name')->pluck('name', 'id');
    }

    private function persist(Album $album, array $data): Album
    {
        return DB::transaction(function () use ($album, $data) {
            $isNew = ! $album->exists;
            $oldStatus = $album->status?->value;

            $attributes = Arr::only($data, ['title', 'slug', 'type', 'release_date', 'description', 'status', 'seo_title', 'seo_description']);
            $attributes['noindex'] = (bool) ($data['noindex'] ?? false);
            $attributes['published_at'] = AdminDate::fromInput($data['published_at'] ?? null);

            if (($attributes['status'] ?? null) === 'published' && $attributes['published_at'] === null) {
                $attributes['published_at'] = now();
            }
            if ((! $isNew && $album->published_at !== null) || blank($attributes['slug'] ?? null)) {
                unset($attributes['slug']);
            }

            $album->fill($attributes);
            $dirty = array_keys($album->getDirty());
            $album->save();

            $artistChanges = array_filter($album->artists()->sync(
                collect($data['artist_ids'])->mapWithKeys(fn ($id) => [(int) $id => ['is_primary' => true]])->all()
            ));

            if ($isNew) {
                AuditLogger::record('album.created', $album, ['status' => $album->status->value]);
            } elseif ($dirty || $artistChanges) {
                AuditLogger::record('album.updated', $album, array_filter(['fields' => $dirty, 'artists_changed' => (bool) $artistChanges]));
            }
            if (! $isNew && $album->status->value !== $oldStatus) {
                AuditLogger::record('album.status_changed', $album, ['from' => $oldStatus, 'to' => $album->status->value]);
            }

            return $album;
        });
    }
}
