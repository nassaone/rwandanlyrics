<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaService
{
    /** Allowed real image types (detected from file contents) and the extension we store them under. */
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function disk(): string
    {
        return config('rwandanlyrics.media_disk', 'public');
    }

    public function store(UploadedFile $file, string $alt, User $actor): Media
    {
        // Trust the bytes, never the client's file name or declared type.
        $info = @getimagesize($file->getRealPath());
        $extension = $info ? (self::TYPES[$info['mime']] ?? null) : null;

        if ($extension === null) {
            throw ValidationException::withMessages(['file' => 'The file is not a supported image (JPG, PNG or WebP).']);
        }

        $disk = $this->disk();
        $path = Storage::disk($disk)->putFileAs('media/'.now()->format('Y/m'), $file, Str::random(40).'.'.$extension);

        return Media::create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => Str::limit(preg_replace('/[\x00-\x1F\x7F]/', '', basename($file->getClientOriginalName())), 120, ''),
            'mime' => $info['mime'],
            'size' => $file->getSize(),
            'width' => $info[0],
            'height' => $info[1],
            'alt_text' => $alt,
            'uploaded_by' => $actor->id,
        ]);
    }

    /**
     * Applies an upload / removal / alt-text edit to a media column on $model (not yet saved).
     * Returns true when something changed.
     */
    public function apply(Model $model, string $column, ?UploadedFile $file, ?string $alt, bool $remove, User $actor): bool
    {
        $current = $model->{$column} ? Media::find($model->{$column}) : null;

        if ($file) {
            $model->forceFill([$column => $this->store($file, (string) $alt, $actor)->id]);

            return true;
        }

        if ($remove && $current) {
            $model->forceFill([$column => null]);

            return true;
        }

        if ($current && filled($alt) && $alt !== $current->alt_text) {
            $current->update(['alt_text' => $alt]);

            return true;
        }

        return false;
    }

    public function delete(Media $media): void
    {
        DB::transaction(function () use ($media) {
            DB::table('songs')->where('cover_id', $media->id)->update(['cover_id' => null]);
            DB::table('albums')->where('cover_id', $media->id)->update(['cover_id' => null]);
            DB::table('artists')->where('image_id', $media->id)->update(['image_id' => null]);
            DB::table('artists')->where('banner_id', $media->id)->update(['banner_id' => null]);

            $media->delete();
        });

        Storage::disk($media->disk)->delete($media->path);
    }
}
