<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_song_page_has_the_share_menu_with_a_tracked_link(): void
    {
        $artist = Artist::factory()->published()->create(['name' => 'Share Star']);
        $song = Song::factory()->published()->create(['title' => 'Shareable']);
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);

        $this->get(route('songs.show', $song->slug))->assertOk()
            ->assertSee('Share on WhatsApp')
            ->assertSee('Copy link')
            ->assertSee('?ref=share', false)
            ->assertSee('Lyrics of Shareable by Share Star on', false);
    }
}
