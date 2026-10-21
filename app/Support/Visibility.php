<?php

namespace App\Support;

use App\Enums\PublicationStatus;
use App\Enums\RightsStatus;
use App\Models\Song;

class Visibility
{
    /** In-memory twin of Song::publiclyVisible(); a test keeps the two in sync. */
    public static function isLive(Song $song): bool
    {
        return $song->status === PublicationStatus::Published
            && $song->published_at !== null
            && $song->published_at->lte(now())
            && $song->rights_status === RightsStatus::Authorized;
    }
}
