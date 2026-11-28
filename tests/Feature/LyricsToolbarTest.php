<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Lyric;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LyricsToolbarTest extends TestCase
{
    use RefreshDatabase;

    private function song(string $title): Song
    {
        $artist = Artist::factory()->published()->create(['name' => $title.' Artist']);
        $song = Song::factory()->published()->create(['title' => $title]);
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);

        return $song;
    }

    public function test_toolbar_has_share_copy_and_download_when_there_are_lyrics(): void
    {
        $song = $this->song('With Words');
        Lyric::factory()->for($song)->create(['body' => 'First line']);

        $this->get(route('songs.show', $song->slug))->assertOk()
            ->assertSee('data-lyrics-toolbar', false)
            ->assertSee('Share on WhatsApp')
            ->assertSee('Download')
            ->assertSee('Lyrics from', false);
    }

    public function test_toolbar_has_only_share_when_there_are_no_lyrics(): void
    {
        $song = $this->song('No Words');

        $this->get(route('songs.show', $song->slug))->assertOk()
            ->assertSee('Share on WhatsApp')
            ->assertDontSee('Download');
    }
}
