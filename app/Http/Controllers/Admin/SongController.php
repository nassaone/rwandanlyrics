<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SongRequest;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Song;
use App\Services\SongService;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SongController extends Controller
{
    public function __construct(private SongService $songs) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Song::class);

        $q = trim((string) $request->query('q'));
        $filter = $request->query('filter');

        $songs = Song::query()->with('artists')
            ->when($q !== '', fn ($query) => $query->where('title', 'like', '%'.$q.'%'))
            ->when(PublicationStatus::tryFrom((string) $request->query('status')), fn ($query, $status) => $query->where('status', $status->value))
            ->when($filter === 'rights_blocked', fn ($query) => $query->where('status', 'published')->where('rights_status', '!=', 'authorized'))
            ->when($filter === 'no_lyrics', fn ($query) => $query->where('status', 'published')->whereDoesntHave('currentLyric'))
            ->latest('updated_at')->latest('id')
            ->paginate(25)->withQueryString();

        return view('admin.songs.index', compact('songs'));
    }

    public function create(): View
    {
        Gate::authorize('create', Song::class);

        return view('admin.songs.create', $this->formData() + ['song' => new Song]);
    }

    public function store(SongRequest $request): RedirectResponse
    {
        Gate::authorize('create', Song::class);

        $song = $this->songs->save(new Song, $request->validated(), $request->user());

        return redirect()->route('admin.songs.edit', $song)->with('status', 'Song created.');
    }

    public function edit(Song $song): View
    {
        Gate::authorize('update', $song);

        $song->load(['artists', 'genres', 'currentLyric']);

        return view('admin.songs.edit', $this->formData() + ['song' => $song]);
    }

    public function update(SongRequest $request, Song $song): RedirectResponse
    {
        Gate::authorize('update', $song);

        $this->songs->save($song, $request->validated(), $request->user());

        return redirect()->route('admin.songs.edit', $song)->with('status', 'Song saved.');
    }

    public function destroy(Song $song): RedirectResponse
    {
        Gate::authorize('delete', $song);

        AuditLogger::record('song.deleted', $song);
        $song->delete();

        return redirect()->route('admin.songs.index')->with('status', 'Song deleted.');
    }

    private function formData(): array
    {
        return [
            'artists' => Artist::orderBy('name')->pluck('name', 'id'),
            'albums' => Album::orderBy('title')->pluck('title', 'id'),
            'languages' => Language::orderBy('name')->pluck('name', 'id'),
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
        ];
    }
}
