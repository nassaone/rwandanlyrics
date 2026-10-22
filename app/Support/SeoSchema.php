<?php

namespace App\Support;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Support\Str;

/** Builds structured data (JSON-LD) and meta descriptions from real fields only. Never includes lyric text. */
class SeoSchema
{
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public static function song(Song $song): array
    {
        $album = $song->relationLoaded('album') ? $song->album : null;
        $language = $song->relationLoaded('language') ? $song->language : null;

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'MusicRecording',
            'name' => $song->title,
            'url' => route('songs.show', $song->slug),
            'byArtist' => $song->artists->map(fn ($a) => ['@type' => 'MusicGroup', 'name' => $a->name, 'url' => route('artists.show', $a->slug)])->values()->all(),
            'inAlbum' => $album ? ['@type' => 'MusicAlbum', 'name' => $album->title, 'url' => route('albums.show', $album->slug)] : null,
            'datePublished' => $song->release_date?->toDateString(),
            'inLanguage' => $language?->code,
        ]);
    }

    public static function artist(Artist $artist): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'MusicGroup',
            'name' => $artist->name,
            'url' => route('artists.show', $artist->slug),
            'image' => $artist->relationLoaded('image') ? $artist->image?->url() : null,
            'sameAs' => $artist->website ? [$artist->website] : null,
        ]);
    }

    public static function album(Album $album): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'MusicAlbum',
            'name' => $album->title,
            'url' => route('albums.show', $album->slug),
            'byArtist' => $album->artists->map(fn ($a) => ['@type' => 'MusicGroup', 'name' => $a->name, 'url' => route('artists.show', $a->slug)])->values()->all(),
            'datePublished' => $album->release_date?->toDateString(),
            'image' => $album->relationLoaded('cover') ? $album->cover?->url() : null,
        ]);
    }

    /** Editor-written meta description wins; otherwise a plain sentence built from real fields. */
    public static function description(Song|Artist|Album $model): string
    {
        if (filled($model->seo_description)) {
            return Str::limit(trim($model->seo_description), 300, '');
        }

        if ($model instanceof Song) {
            $by = $model->artists->pluck('name')->join(', ');

            return Str::limit('Lyrics of '.$model->title.($by !== '' ? ' by '.$by : '').' on '.config('app.name').'.', 160, '');
        }

        if ($model instanceof Artist) {
            if (filled($model->bio)) {
                return Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($model->bio))), 160);
            }

            return Str::limit($model->name.': songs, albums and lyrics on '.config('app.name').'.', 160, '');
        }

        $by = $model->artists->pluck('name')->join(', ');

        return Str::limit($model->title.($by !== '' ? ' by '.$by : '').': tracklist and lyrics on '.config('app.name').'.', 160, '');
    }

    private static function clean(array $data): array
    {
        return array_filter($data, fn ($v) => $v !== null && $v !== [] && $v !== '');
    }
}
