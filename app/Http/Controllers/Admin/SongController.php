<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublicationStatus;
use App\Enums\Role;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SongController extends Controller
{
    public function __construct(private SongService $songs) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Song::class);

        $songs = $this->listQuery($request)->with('artists')
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

    public function bulk(Request $request): RedirectResponse
    {
        Gate::authorize('viewAny', Song::class);
        abort_unless($request->user()->atLeast(Role::Admin), 403);

        $data = $request->validate([
            'action' => ['required', 'in:publish,unpublish,authorize'],
            'scope' => ['nullable', 'in:page,all'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
        ]);

        $all = ($data['scope'] ?? 'page') === 'all';
        if (! $all && empty($data['ids'])) {
            return back()->with('status', 'Tick at least one song first.');
        }

        $query = $all ? $this->listQuery($request) : Song::query()->whereIn('id', $data['ids']);
        $songs = $query->orderBy('id')->take(1000)->get();

        $done = 0;
        $skippedDenied = 0;

        foreach ($songs as $song) {
            if (! Gate::allows('update', $song)) {
                $skippedDenied++;

                continue;
            }

            if ($data['action'] === 'publish') {
                $song->update([
                    'status' => 'published',
                    'rights_status' => 'authorized',
                    'published_at' => ($song->published_at && $song->published_at->lte(now())) ? $song->published_at : now(),
                ]);
            } elseif ($data['action'] === 'unpublish') {
                $song->update(['status' => 'draft']);
            } else {
                $song->update(['rights_status' => 'authorized']);
            }

            AuditLogger::record('song.bulk_'.$data['action'], $song, ['bulk' => true]);
            $done++;
        }

        $label = ['publish' => 'Published', 'unpublish' => 'Moved to draft:', 'authorize' => 'Authorized rights for'][$data['action']];
        $message = "{$label} {$done} song".($done === 1 ? '' : 's').'.';
        if ($skippedDenied) {
            $message .= " Skipped {$skippedDenied} you cannot edit.";
        }
        if ($all && $songs->count() === 1000) {
            $message .= ' Limit of 1000 per run reached, run it again for the rest.';
        }

        return back()->with('status', $message);
    }

    private function listQuery(Request $request): Builder
    {
        $q = trim((string) $request->input('q'));
        $filter = $request->input('filter');

        return Song::query()
            ->when($q !== '', fn ($query) => $query->where('title', 'like', '%'.$q.'%'))
            ->when(PublicationStatus::tryFrom((string) $request->input('status')), fn ($query, $status) => $query->where('status', $status->value))
            ->when($filter === 'rights_blocked', fn ($query) => $query->where('status', 'published')->where('rights_status', '!=', 'authorized'))
            ->when($filter === 'no_lyrics', fn ($query) => $query->where('status', 'published')->whereDoesntHave('currentLyric'));
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
