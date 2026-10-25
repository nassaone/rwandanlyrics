<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Lyric;
use App\Models\Song;
use App\Services\ViewStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SongViewCountTest extends TestCase
{
    use RefreshDatabase;

    private function song(): Song
    {
        $artist = Artist::factory()->published()->create();
        $song = Song::factory()->published()->create();
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);
        Lyric::factory()->for($song)->create();

        return $song;
    }

    public function test_compact_formats_numbers(): void
    {
        $this->assertSame('0', ViewStats::compact(0));
        $this->assertSame('950', ViewStats::compact(950));
        $this->assertSame('1K', ViewStats::compact(1000));
        $this->assertSame('1.2K', ViewStats::compact(1234));
        $this->assertSame('3.4M', ViewStats::compact(3400000));
    }

    public function test_song_views_adds_all_days_for_that_song_only(): void
    {
        $a = $this->song();
        $b = $this->song();
        DB::table('daily_views')->insert([
            ['type' => 'song', 'item_id' => $a->id, 'viewed_on' => now()->subDays(40)->toDateString(), 'views' => 700],
            ['type' => 'song', 'item_id' => $a->id, 'viewed_on' => now()->toDateString(), 'views' => 300],
            ['type' => 'song', 'item_id' => $b->id, 'viewed_on' => now()->toDateString(), 'views' => 50],
            ['type' => 'artist', 'item_id' => $a->id, 'viewed_on' => now()->toDateString(), 'views' => 999],
        ]);

        $this->assertSame(1000, app(ViewStats::class)->songViews($a->id));
    }

    public function test_page_shows_the_count_only_when_the_song_has_views(): void
    {
        $quiet = $this->song();
        $busy = $this->song();
        DB::table('daily_views')->insert([
            'type' => 'song', 'item_id' => $busy->id, 'viewed_on' => now()->toDateString(), 'views' => 5000,
        ]);

        $this->get(route('songs.show', $busy->slug))->assertOk()->assertSee('data-views', false)->assertSee('5K views');
        $this->get(route('songs.show', $quiet->slug))->assertOk()->assertDontSee('data-views', false);
    }
}
