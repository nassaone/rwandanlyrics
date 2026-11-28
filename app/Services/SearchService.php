<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Lyric;
use App\Models\Song;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;

/**
 * LIKE-based search. Works on SQLite and MySQL. Every word must match (title or artist),
 * in any order. Lyrics are searched separately, as a phrase, so they never outrank titles.
 * Keep callers going through this class so the engine can be swapped later.
 */
class SearchService
{
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 100;

    public const LYRICS_MIN_LENGTH = 4;

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

    private static function like(string $token): string
    {
        return '%'.self::escape($token).'%';
    }

    private static function rank(string $column): string
    {
        return "CASE WHEN {$column} LIKE ? ESCAPE '!' THEN 0 WHEN {$column} LIKE ? ESCAPE '!' THEN 1 ELSE 2 END";
    }

    /** @return list<string> */
    private static function tokens(string $query): array
    {
        $words = array_values(array_filter(explode(' ', $query), fn ($word) => $word !== ''));

        return array_slice($words, 0, 6);
    }

    private function artistMatch(string $like): Closure
    {
        return function ($match) use ($like) {
            $match->whereRaw("artists.name LIKE ? ESCAPE '!'", [$like])
                ->orWhereHas('aliases', fn ($alias) => $alias->whereRaw("artist_aliases.alias LIKE ? ESCAPE '!'", [$like]));
        };
    }

    private function songsMatching(array $tokens): Closure
    {
        return function ($where) use ($tokens) {
            foreach ($tokens as $token) {
                $like = self::like($token);
                $where->where(function ($one) use ($like) {
                    $one->whereRaw("songs.title LIKE ? ESCAPE '!'", [$like])
                        ->orWhereHas('artists', fn ($artist) => $artist->where($this->artistMatch($like)));
                });
            }
        };
    }

    public function songs(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Song::publiclyVisible()
            ->with(['artists.image', 'cover', 'album.cover'])
            ->where($this->songsMatching(self::tokens($query)))
            ->orderByRaw(self::rank('songs.title'), [self::escape($query), self::escape($query).'%'])
            ->orderByDesc('songs.published_at')
            ->paginate($perPage);
    }

    public function artists(string $query, int $limit = 10): Collection
    {
        $tokens = self::tokens($query);

        return Artist::published()
            ->with('image')
            ->where(function ($where) use ($tokens) {
                foreach ($tokens as $token) {
                    $where->where($this->artistMatch(self::like($token)));
                }
            })
            ->orderByRaw(self::rank('artists.name'), [self::escape($query), self::escape($query).'%'])
            ->orderBy('artists.name')
            ->limit($limit)
            ->get();
    }

    public function albums(string $query, int $limit = 10): Collection
    {
        $tokens = self::tokens($query);

        return Album::published()
            ->with(['artists', 'cover'])
            ->where(function ($where) use ($tokens) {
                foreach ($tokens as $token) {
                    $like = self::like($token);
                    $where->where(function ($one) use ($like) {
                        $one->whereRaw("albums.title LIKE ? ESCAPE '!'", [$like])
                            ->orWhereHas('artists', fn ($artist) => $artist->whereRaw("artists.name LIKE ? ESCAPE '!'", [$like]));
                    });
                }
            })
            ->orderByRaw(self::rank('albums.title'), [self::escape($query), self::escape($query).'%'])
            ->orderBy('albums.title')
            ->limit($limit)
            ->get();
    }

