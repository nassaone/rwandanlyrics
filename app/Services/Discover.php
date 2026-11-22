<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use Illuminate\Support\Collection;

/**
 * Live-only lists for the "Keep exploring" block: trending first, newest to fill the gaps.
 * Deliberately not cached, so a takedown disappears from every page immediately.
 */
class Discover
{
    public function songs(int $limit = 6): Collection
    {
        $ids = app(ViewStats::class)->trendingIds('song', null, $limit);

        $trending = $ids
            ? Song::publiclyVisible()->with(['artists.image', 'cover', 'album.cover'])->whereIn('songs.id', $ids)->get()
                ->sortBy(fn ($s) => array_search($s->id, $ids))->values()
            : collect();

        if ($trending->count() < $limit) {
            $fill = Song::publiclyVisible()->with(['artists.image', 'cover', 'album.cover'])
                ->whereNotIn('songs.id', $trending->pluck('id'))
                ->latest('songs.published_at')->limit($limit - $trending->count())->get();
            $trending = $trending->concat($fill);
        }

        return $trending->take($limit)->values();
    }

    public function artists(int $limit = 8): Collection
    {
        $live = fn () => Artist::published()->with('image')->whereHas('songs', fn ($q) => $q->publiclyVisible());
        $ids = app(ViewStats::class)->trendingIds('artist', null, $limit);

        $popular = $ids
            ? $live()->whereIn('artists.id', $ids)->get()->sortBy(fn ($a) => array_search($a->id, $ids))->values()
            : collect();

        if ($popular->count() < $limit) {
            $fill = $live()->whereNotIn('artists.id', $popular->pluck('id'))
                ->latest('artists.published_at')->limit($limit - $popular->count())->get();
            $popular = $popular->concat($fill);
        }

        return $popular->take($limit)->values();
    }

    public function genres(int $limit = 12): Collection
    {
        return Genre::whereHas('songs', fn ($q) => $q->publiclyVisible())
            ->withCount(['songs as visible_songs_count' => fn ($q) => $q->publiclyVisible()])
            ->orderByDesc('visible_songs_count')->orderBy('name')->limit($limit)->get();
    }
}
