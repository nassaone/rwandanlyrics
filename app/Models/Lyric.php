<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lyric extends Model
{
    use HasFactory;

    protected $fillable = ['song_id', 'body', 'revision_number', 'is_current', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['is_current' => 'boolean'];
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
