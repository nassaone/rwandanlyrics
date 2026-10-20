<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\VerificationStatus;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Artist extends Model
{
    use HasFactory, HasUniqueSlug;

    protected $fillable = [
        'name', 'slug', 'bio', 'country_code', 'website', 'social_links',
        'status', 'verification_status', 'published_at',
        'seo_title', 'seo_description', 'noindex',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'status' => PublicationStatus::class,
            'verification_status' => VerificationStatus::class,
            'published_at' => 'datetime',
            'noindex' => 'boolean',
        ];
    }

    protected function slugSource(): string
    {
        return 'name';
    }

    protected static function booted(): void
    {
        static::saving(function (Artist $artist) {
            $artist->normalized_name = static::normalize($artist->name);
        });
    }

    public static function normalize(string $value): string
    {
        $normalized = Str::of(Str::ascii($value))->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString();

        return $normalized !== '' ? $normalized : mb_strtolower((string) preg_replace('/\s+/u', '', $value));
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published->value)
            ->where('published_at', '<=', now());
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ArtistAlias::class);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'genre_artist');
    }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class, 'song_artist')->withPivot('role', 'position');
    }

    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'album_artist')->withPivot('is_primary');
    }
}
