<?php

namespace App\Services;

use App\Models\Song;
use App\Models\User;
use App\Support\AdminDate;
use App\Support\AuditLogger;
use App\Support\Redirects;
use App\Support\YouTube;
use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SongService
{
    private const FIELDS = [
        'title', 'slug', 'album_id', 'track_number', 'language_id', 'release_date', 'description',
        'status', 'verification_status', 'rights_status', 'rights_holder', 'rights_notes', 'rights_source',
        'seo_title', 'seo_description', 'canonical_url',
    ];

    public function __construct(private MediaService $media) {}

    public function save(Song $song, array $data, User $actor): Song
    {
        return DB::transaction(function () use ($song, $data, $actor) {
            $isNew = ! $song->exists;
            $oldStatus = $this->value($song->status);
            $oldRights = $this->value($song->rights_status);
            $oldSlug = $song->slug;
            $hadPublicUrl = ! $isNew && $song->published_at !== null;

            $attributes = Arr::only($data, self::FIELDS);
            $attributes['noindex'] = (bool) ($data['noindex'] ?? false);
            if (array_key_exists('youtube_url', $data)) {
                $attributes['youtube_id'] = YouTube::extractId($data['youtube_url']);
            }
            $attributes['published_at'] = AdminDate::fromInput($data['published_at'] ?? null);
            $attributes['updated_by'] = $actor->id;

            if (($attributes['status'] ?? null) === 'published' && $attributes['published_at'] === null) {
                $attributes['published_at'] = now();
            }
            if (blank($attributes['slug'] ?? null)) {
                unset($attributes['slug']); // blank = keep the current slug / generate one
            }
            if ($isNew) {
                $attributes['created_by'] = $actor->id;
            }

            $this->media->apply($song, 'cover_id', $data['cover'] ?? null, $data['cover_alt'] ?? null, (bool) ($data['remove_cover'] ?? false), $actor);

            $song->fill($attributes);
            $dirty = array_values(array_diff(array_keys($song->getDirty()), ['updated_by']));
            $song->save();

            $primaryId = (int) $data['primary_artist_id'];
            $featuredIds = array_values(array_unique(array_diff(array_map('intval', $data['featured_artist_ids'] ?? []), [$primaryId])));
            $pivot = [$primaryId => ['role' => 'primary', 'position' => 0]];
            foreach ($featuredIds as $i => $id) {
                $pivot[$id] = ['role' => 'featured', 'position' => $i + 1];
            }
            $artistChanges = array_filter($song->artists()->sync($pivot));
            $genreChanges = array_filter($song->genres()->sync($data['genre_ids'] ?? []));

            $lyricsRevision = $this->saveLyrics($song, $data['lyrics'] ?? null, $actor);

            $changed = $isNew || $dirty || $artistChanges || $genreChanges || $lyricsRevision !== null;
            if ($isNew) {
                AuditLogger::record('song.created', $song, ['status' => $this->value($song->status)], $actor);
            } elseif ($changed) {
                AuditLogger::record('song.updated', $song, array_filter([
                    'fields' => $dirty,
                    'artists_changed' => (bool) $artistChanges,
                    'genres_changed' => (bool) $genreChanges,
                    'lyrics_revision' => $lyricsRevision,
                ]), $actor);
            }

            $newStatus = $this->value($song->status);
            if (! $isNew && $newStatus !== $oldStatus) {
                AuditLogger::record('song.status_changed', $song, ['from' => $oldStatus, 'to' => $newStatus], $actor);
            }
            $newRights = $this->value($song->rights_status);
            if (! $isNew && $newRights !== $oldRights) {
                AuditLogger::record('song.rights_changed', $song, ['from' => $oldRights, 'to' => $newRights], $actor);
            }

            if ($hadPublicUrl && $song->slug !== $oldSlug) {
                Redirects::record(route('songs.show', $oldSlug, false), route('songs.show', $song->slug, false), $actor);
            }

            return $song;
        });
    }

    /** Returns the new revision number, 0 when lyrics were removed, or null when nothing changed. */
    private function saveLyrics(Song $song, ?string $input, User $actor): ?int
    {
        $body = filled($input) ? rtrim(str_replace(["\r\n", "\r"], "\n", $input)) : null;
        $current = $song->lyrics()->where('is_current', true)->first();

        if ($body === ($current?->body)) {
            return null;
        }

        $current?->update(['is_current' => false]);

        if ($body === null) {
            return 0;
        }

        $revision = ((int) $song->lyrics()->max('revision_number')) + 1;
        $song->lyrics()->create([
            'body' => $body,
            'revision_number' => $revision,
            'is_current' => true,
            'created_by' => $actor->id,
        ]);

        return $revision;
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
