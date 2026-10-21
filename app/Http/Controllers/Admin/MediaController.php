<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MediaController extends Controller
{
    public function __construct(private MediaService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Media::class);

        $media = Media::latest('id')->paginate(30);

        $used = collect([
            DB::table('songs')->whereNotNull('cover_id')->pluck('cover_id'),
            DB::table('albums')->whereNotNull('cover_id')->pluck('cover_id'),
            DB::table('artists')->whereNotNull('image_id')->pluck('image_id'),
            DB::table('artists')->whereNotNull('banner_id')->pluck('banner_id'),
        ])->flatten()->flip();

        return view('admin.media.index', compact('media', 'used'));
    }

    public function destroy(Media $media): RedirectResponse
    {
        Gate::authorize('delete', $media);

        AuditLogger::record('media.deleted', $media, ['original_name' => $media->original_name]);
        $this->service->delete($media);

        return redirect()->route('admin.media.index')->with('status', 'Image deleted. Pages that used it no longer show it.');
    }
}
