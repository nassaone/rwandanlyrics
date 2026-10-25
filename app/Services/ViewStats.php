<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Reads the real aggregate counts. Never invents or pads numbers. */
class ViewStats
{
    private function since(int $days): string
    {
        return now()->subDays(max(1, $days) - 1)->toDateString();
    }

    public function total(int $days): int
    {
        return (int) DB::table('daily_views')->where('viewed_on', '>=', $this->since($days))->sum('views');
    }

    /** @return Collection<int, array{model: mixed, views: int}> */
    public function top(string $type, int $days, int $limit = 10): Collection
    {
        $class = ['song' => Song::class, 'artist' => Artist::class, 'album' => Album::class][$type];

        $rows = DB::table('daily_views')
            ->where('type', $type)->where('viewed_on', '>=', $this->since($days))
            ->selectRaw('item_id, sum(views) as views')->groupBy('item_id')
            ->orderByDesc('views')->limit($limit)->get();

        $models = $class::whereIn('id', $rows->pluck('item_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => ['model' => $models[$row->item_id] ?? null, 'views' => (int) $row->views])
            ->filter(fn ($row) => $row['model'] !== null)->values();
    }

    /** Public "Popular" list: only publicly visible songs with enough real views. */
    public function popularSongs(?int $days = null, int $limit = 8): Collection
    {
        $days ??= (int) config('rwandanlyrics.analytics.popular_days', 7);
        $min = (int) config('rwandanlyrics.analytics.min_views', 3);

        $rows = DB::table('daily_views')
            ->where('type', 'song')->where('viewed_on', '>=', $this->since($days))
            ->selectRaw('item_id, sum(views) as views')->groupBy('item_id')
            ->havingRaw('sum(views) >= ?', [$min])
            ->orderByDesc('views')->limit($limit * 3)->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $songs = Song::publiclyVisible()->with(['artists.image', 'cover', 'album.cover'])->whereIn('songs.id', $rows->pluck('item_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => $songs[$row->item_id] ?? null)->filter()->take($limit)->values();
    }

    /**
     * "Trending" list: only recent views count, and newer days count more
     * (half-life decay), so old songs with big lifetime totals do not stay on top.
     */
    public function trendingSongs(?int $days = null, int $limit = 8): Collection
    {
        $days ??= (int) config('rwandanlyrics.analytics.trending_days', 14);
        $halfLife = max(0.5, (float) config('rwandanlyrics.analytics.trending_half_life', 3));
        $min = (int) config('rwandanlyrics.analytics.min_views', 3);
        $today = now()->startOfDay()->getTimestamp();

        $rows = DB::table('daily_views')
            ->where('type', 'song')
            ->where('viewed_on', '>=', $this->since($days))
            ->get(['item_id', 'viewed_on', 'views']);

        $scores = [];
        $totals = [];

        foreach ($rows as $row) {
            $age = max(0, ($today - strtotime($row->viewed_on)) / 86400);
            $scores[$row->item_id] = ($scores[$row->item_id] ?? 0) + $row->views * (0.5 ** ($age / $halfLife));
            $totals[$row->item_id] = ($totals[$row->item_id] ?? 0) + $row->views;
        }

        $scores = array_filter($scores, fn ($score, $id) => $totals[$id] >= $min, ARRAY_FILTER_USE_BOTH);

        if ($scores === []) {
            return collect();
        }

        arsort($scores);
        $ids = array_slice(array_keys($scores), 0, $limit * 3);

        $songs = Song::publiclyVisible()
            ->with(['artists.image', 'cover', 'album.cover'])
            ->whereIn('songs.id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn ($id) => $songs[$id] ?? null)->filter()->take($limit)->values();
    }

    /** Lifetime recorded views for one song (real counts only). */
    public function songViews(int $songId): int
    {
        return (int) DB::table('daily_views')
            ->where('type', 'song')
            ->where('item_id', $songId)
            ->sum('views');
    }

    /** Short display form: 950, 1.2K, 3.4M. */
    public static function compact(int $n): string
    {
        if ($n < 1000) {
            return (string) $n;
        }

        if ($n < 1000000) {
            return rtrim(rtrim(number_format($n / 1000, 1, '.', ''), '0'), '.').'K';
        }

        return rtrim(rtrim(number_format($n / 1000000, 1, '.', ''), '0'), '.').'M';
    }
}
