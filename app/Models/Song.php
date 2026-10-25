<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\RightsStatus;
use App\Enums\VerificationStatus;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Song extends Model
{
    use HasFactory, HasUniqueSlug;

    protected $fillable = [
        'title', 'slug', 'album_id', 'track_number', 'language_id', 'release_date',
        'description', 'youtube_id', 'external_links', 'status', 'published_at', 'verification_status',
        'rights_status', 'rights_holder', 'rights_notes', 'rights_source',
        'seo_title', 'seo_description', 'canonical_url', 'noindex',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'external_links' => 'array',
            'status' => PublicationStatus::class,
            'verification_status' => VerificationStatus::class,
            'rights_status' => RightsStatus::class,
            'release_date' => 'date',
            'published_at' => 'datetime',
            'noindex' => 'boolean',
        ];
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    /**
     * The single rule for what the public may see: published, already live, and rights-authorized.
     * Use this in every public page, search, sitemap and future API.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('songs.status', PublicationStatus::Published->value)
            ->where('songs.published_at', '<=', now())
            ->where('songs.rights_status', RightsStatus::Authorized->value);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'song_artist')
            ->withPivot('role', 'position')
            ->orderByPivot('position');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function lyrics(): HasMany
    {
        return $this->hasMany(Lyric::class);
    }

    public function currentLyric(): HasOne
    {
        return $this->hasOne(Lyric::class)->where('is_current', true);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_id');
    }
}
