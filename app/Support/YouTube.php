<?php

namespace App\Support;

/** Turns a pasted YouTube link into a safe 11-character video ID, or null. */
final class YouTube
{
    private const ID = '/^[A-Za-z0-9_-]{11}$/';

    private const HOSTS = ['youtube.com', 'youtube-nocookie.com', 'youtu.be'];

    public static function extractId(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }
        if (preg_match(self::ID, $input)) {
            return $input;
        }

        $url = preg_match('#^https?://#i', $input) ? $input : 'https://'.$input;
        $parts = parse_url($url);
        if (! $parts || empty($parts['host'])) {
            return null;
        }
        $host = strtolower(preg_replace('/^(www|m|music)\./i', '', $parts['host']));
        if (! in_array($host, self::HOSTS, true)) {
            return null;
        }

        $path = $parts['path'] ?? '';
        $id = null;
        if ($host === 'youtu.be') {
            $id = explode('/', trim($path, '/'))[0] ?? null;
        } elseif (str_starts_with($path, '/watch')) {
            parse_str($parts['query'] ?? '', $query);
            $id = is_string($query['v'] ?? null) ? $query['v'] : null;
        } elseif (preg_match('#^/(embed|shorts|live|v)/([^/?]+)#', $path, $m)) {
            $id = $m[2];
        }

        return $id && preg_match(self::ID, $id) ? $id : null;
    }

    public static function embedUrl(string $id): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$id.'?autoplay=1&rel=0';
    }

    public static function watchUrl(string $id): string
    {
        return 'https://www.youtube.com/watch?v='.$id;
    }
}
