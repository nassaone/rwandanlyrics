<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class AdminDate
{
    public static function timezone(): string
    {
        return config('rwandanlyrics.admin_timezone', 'Africa/Kigali');
    }

    /** Admin form value (entered in the admin time zone) to UTC. */
    public static function fromInput(?string $value): ?Carbon
    {
        return filled($value) ? Carbon::parse($value, self::timezone())->utc() : null;
    }

    /** Stored UTC value to a datetime-local form value in the admin time zone. */
    public static function toInput(?CarbonInterface $date): ?string
    {
        return $date?->copy()->timezone(self::timezone())->format('Y-m-d\TH:i');
    }

    public static function display(?CarbonInterface $date): string
    {
        return $date ? $date->copy()->timezone(self::timezone())->format('j M Y H:i') : '—';
    }
}
