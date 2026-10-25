<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Lyric;
use App\Models\Song;
use App\Services\ViewStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrendingSongsTest extends TestCase
{
    use RefreshDatabase;

    private function song(string $title): Song
    {
        $artist = Artist::factory()->published()->create();
        $song = Song::factory()->published()->create(['title' => $title]);
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);
        Lyric::factory()->for($song)->create();

        return $song;
    }

    private function views(Song $song, int $daysAgo, int $views): void
    {
        DB::table('daily_views')->insert([
            'type' => 'song',
            'item_id' => $song->id,
            'viewed_on' => now()->subDays($daysAgo)->toDateString(),
            'views' => $views,
        ]);
    }

    public function test_recent_views_beat_old_views_even_when_old_total_is_bigger(): void
    {
        $ancient = $this->song('Ancient Hit');
        $fading = $this->song('Fading Hit');
        $fresh = $this->song('Fresh Hit');

        $this->views($ancient, 30, 10000);
        $this->views($fading, 13, 100);
        $this->views($fresh, 0, 20);

        $titles = app(ViewStats::class)->trendingSongs()->pluck('title')->all();

        $this->assertSame(['Fresh Hit', 'Fading Hit'], $titles);
    }

    public function test_songs_below_the_minimum_recent_views_do_not_trend(): void
    {
        $this->views($this->song('Barely Seen'), 0, 1);

        $this->assertTrue(app(ViewStats::class)->trendingSongs()->isEmpty());
    }

    public function test_hidden_songs_never_trend(): void
    {
        $draft = Song::factory()->create(['title' => 'Hidden Draft']);
        $this->views($draft, 0, 500);

        $this->assertTrue(app(ViewStats::class)->trendingSongs()->isEmpty());
    }

    public function test_home_shows_trending_only_when_there_is_something_trending(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Trending now');

        $this->views($this->song('Hot Song'), 0, 10);

        $this->get('/')->assertOk()->assertSee('Trending now')->assertSee('Hot Song');
    }
}
