<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    /** Only contributor-supplied fields are mass assignable; status, type and review fields are set in code. */
    protected $fillable = [
        'title', 'artist_name', 'album_title', 'language_id', 'lyrics', 'notes',
        'rights_holder', 'rights_source', 'rights_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'type' => SubmissionType::class,
            'status' => SubmissionStatus::class,
            'rights_confirmed' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function resultSong(): BelongsTo
    {
        return $this->belongsTo(Song::class, 'result_song_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function isPending(): bool
    {
        return $this->status === SubmissionStatus::Pending;
    }
}
