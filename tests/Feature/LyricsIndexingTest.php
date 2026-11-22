<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Lyric;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LyricsIndexingTest extends TestCase
{
    use RefreshDatabase;

    private function song(string $title, bool $withLyrics): Song
    {
        $artist = Artist::factory()->published()->create(['name' => $title.' Artist']);
        $song = Song::factory()->published()->create(['title' => $title]);
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);
        if ($withLyrics) {
            Lyric::factory()->for($song)->create();
        }

        return $song;
    }

    public function test_a_song_without_lyrics_is_noindex_and_one_with_lyrics_is_indexable(): void
    {
        $with = $this->song('Has Words', true);
        $without = $this->song('No Words', false);

        $this->get(route('songs.show', $with->slug))->assertOk()->assertSee('content="index,follow"', false);
        $this->get(route('songs.show', $without->slug))->assertOk()->assertSee('content="noindex,follow"', false);
    }

    public function test_sitemaps_only_list_pages_that_have_lyrics(): void
    {
        $with = $this->song('Has Words', true);
        $without = $this->song('No Words', false);

        $this->get('/sitemaps/songs-1.xml')->assertOk()
            ->assertSee("/songs/{$with->slug}", false)
            ->assertDontSee("/songs/{$without->slug}", false);

        $this->get('/sitemaps/artists-1.xml')->assertOk()
            ->assertSee('/artists/'.$with->artists->first()->slug, false)
            ->assertDontSee('/artists/'.$without->artists->first()->slug, false);
    }

    public function test_adding_lyrics_puts_the_song_in_the_sitemap(): void
    {
        $song = $this->song('Later Words', false);
        Lyric::factory()->for($song)->create();

        $this->get('/sitemaps/songs-1.xml')->assertOk()->assertSee("/songs/{$song->slug}", false);
    }
}
