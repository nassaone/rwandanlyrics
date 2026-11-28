<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Services\SocialCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SocialCardController extends Controller
{
    public function song(string $slug): BinaryFileResponse|RedirectResponse
    {
        $song = Song::publiclyVisible()
            ->where('songs.slug', $slug)
            ->with(['artists', 'cover', 'album.cover'])
            ->firstOrFail();

        $fallback = redirect(asset('og-default.png'));

        if (! SocialCard::available()) {
            return $fallback;
        }

        $media = $song->cover ?? $song->album?->cover;
        $artists = $song->artists->pluck('name')->join(', ');
        $hash = md5(implode('|', [$song->title, $artists, $media?->path, (string) $media?->updated_at, 'v1']));
        $dir = storage_path('app/social-cards');
        $file = "{$dir}/song-{$song->id}-{$hash}.png";

        if (! is_file($file)) {
            try {
                File::ensureDirectoryExists($dir);

                try {
                    $bytes = $media ? Storage::disk($media->disk)->get($media->path) : null;
                } catch (\Throwable) {
                    $bytes = null;
                }

                $png = SocialCard::png($song->title, $artists, $bytes, 'Lyrics', (string) (parse_url(config('app.url'), PHP_URL_HOST) ?: 'vocalvibesnation.online'));

                foreach (glob("{$dir}/song-{$song->id}-*.png") ?: [] as $old) {
                    @unlink($old);
                }
                file_put_contents($file, $png);
            } catch (\Throwable $e) {
                report($e);

                return $fallback;
            }
        }

        return response()->file($file, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }
}
