<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['disk', 'path', 'original_name', 'mime', 'size', 'width', 'height', 'alt_text', 'uploaded_by'];

    public function url(): string
    {
        // The local public disk is served through the app's own (possibly proxied) host.
        if ($this->disk === 'public' && config('filesystems.disks.public.driver') === 'local') {
            return asset('storage/'.$this->path);
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
