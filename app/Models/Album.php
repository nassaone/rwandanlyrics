<?php

namespace App\Models;

use App\Enums\AlbumType;
use App\Enums\PublicationStatus;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    use HasFactory, HasUniqueSlug;

    protected $fillable = [
        'title', 'slug', 'type', 'release_date', 'description', 'status',
        'published_at', 'seo_title', 'seo_description', 'noindex',
    ];

    protected function casts(): array
    {
        return [
            'type' => AlbumType::class,
            'status' => PublicationStatus::class,
            'release_date' => 'date',
            'published_at' => 'datetime',
            'noindex' => 'boolean',
        ];
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published->value)
            ->where('published_at', '<=', now());
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'album_artist')->withPivot('is_primary');
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class)->orderBy('track_number');
    }
}
