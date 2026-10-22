<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Privacy-friendly view counting. A visitor is a one-way hash of IP + browser + date + app key,
 * so it changes every day and nothing personal is stored. One count per visitor per page per day.
 */
class ViewCounter
{
    public static function isHuman(?string $userAgent): bool
    {
        $userAgent = (string) $userAgent;

        return str_contains($userAgent, 'Mozilla/')
            && ! preg_match('/bot|crawl|spider|slurp|preview|headless|monitor|lighthouse|facebookexternalhit|curl|wget|python|httpclient|java\//i', $userAgent);
    }

    public function record(string $type, int $itemId, string $ip, string $userAgent): bool
    {
        $today = now()->toDateString();
        $hash = hash('sha256', $ip.'|'.$userAgent.'|'.$today.'|'.config('app.key'));

        $isNew = DB::table('view_events')->insertOrIgnore([
            'type' => $type, 'item_id' => $itemId, 'visitor_hash' => $hash, 'viewed_on' => $today,
        ]) > 0;

        if (! $isNew) {
            return false;
        }

        $where = ['type' => $type, 'item_id' => $itemId, 'viewed_on' => $today];

        if (DB::table('daily_views')->where($where)->increment('views') === 0) {
            $created = DB::table('daily_views')->insertOrIgnore($where + ['views' => 1]);
            if ($created === 0) {
                DB::table('daily_views')->where($where)->increment('views');
            }
        }

        return true;
    }
}
