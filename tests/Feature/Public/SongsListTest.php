<?php

namespace Tests\Feature\Public;

use App\Models\Artist;
use App\Models\Genre;
use App\Models\Lyric;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SongsListTest extends TestCase
{
    use RefreshDatabase;

    private function song(string $title, array $attributes = []): Song
    {
        $artist = Artist::factory()->published()->create();
        $song = Song::factory()->published()->create(array_merge(['title' => $title], $attributes));
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);
        Lyric::factory()->for($song)->create();

        return $song;
    }

    public function test_a_to_z_sorts_by_title_and_shows_the_count(): void
    {
        $this->song('Zulu Song');
        $this->song('Alpha Song');

        $this->get(route('songs.index', ['sort' => 'az']))
            ->assertOk()
            ->assertSeeInOrder(['Alpha Song', 'Zulu Song'])
            ->assertSee('2 songs');
    }

    public function test_genre_filter_shows_only_that_genre(): void
    {
        $gospel = Genre::factory()->create(['name' => 'Gospel', 'slug' => 'gospel']);
        Genre::factory()->create(['name' => 'Hiphop', 'slug' => 'hiphop']);
        $this->song('Praise Song')->genres()->attach($gospel->id);
        $this->song('Street Song');

        $this->get(route('songs.index', ['genre' => 'gospel']))
            ->assertOk()
            ->assertSee('Praise Song')
            ->assertDontSee('Street Song')
            ->assertSee('1 song in Gospel');
    }

    public function test_trending_lists_recently_viewed_songs_only(): void
    {
        $hot = $this->song('Hot Song');
        $this->song('Quiet Song');
        DB::table('daily_views')->insert([
            'type' => 'song', 'item_id' => $hot->id, 'viewed_on' => now()->toDateString(), 'views' => 10,
        ]);

        $this->get(route('songs.index', ['sort' => 'trending']))
            ->assertOk()
            ->assertSee('Hot Song')
            ->assertDontSee('Quiet Song');
    }

    public function test_empty_trending_and_unknown_filters_are_handled(): void
    {
        $this->song('Some Song');

        $this->get(route('songs.index', ['sort' => 'trending']))
            ->assertOk()->assertSee('Nothing is trending yet')->assertSee('Clear filters');

        $this->get(route('songs.index', ['sort' => 'nonsense', 'genre' => 'nope']))
            ->assertOk()->assertSee('Some Song');
    }

    public function test_rows_show_a_video_icon_only_for_songs_with_a_video(): void
    {
        $this->song('Video Song', ['youtube_id' => 'dQw4w9WgXcQ']);

        $this->get(route('songs.index'))->assertOk()->assertSee('aria-label="Has video"', false);

        Song::query()->update(['youtube_id' => null]);

        $this->get(route('songs.index'))->assertOk()->assertDontSee('aria-label="Has video"', false);
    }
}
