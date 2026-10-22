<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function __construct(private SubmissionService $service) {}

    public function index(Request $request): View
    {
        Gate::authorize('review', new Submission);

        $status = SubmissionStatus::tryFrom((string) $request->query('status', 'pending')) ?? SubmissionStatus::Pending;
        $submissions = Submission::with('user')->where('status', $status->value)
            ->orderBy('created_at', $status === SubmissionStatus::Pending ? 'asc' : 'desc')
            ->paginate(20)->withQueryString();
        $counts = Submission::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.submissions.index', compact('submissions', 'status', 'counts'));
    }

    public function show(Submission $submission): View
    {
        Gate::authorize('review', $submission);
        $submission->load(['user', 'song.currentLyric', 'language', 'reviewer', 'resultSong']);

        return view('admin.submissions.show', [
            'submission' => $submission,
            'artists' => Artist::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function approve(Request $request, Submission $submission): RedirectResponse
    {
        Gate::authorize('review', $submission);

        if (! $submission->isPending()) {
            return back()->withErrors(['review' => 'This submission has already been reviewed.']);
        }

        $isNew = $submission->type === SubmissionType::NewSong;
        $data = $request->validate([
            'artist_id' => [$isNew ? 'required' : 'nullable', 'integer', 'exists:artists,id'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $song = $this->service->approve($submission, $request->user(), $data['artist_id'] ?? null, $data['review_notes'] ?? null);

        return $song
            ? redirect()->route('admin.songs.edit', $song)->with('status', 'Approved. Review the song, set its rights status, then publish.')
            : redirect()->route('admin.submissions.index')->with('status', 'Approved.');
    }

    public function reject(Request $request, Submission $submission): RedirectResponse
    {
        Gate::authorize('review', $submission);

        if (! $submission->isPending()) {
            return back()->withErrors(['review' => 'This submission has already been reviewed.']);
        }

        $data = $request->validate(['review_notes' => ['required', 'string', 'min:5', 'max:2000']]);
        $this->service->reject($submission, $request->user(), $data['review_notes']);

        return redirect()->route('admin.submissions.index')->with('status', 'Submission declined.');
    }
}
