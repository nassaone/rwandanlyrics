<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

class FakeImage
{
    public static function png(int $width = 300, int $height = 300): string
    {
        $row = "\x00".str_repeat("\x20\x40\x80", $width);
        $raw = str_repeat($row, $height);

        return "\x89PNG\r\n\x1a\n"
            .self::chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .self::chunk('IDAT', gzcompress($raw))
            .self::chunk('IEND', '');
    }

    public static function upload(string $name = 'photo.png', int $width = 300, int $height = 300): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, self::png($width, $height));
    }

    private static function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
