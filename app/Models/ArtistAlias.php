<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtistAlias extends Model
{
    protected $fillable = ['artist_id', 'alias', 'approved'];

    protected function casts(): array
    {
        return ['approved' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (ArtistAlias $alias) {
            $alias->normalized_alias = Artist::normalize($alias->alias);
        });
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }
}
