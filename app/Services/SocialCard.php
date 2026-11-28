<?php

namespace App\Services;

/**
 * Draws the 1200x630 preview image shown when a link is shared on WhatsApp, Facebook, X or Telegram.
 * Pure GD, no lyrics text: only the title, a subtitle (artist), the cover and the brand.
 */
class SocialCard
{
    private const W = 1200;

    private const H = 630;

    private const S = 2; // draw at 2x and scale down for smooth edges

    public static function available(): bool
    {
        return function_exists('imagecreatetruecolor') && function_exists('imagettftext') && is_file(self::font(true));
    }

    private static function font(bool $bold): string
    {
        return dirname(__DIR__, 2).'/resources/fonts/'.($bold ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf');
    }

    private static function pt(float $px): float
    {
        return $px * self::S * 0.75;
    }

    private static function width(string $text, string $font, float $px): int
    {
        $box = imagettfbbox(self::pt($px), 0, $font, $text);

        return (int) abs($box[2] - $box[0]);
    }

    /** @return list<string> */
    private static function wrap(string $text, string $font, float $px, int $maxWidth, int $maxLines): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $try = $line === '' ? $word : $line.' '.$word;
            if ($line !== '' && self::width($try, $font, $px) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $last = rtrim($lines[$maxLines - 1], ' .,;:-');
            while ($last !== '' && self::width($last.'…', $font, $px) > $maxWidth) {
                $last = mb_substr($last, 0, -1);
            }
            $lines[$maxLines - 1] = $last.'…';
        }

        return $lines;
    }

    private static function roundCorners($img, int $x, int $y, int $size, int $radius, int $bg): void
    {
        for ($dx = 0; $dx < $radius; $dx++) {
            for ($dy = 0; $dy < $radius; $dy++) {
                if (hypot($radius - $dx - 0.5, $radius - $dy - 0.5) <= $radius) {
                    continue;
                }
                imagesetpixel($img, $x + $dx, $y + $dy, $bg);
                imagesetpixel($img, $x + $size - 1 - $dx, $y + $dy, $bg);
                imagesetpixel($img, $x + $dx, $y + $size - 1 - $dy, $bg);
                imagesetpixel($img, $x + $size - 1 - $dx, $y + $size - 1 - $dy, $bg);
            }
        }
    }

    public static function png(string $title, string $subtitle = '', ?string $coverBytes = null, string $label = 'Lyrics', string $domain = 'vocalvibesnation.online'): string
    {
        $S = self::S;
        $bold = self::font(true);
        $regular = self::font(false);

        $img = imagecreatetruecolor(self::W * $S, self::H * $S);
        $bg = imagecolorallocate($img, 14, 21, 18);
        $white = imagecolorallocate($img, 238, 244, 240);
        $muted = imagecolorallocate($img, 168, 186, 177);
        $faint = imagecolorallocate($img, 108, 130, 118);
        $green = imagecolorallocate($img, 61, 220, 151);
        $sun = imagecolorallocate($img, 250, 204, 21);
        $sky = imagecolorallocate($img, 56, 153, 222);
        $ink = imagecolorallocate($img, 14, 21, 18);
        imagefilledrectangle($img, 0, 0, self::W * $S, self::H * $S, $bg);

        // cover (or a placeholder with the first letter)
        $size = 440 * $S;
        $cx = 70 * $S;
        $cy = 83 * $S;
        $src = $coverBytes ? @imagecreatefromstring($coverBytes) : false;
        if ($src) {
            $sw = imagesx($src);
            $sh = imagesy($src);
            $side = min($sw, $sh);
            imagecopyresampled($img, $src, $cx, $cy, (int) (($sw - $side) / 2), (int) (($sh - $side) / 2), $size, $size, $side, $side);
        } else {
            imagefilledrectangle($img, $cx, $cy, $cx + $size - 1, $cy + $size - 1, imagecolorallocate($img, 27, 107, 69));
            $letter = mb_strtoupper(mb_substr(trim($title), 0, 1)) ?: 'V';
            $box = imagettfbbox(self::pt(200), 0, $bold, $letter);
            imagettftext($img, self::pt(200), 0, (int) ($cx + ($size - ($box[2] - $box[0])) / 2 - $box[0]), (int) ($cy + ($size - ($box[1] - $box[7])) / 2 - $box[7]), $green, $bold, $letter);
        }
        self::roundCorners($img, $cx, $cy, $size, 30 * $S, $bg);

        // text column
        $x = 570 * $S;
        $maxWidth = (1200 - 570 - 70) * $S;

        // brand
        $first = 'Vocal Vibes ';
        imagettftext($img, self::pt(30), 0, $x, 118 * $S, $white, $bold, $first);
        imagettftext($img, self::pt(30), 0, $x + self::width($first, $bold, 30), 118 * $S, $green, $bold, 'Nation');

        // label pill
        $pillW = self::width($label, $bold, 24) + 44 * $S;
        $pillH = 46 * $S;
        $pillY = 156 * $S;
        imagefilledrectangle($img, $x + 23 * $S, $pillY, $x + $pillW - 23 * $S, $pillY + $pillH, $sun);
        imagefilledellipse($img, $x + 23 * $S, $pillY + (int) ($pillH / 2), $pillH, $pillH, $sun);
        imagefilledellipse($img, $x + $pillW - 23 * $S, $pillY + (int) ($pillH / 2), $pillH, $pillH, $sun);
        imagettftext($img, self::pt(24), 0, $x + 22 * $S, $pillY + 33 * $S, $ink, $bold, $label);

        // title: shrink until it fits in three lines
        $px = 66;
        do {
            $lines = self::wrap($title, $bold, $px, $maxWidth, 3);
            $fits = true;
            foreach ($lines as $line) {
                if (self::width($line, $bold, $px) > $maxWidth) {
                    $fits = false;
                }
            }
            if ($fits && (count($lines) <= 2 || $px <= 46)) {
                break;
            }
            $px -= 4;
        } while ($px >= 38);

        $y = (count($lines) >= 3 ? 262 : 282) * $S;
        foreach ($lines as $line) {
            imagettftext($img, self::pt($px), 0, $x, $y, $white, $bold, $line);
            $y += (int) ($px * 1.22 * $S);
        }

        // subtitle (artist)
        if ($subtitle !== '') {
            $y += 10 * $S;
            foreach (self::wrap($subtitle, $regular, 36, $maxWidth, count($lines) >= 3 ? 1 : 2) as $line) {
                imagettftext($img, self::pt(36), 0, $x, $y, $muted, $regular, $line);
                $y += (int) (44 * $S);
            }
        }

        // domain and tricolor line
        imagettftext($img, self::pt(24), 0, $x, 566 * $S, $faint, $regular, $domain);
        $barY = (self::H - 14) * $S;
        $third = (int) (self::W * $S / 3);
        imagefilledrectangle($img, 0, $barY, $third, self::H * $S, $green);
        imagefilledrectangle($img, $third, $barY, 2 * $third, self::H * $S, $sun);
        imagefilledrectangle($img, 2 * $third, $barY, self::W * $S, self::H * $S, $sky);

        $out = imagecreatetruecolor(self::W, self::H);
        imagecopyresampled($out, $img, 0, 0, 0, 0, self::W, self::H, self::W * $S, self::H * $S);
        ob_start();
        imagepng($out, null, 6);

        return (string) ob_get_clean();
    }
}
