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

        $songs = Song::publiclyVisible()->with('artists')->whereIn('songs.id', $rows->pluck('item_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => $songs[$row->item_id] ?? null)->filter()->take($limit)->values();
    }
}
