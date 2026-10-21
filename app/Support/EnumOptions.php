<?php

namespace App\Support;

use Illuminate\Support\Str;

class EnumOptions
{
    /** @param class-string<\BackedEnum> $enum */
    public static function for(string $enum): array
    {
        return collect($enum::cases())
            ->mapWithKeys(fn ($case) => [$case->value => Str::headline($case->value)])
            ->all();
    }
}
