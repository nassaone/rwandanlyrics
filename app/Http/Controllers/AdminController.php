<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use App\Models\AuditLog;
use App\Models\Song;
use Illuminate\Contracts\View\View;

class AdminController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'published_songs' => Song::where('status', 'published')->count(),
            'live_songs' => Song::publiclyVisible()->count(),
            'draft_songs' => Song::where('status', 'draft')->count(),
            'pending_songs' => Song::where('status', 'pending_review')->count(),
            'artists' => Artist::count(),
            'albums' => Album::count(),
        ];

        $warnings = [
            'rights_blocked' => Song::where('status', 'published')->where('rights_status', '!=', 'authorized')->count(),
            'no_lyrics' => Song::where('status', 'published')->whereDoesntHave('currentLyric')->count(),
        ];

        $recent = AuditLog::with('actor')->latest('id')->limit(10)->get();

        return view('admin.index', compact('stats', 'warnings', 'recent'));
    }
}
