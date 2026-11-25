<?php

namespace App\Services;

use App\Models\Song;

/** Works out which song needs lyrics next, in a stable order (by id, wrapping at the end). */
class LyricsQueue
{
    public function remaining(): int
    {
        return Song::query()->whereDoesntHave('currentLyric')->count();
    }

    public function next(Song $current): ?Song
    {
        $base = fn () => Song::query()
            ->whereDoesntHave('currentLyric')
            ->whereKeyNot($current->id)
            ->orderBy('songs.id');

        return $base()->where('songs.id', '>', $current->id)->first() ?? $base()->first();
    }
}
