<?php

namespace App\Services;

use App\Enums\PublicationStatus;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Song;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\YouTube;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/** Reads a CSV of artists/songs, shows what would happen, then saves everything as drafts. */
class CsvImporter
{
    public const MAX_ROWS = 500;

    private const ALIASES = [
        'artist' => ['artist', 'primary_artist', 'artist_name'],
        'title' => ['title', 'song', 'song_title'],
        'youtube_url' => ['youtube_url', 'youtube', 'video', 'video_url', 'link', 'url'],
        'album' => ['album', 'album_title'],
        'genre' => ['genre', 'genres'],
        'language' => ['language', 'lang'],
        'release_date' => ['release_date', 'released', 'date', 'year'],
        'featured_artists' => ['featured_artists', 'featured', 'feat', 'ft'],
        'lyrics' => ['lyrics', 'lyric'],
        'country' => ['country', 'country_code'],
    ];

    /** @return array{rows: list<array<string,string>>, ignored: list<string>} */
    public function parse(string $contents): array
    {
        $contents = (string) preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $firstLine = rtrim((string) strtok($contents, "\n"), "\r");

        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        $map = [];
        $ignored = [];
        $rows = [];
        $sawHeader = false;

        while (($cells = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            if (! $sawHeader) {
                $sawHeader = true;
                foreach ($cells as $i => $name) {
                    $key = Str::of((string) $name)->trim()->lower()->replace([' ', '-'], '_')->toString();
                    $canonical = null;
                    foreach (self::ALIASES as $canon => $aliases) {
                        if (in_array($key, $aliases, true)) {
                            $canonical = $canon;
                            break;
                        }
                    }
                    if ($canonical !== null && ! isset($map[$canonical])) {
                        $map[$canonical] = $i;
                    } elseif ($key !== '') {
                        $ignored[] = (string) $name;
                    }
                }
                if (! isset($map['artist'])) {
                    throw new InvalidArgumentException('The first row must be a header row with an "artist" column.');
                }

                continue;
            }

            $row = [];
            foreach ($map as $canon => $i) {
                $row[$canon] = trim((string) ($cells[$i] ?? ''));
            }
            $rows[] = $row;

            if (count($rows) > self::MAX_ROWS) {
                throw new InvalidArgumentException('The file has more than '.self::MAX_ROWS.' rows. Split it into smaller files.');
            }
        }
        fclose($handle);

        if (! $sawHeader) {
            throw new InvalidArgumentException('The file is empty.');
        }

        return ['rows' => $rows, 'ignored' => $ignored];
    }

    /** Works out what an import would do. Saves nothing. */
    public function plan(string $contents): array
    {
        ['rows' => $rows, 'ignored' => $ignored] = $this->parse($contents);

        $artistIds = Artist::query()->pluck('id', 'normalized_name')->all();
        $genreNames = Genre::query()->pluck('name')->map(fn ($n) => Str::lower($n))->flip()->all();
        $newArtists = [];
        $newGenres = [];
        $existingTitles = [];
        $seenSongs = [];
        $albumCache = [];
        $languageCache = [];
        $out = [];

        foreach ($rows as $i => $row) {
            $item = [
                'line' => $i + 2,
                'artist' => $row['artist'] ?? '',
                'title' => $row['title'] ?? '',
                'action' => 'song',
                'errors' => [],
                'warnings' => [],
                'notes' => [],
                'data' => [],
            ];

            if ($item['artist'] === '') {
                $item['action'] = 'error';
                $item['errors'][] = 'Artist is missing.';
                $out[] = $item;

                continue;
            }

            $norm = Artist::normalize($item['artist']);
            $artistExists = isset($artistIds[$norm]);
            $country = $row['country'] ?? '';

            if ($item['title'] === '') {
                if ($artistExists || isset($newArtists[$norm])) {
                    $item['action'] = 'skip';
                    $item['notes'][] = 'Artist already exists.';
                } else {
                    $item['action'] = 'artist';
                    $item['notes'][] = 'New artist.';
                    $item['data'] = ['country' => $country];
                    $newArtists[$norm] = $item['artist'];
                }
                $out[] = $item;

                continue;
            }

            $youtube = null;
            if (($row['youtube_url'] ?? '') !== '') {
                $youtube = YouTube::extractId($row['youtube_url']);
                if ($youtube === null) {
                    $item['errors'][] = 'The YouTube link is not valid.';
                }
            }

            $titleNorm = $this->normalizeTitle($item['title']);
            $duplicate = false;
            if ($artistExists) {
                $id = $artistIds[$norm];
                $existingTitles[$id] ??= Song::query()
                    ->whereHas('artists', fn ($q) => $q->where('artists.id', $id))
                    ->pluck('title')->map(fn ($t) => $this->normalizeTitle($t))->flip()->all();
                $duplicate = isset($existingTitles[$id][$titleNorm]);
            }
            $key = $norm.'|'.$titleNorm;
            if (isset($seenSongs[$key])) {
                $duplicate = true;
            }
            $seenSongs[$key] = true;

            if ($duplicate) {
                $item['action'] = 'skip';
                $item['notes'][] = 'This song already exists for this artist.';
                $out[] = $item;

                continue;
            }

            $date = null;
            if (($row['release_date'] ?? '') !== '') {
                $date = $this->parseDate($row['release_date']);
                if ($date === null) {
                    $item['warnings'][] = 'Release date not understood; left empty.';
                }
            }

            $albumId = null;
            if (($row['album'] ?? '') !== '') {
                $k = Str::lower($row['album']);
                $albumCache[$k] ??= Album::query()->whereRaw('lower(title) = ?', [$k])->pluck('id')->all();
                if (count($albumCache[$k]) === 1) {
                    $albumId = $albumCache[$k][0];
                } elseif (count($albumCache[$k]) === 0) {
                    $item['warnings'][] = 'Album "'.$row['album'].'" was not found; imported without an album.';
                } else {
                    $item['warnings'][] = 'More than one album is called "'.$row['album'].'"; imported without an album.';
                }
            }

            $languageId = null;
            if (($row['language'] ?? '') !== '') {
                $k = Str::lower($row['language']);
                $languageCache[$k] ??= Language::query()->whereRaw('lower(name) = ? or lower(code) = ?', [$k, $k])->value('id') ?? 0;
                if ($languageCache[$k]) {
                    $languageId = $languageCache[$k];
                } else {
                    $item['warnings'][] = 'Language "'.$row['language'].'" was not found; left empty.';
                }
            }

            $genres = $this->splitList($row['genre'] ?? '', true);
            $featured = $this->splitList($row['featured_artists'] ?? '', false);

            if ($item['errors']) {
                $item['action'] = 'error';
                $out[] = $item;

                continue;
            }

            if (! $artistExists && ! isset($newArtists[$norm])) {
                $newArtists[$norm] = $item['artist'];
                $item['notes'][] = 'New artist.';
            }
            foreach ($featured as $name) {
                $fn = Artist::normalize($name);
                if (! isset($artistIds[$fn]) && ! isset($newArtists[$fn])) {
                    $newArtists[$fn] = $name;
                    $item['notes'][] = 'New featured artist: '.$name.'.';
                }
            }
            foreach ($genres as $g) {
                $gl = Str::lower($g);
                if (! isset($genreNames[$gl]) && ! isset($newGenres[$gl])) {
                    $newGenres[$gl] = $g;
                    $item['notes'][] = 'New genre: '.$g.'.';
                }
            }

            $item['data'] = [
                'youtube_id' => $youtube,
                'album_id' => $albumId,
                'language_id' => $languageId,
                'release_date' => $date,
                'featured' => $featured,
                'genres' => $genres,
                'lyrics' => $row['lyrics'] ?? '',
                'country' => $country,
            ];
            $out[] = $item;
        }

        $count = fn (string $action) => count(array_filter($out, fn ($r) => $r['action'] === $action));

        return [
            'rows' => $out,
            'ignored' => $ignored,
            'summary' => [
                'rows' => count($out),
                'songs' => $count('song'),
                'artist_rows' => $count('artist'),
                'new_artists' => count($newArtists),
                'new_genres' => count($newGenres),
                'skipped' => $count('skip'),
                'errors' => $count('error'),
                'warnings' => array_sum(array_map(fn ($r) => count($r['warnings']), $out)),
            ],
        ];
    }

    /** Saves the plan as drafts, all or nothing. */
    public function execute(string $contents, User $actor): array
    {
        $plan = $this->plan($contents);
        $created = ['artists' => 0, 'songs' => 0, 'genres' => 0];
        $songs = app(SongService::class);

        DB::transaction(function () use ($plan, $actor, &$created, $songs) {
            foreach ($plan['rows'] as $row) {
                if ($row['action'] === 'artist') {
                    $this->artistId($row['artist'], $row['data']['country'] ?? null, $actor, $created);

                    continue;
                }
                if ($row['action'] !== 'song') {
                    continue;
                }

                $d = $row['data'];
                $primary = $this->artistId($row['artist'], $d['country'] ?? null, $actor, $created);
                $featuredIds = [];
                foreach ($d['featured'] as $name) {
                    $featuredIds[] = $this->artistId($name, null, $actor, $created);
                }
                $genreIds = [];
                foreach ($d['genres'] as $name) {
                    $genreIds[] = $this->genreId($name, $created);
                }

                $songs->save(new Song, [
                    'title' => $row['title'],
                    'primary_artist_id' => $primary,
                    'featured_artist_ids' => $featuredIds,
                    'genre_ids' => $genreIds,
                    'album_id' => $d['album_id'],
                    'language_id' => $d['language_id'],
                    'release_date' => $d['release_date'],
                    'youtube_url' => $d['youtube_id'] ? YouTube::watchUrl($d['youtube_id']) : null,
                    'lyrics' => $d['lyrics'] !== '' ? $d['lyrics'] : null,
                    'status' => 'draft',
                    'verification_status' => 'unverified',
                    'rights_status' => 'unknown',
                ], $actor);
                $created['songs']++;
            }

            AuditLogger::record('import.committed', $actor, $created + ['rows' => count($plan['rows'])]);
        });

        return $created + [
            'skipped' => $plan['summary']['skipped'],
            'errors' => $plan['summary']['errors'],
        ];
    }

    private function artistId(string $name, ?string $country, User $actor, array &$created): int
    {
        $id = Artist::query()->where('normalized_name', Artist::normalize($name))->value('id');
        if ($id) {
            return (int) $id;
        }

        $attributes = ['name' => $name, 'status' => PublicationStatus::Draft, 'verification_status' => 'unverified'];
        if ($country !== null && preg_match('/^[A-Za-z]{2}$/', $country)) {
            $attributes['country_code'] = strtoupper($country);
        }

        $artist = Artist::create($attributes);
        AuditLogger::record('artist.created', $artist, ['source' => 'import'], $actor);
        $created['artists']++;

        return (int) $artist->id;
    }

    private function genreId(string $name, array &$created): int
    {
        $id = Genre::query()->whereRaw('lower(name) = ?', [Str::lower($name)])->value('id');
        if ($id) {
            return (int) $id;
        }
        $created['genres']++;

        return (int) Genre::create(['name' => $name])->id;
    }

    private function normalizeTitle(string $title): string
    {
        $n = Str::of(Str::ascii($title))->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString();

        return $n !== '' ? $n : mb_strtolower((string) preg_replace('/\s+/u', '', $title));
    }

    private function parseDate(string $value): ?string
    {
        if (preg_match('/^\d{4}$/', $value)) {
            return $value.'-01-01';
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /** @return list<string> */
    private function splitList(string $value, bool $commaToo): array
    {
        $parts = preg_split($commaToo ? '/[;|,]/' : '/[;|]/', $value) ?: [];

        return array_values(array_unique(array_filter(array_map('trim', $parts), fn ($p) => $p !== '')));
    }
}
