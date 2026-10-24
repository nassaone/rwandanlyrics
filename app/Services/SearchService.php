<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * LIKE-based search. Works on SQLite and MySQL. MySQL FULLTEXT is deferred until
 * it can be tested; keep callers going through this class so it can be swapped.
 * Lyrics text is intentionally not searched here.
 */
class SearchService
{
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 100;

    public static function normalize(?string $query): string
    {
        $query = preg_replace('/\s+/u', ' ', trim((string) $query)) ?? '';

        return mb_substr($query, 0, self::MAX_LENGTH);
    }

    public static function isSearchable(string $query): bool
    {
        return mb_strlen($query) >= self::MIN_LENGTH;
    }

    /** Escape LIKE wildcards using "!" as the escape character (portable across SQLite and MySQL). */
    private static function escape(string $text): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text);
    }

    private static function rank(string $column): string
    {
        return "CASE WHEN {$column} LIKE ? ESCAPE '!' THEN 0 WHEN {$column} LIKE ? ESCAPE '!' THEN 1 ELSE 2 END";
    }

    public function songs(string $query, int $perPage = 15): LengthAwarePaginator
    {
        $contains = '%'.self::escape($query).'%';

        return Song::publiclyVisible()
            ->with(['artists.image', 'cover', 'album.cover'])
            ->where(function ($where) use ($contains) {
                $where->whereRaw("songs.title LIKE ? ESCAPE '!'", [$contains])
                    ->orWhereHas('artists', function ($artist) use ($contains) {
                        $artist->where(function ($match) use ($contains) {
                            $match->whereRaw("artists.name LIKE ? ESCAPE '!'", [$contains])
                                ->orWhereHas('aliases', fn ($alias) => $alias->whereRaw("artist_aliases.alias LIKE ? ESCAPE '!'", [$contains]));
                        });
                    });
            })
            ->orderByRaw(self::rank('songs.title'), [self::escape($query), self::escape($query).'%'])
            ->orderByDesc('songs.published_at')
            ->paginate($perPage);
    }

    public function artists(string $query, int $limit = 10): Collection
    {
        $contains = '%'.self::escape($query).'%';

        return Artist::published()
            ->with('image')
            ->where(function ($where) use ($contains) {
                $where->whereRaw("artists.name LIKE ? ESCAPE '!'", [$contains])
                    ->orWhereHas('aliases', fn ($alias) => $alias->whereRaw("artist_aliases.alias LIKE ? ESCAPE '!'", [$contains]));
            })
            ->orderByRaw(self::rank('artists.name'), [self::escape($query), self::escape($query).'%'])
            ->orderBy('artists.name')
            ->limit($limit)
            ->get();
    }

    public function albums(string $query, int $limit = 10): Collection
    {
        $contains = '%'.self::escape($query).'%';

        return Album::published()
            ->with(['artists', 'cover'])
            ->where(function ($where) use ($contains) {
                $where->whereRaw("albums.title LIKE ? ESCAPE '!'", [$contains])
                    ->orWhereHas('artists', fn ($artist) => $artist->whereRaw("artists.name LIKE ? ESCAPE '!'", [$contains]));
            })
            ->orderByRaw(self::rank('albums.title'), [self::escape($query), self::escape($query).'%'])
            ->orderBy('albums.title')
            ->limit($limit)
            ->get();
    }
}
