<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Lyric;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAndLyricsPageTest extends TestCase
{
    use RefreshDatabase;

    private function song(string $title, Artist $artist): Song
    {
        $song = Song::factory()->published()->create(['title' => $title]);
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);

        return $song;
    }

    public function test_home_page_has_a_featured_lyric_card(): void
    {
        $artist = Artist::factory()->published()->create(['name' => 'Hero Artist']);
        $this->song('Hero Song', $artist);

        $this->get('/')->assertOk()
            ->assertSee('Read lyrics')->assertSee('Hero Song')->assertSee('Latest lyrics')->assertSee('All genres');
    }

    public function test_lyrics_show_section_labels_and_stanzas(): void
    {
        $artist = Artist::factory()->published()->create(['name' => 'Test Star']);
        $song = $this->song('Formatted', $artist);
        Lyric::factory()->for($song)->create(['body' => "[Verse 1]\nLine one\nLine two\n\n[Chorus]\nHook line"]);

        $this->get(route('songs.show', $song->slug))->assertOk()
            ->assertSeeInOrder(['Verse 1', 'Line one', 'Line two', 'Chorus', 'Hook line'])
            ->assertDontSee('[Verse 1]', false);
    }

    public function test_song_page_has_the_artist_card_and_one_video_player_per_layout(): void
    {
        $artist = Artist::factory()->published()->create(['name' => 'Card Star']);
        $song = $this->song('With Video', $artist);
        $song->forceFill(['youtube_id' => 'dQw4w9WgXcQ'])->save();

        $this->get(route('songs.show', $song->slug))->assertOk()
            ->assertSee('View all lyrics by Card Star')
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Open on YouTube');
    }
}
