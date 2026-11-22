<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SongRelatedSectionsTest extends TestCase
{
    use RefreshDatabase;

    private function song(string $title, Artist $artist, array $attrs = []): Song
    {
        $song = Song::factory()->published()->create(['title' => $title] + $attrs);
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);

        return $song;
    }

    public function test_song_page_shows_more_from_the_artist_and_new_lyrics(): void
    {
        $star = Artist::factory()->published()->create(['name' => 'Test Star']);
        $other = Artist::factory()->published()->create(['name' => 'Someone Else']);

        $alpha = $this->song('Alpha Song', $star);
        $this->song('Bravo Song', $star);
        $this->song('Charlie Song', $other);
        $this->song('Secret Draft', $star, ['status' => 'draft', 'published_at' => null]);

        $this->get(route('songs.show', $alpha->slug))->assertOk()
            ->assertSee('More from Test Star')
            ->assertSee('Bravo Song')
            ->assertSee('See all songs by Test Star')
            ->assertSee('New lyrics')
            ->assertSee('Charlie Song')
            ->assertDontSee('Secret Draft');
    }

    public function test_a_song_never_lists_itself(): void
    {
        $star = Artist::factory()->published()->create(['name' => 'Solo Star']);
        $only = $this->song('Only Song', $star);

        $html = $this->get(route('songs.show', $only->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('More from Solo Star', $html);
        $this->assertStringNotContainsString('New lyrics', $html);
    }
}