    /**
     * Live songs whose current lyrics contain the whole phrase, each with the matching line
     * split into [before, match, after] so the page can highlight it.
     */
    public function lyricMatches(string $query, array $excludeIds = [], int $limit = 8): BaseCollection
    {
        if (mb_strlen($query) < self::LYRICS_MIN_LENGTH) {
            return collect();
        }

        $table = (new Lyric)->getTable();
        $like = self::like($query);

        $songs = Song::publiclyVisible()
            ->with(['artists', 'currentLyric'])
            ->whereNotIn('songs.id', $excludeIds)
            ->whereHas('currentLyric', fn ($lyric) => $lyric->whereRaw("{$table}.body LIKE ? ESCAPE '!'", [$like]))
            ->latest('songs.published_at')
            ->limit($limit)
            ->get();

        foreach ($songs as $song) {
            $song->setAttribute('snippet_parts', $this->snippet((string) $song->currentLyric?->body, $query));
        }

        return $songs;
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function snippet(string $body, string $query): array
    {
        foreach (preg_split('/\R/u', $body) ?: [] as $line) {
            $pos = mb_stripos($line, $query);
            if ($pos === false) {
                continue;
            }
            $before = mb_substr($line, 0, $pos);
            $match = mb_substr($line, $pos, mb_strlen($query));
            $after = mb_substr($line, $pos + mb_strlen($query));

            if (mb_strlen($before) > 40) {
                $before = '…'.mb_substr($before, -40);
            }
            if (mb_strlen($after) > 60) {
                $after = mb_substr($after, 0, 60).'…';
            }

            return [$before, $match, $after];
        }

        return [Str::limit(trim(preg_replace('/\s+/u', ' ', $body) ?? ''), 100), '', ''];
    }

    /** Small payload for the "suggestions while typing" dropdown. */
    public function quick(string $query): array
    {
        $tokens = self::tokens($query);

        $songs = Song::publiclyVisible()->with('artists')
            ->where($this->songsMatching($tokens))
            ->orderByRaw(self::rank('songs.title'), [self::escape($query), self::escape($query).'%'])
            ->orderByDesc('songs.published_at')
            ->limit(5)->get()
            ->map(fn ($song) => [
                'title' => $song->title,
                'artist' => $song->artists->pluck('name')->join(', '),
                'url' => route('songs.show', $song->slug),
            ])->values()->all();

        $artists = $this->artists($query, 3)->map(fn ($artist) => [
            'name' => $artist->name,
            'url' => route('artists.show', $artist->slug),
        ])->values()->all();

        return ['songs' => $songs, 'artists' => $artists];
    }

    /** The closest live title or artist name to a query that found nothing, or null. */
    public function suggest(string $query): ?string
    {
        $q = Str::lower(Str::ascii($query));
        $tokens = array_values(array_filter(explode(' ', $q)));
        if (! $tokens) {
            return null;
        }

        $names = Song::publiclyVisible()->limit(5000)->pluck('title')
            ->merge(Artist::published()->whereHas('songs', fn ($s) => $s->publiclyVisible())->limit(2000)->pluck('name'))
            ->merge(Album::published()->limit(2000)->pluck('title'))
            ->unique();

        $allowed = fn (int $length) => max(1, intdiv($length, 3));
        $best = null;
        $bestScore = PHP_INT_MAX;

        foreach ($names as $name) {
            $folded = Str::lower(Str::ascii((string) $name));
            if ($folded === '' || $folded === $q || strlen($folded) > 250 || strlen($q) > 250) {
                continue;
            }

            $score = null;
            $whole = levenshtein($q, $folded);
            if ($whole <= $allowed(strlen($q))) {
                $score = $whole;
            }

            $words = preg_split('/\s+/', $folded) ?: [];
            $sum = 0;
            $ok = true;
            foreach ($tokens as $token) {
                $closest = PHP_INT_MAX;
                foreach ($words as $word) {
                    $closest = min($closest, levenshtein($token, $word));
                }
                if ($closest > $allowed(strlen($token))) {
                    $ok = false;
                    break;
                }
                $sum += $closest;
            }
            if ($ok) {
                $score = $score === null ? $sum : min($score, $sum);
            }

            if ($score !== null && $score < $bestScore) {
                $best = (string) $name;
                $bestScore = $score;
            }
        }

        return $best;
    }
}
