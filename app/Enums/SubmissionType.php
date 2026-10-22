<?php

namespace App\Enums;

enum SubmissionType: string
{
    case NewSong = 'new_song';
    case Correction = 'correction';

    public function label(): string
    {
        return match ($this) {
            self::NewSong => 'New song',
            self::Correction => 'Correction',
        };
    }
}
