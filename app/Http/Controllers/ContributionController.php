<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Language;
use App\Models\Song;
use App\Models\Submission;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContributionController extends Controller
{
    public const MAX_PENDING = 10;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Submission::class);

        $submissions = Submission::where('user_id', $request->user()->id)->latest()->paginate(20);

        return view('contribute.index', compact('submissions'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Submission::class);

        $song = null;
        if ($slug = $request->query('song')) {
            $song = Song::publiclyVisible()->with(['artists', 'currentLyric'])->where('slug', $slug)->firstOrFail();
        }

        return view('contribute.create', [
            'song' => $song,
            'languages' => Language::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(StoreSubmissionRequest $request): RedirectResponse
    {
        Gate::authorize('create', Submission::class);
        $user = $request->user();

        $pending = Submission::where('user_id', $user->id)->where('status', SubmissionStatus::Pending->value)->count();
        if ($pending >= self::MAX_PENDING) {
            return back()->withInput()->withErrors(['title' => 'You already have '.self::MAX_PENDING.' submissions waiting for review. Please wait for them to be reviewed.']);
        }

        $song = $request->filled('song')
            ? Song::publiclyVisible()->where('slug', $request->input('song'))->firstOrFail()
            : null;

        $submission = new Submission($request->safe()->except('song'));
        $submission->forceFill([
            'user_id' => $user->id,
            'type' => $song ? SubmissionType::Correction : SubmissionType::NewSong,
            'song_id' => $song?->id,
            'status' => SubmissionStatus::Pending,
        ])->save();

        AuditLogger::record('submission.created', $submission);

        return redirect()->route('contribute.show', $submission)
            ->with('status', 'Thank you. Your submission is waiting for an editor to review it.');
    }

    public function show(Submission $submission): View
    {
        Gate::authorize('view', $submission);

        return view('contribute.show', compact('submission'));
    }
}
