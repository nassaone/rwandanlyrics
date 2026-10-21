<?php

namespace App\Console\Commands;

use App\Enums\PublicationStatus;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Support\AuditLogger;
use Illuminate\Console\Command;

class PublishScheduledContent extends Command
{
    protected $signature = 'rwandanlyrics:publish-scheduled';

    protected $description = 'Publish scheduled songs, artists and albums whose publication time has arrived';

    public function handle(): int
    {
        $count = 0;

        foreach ([Song::class, Artist::class, Album::class] as $model) {
            $model::where('status', PublicationStatus::Scheduled->value)
                ->where('published_at', '<=', now())
                ->get()
                ->each(function ($item) use (&$count) {
                    $item->update(['status' => PublicationStatus::Published]);
                    AuditLogger::record('content.scheduled_published', $item);
                    $count++;
                });
        }

        $this->info("Published {$count} scheduled item(s).");

        return self::SUCCESS;
    }
}
