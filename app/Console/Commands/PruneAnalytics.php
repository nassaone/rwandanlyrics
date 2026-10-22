<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneAnalytics extends Command
{
    protected $signature = 'rwandanlyrics:prune-analytics';

    protected $description = 'Delete short-lived visitor dedup rows older than yesterday. Daily totals are kept.';

    public function handle(): int
    {
        $deleted = DB::table('view_events')->where('viewed_on', '<', now()->subDay()->toDateString())->delete();
        $this->info("Deleted {$deleted} old visitor rows.");

        return self::SUCCESS;
    }
}
