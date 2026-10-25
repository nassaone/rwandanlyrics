<?php

namespace Tests\Unit;

use App\Support\YouTube;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class YouTubeTest extends TestCase
{
    public static function valid(): array
    {
        $id = 'dQw4w9WgXcQ';

        return [
            'watch' => ["https://www.youtube.com/watch?v=$id"],
            'watch with extras' => ["https://www.youtube.com/watch?feature=share&v=$id&t=42s"],
            'mobile' => ["https://m.youtube.com/watch?v=$id"],
            'music' => ["https://music.youtube.com/watch?v=$id"],
            'short link' => ["https://youtu.be/$id?si=abc"],
            'shorts' => ["https://www.youtube.com/shorts/$id"],
            'embed' => ["https://www.youtube.com/embed/$id"],
            'nocookie embed' => ["https://www.youtube-nocookie.com/embed/$id"],
            'live' => ["https://www.youtube.com/live/$id"],
            'no scheme' => ["youtube.com/watch?v=$id"],
            'bare id' => [$id],
            'padded' => ["  https://youtu.be/$id  "],
        ];
    }

    #[DataProvider('valid')]
    public function test_extracts_the_video_id(string $input): void
    {
        $this->assertSame('dQw4w9WgXcQ', YouTube::extractId($input));
    }

    public static function invalid(): array
    {
        return [
            'empty' => [''],
            'other site' => ['https://vimeo.com/watch?v=dQw4w9WgXcQ'],
            'lookalike host' => ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ'],
            'lookalike prefix' => ['https://notyoutube.com/watch?v=dQw4w9WgXcQ'],
            'too short id' => ['https://youtu.be/abc'],
            'too long id' => ['https://youtu.be/dQw4w9WgXcQextra'],
            'bad characters' => ['https://www.youtube.com/watch?v=dQw4w9WgX"><script>'],
            'playlist only' => ['https://www.youtube.com/playlist?list=PL12345678901234'],
            'javascript' => ['javascript:alert(1)'],
            'array param' => ['https://www.youtube.com/watch?v[]=dQw4w9WgXcQ'],
        ];
    }

    #[DataProvider('invalid')]
    public function test_rejects_everything_else(string $input): void
    {
        $this->assertNull(YouTube::extractId($input));
    }

    public function test_null_is_null_and_urls_are_built_from_the_id_only(): void
    {
        $this->assertNull(YouTube::extractId(null));
        $this->assertStringStartsWith('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', YouTube::embedUrl('dQw4w9WgXcQ'));
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', YouTube::watchUrl('dQw4w9WgXcQ'));
    }
}
