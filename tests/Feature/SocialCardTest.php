<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Song;
use App\Services\SocialCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialCardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/social-cards/song-*.png')) ?: [] as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function song(string $title, array $attrs = []): Song
    {
        $artist = Artist::factory()->published()->create(['name' => $title.' Artist']);
        $song = Song::factory()->published()->create(['title' => $title] + $attrs);
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);

        return $song;
    }

    public function test_song_pages_point_social_networks_at_their_own_card(): void
    {
        $song = $this->song('Card Song');

        $this->get(route('songs.show', $song->slug))->assertOk()
            ->assertSee('property="og:image" content="'.route('social.song', $song->slug).'"', false)
            ->assertSee('name="twitter:image" content="'.route('social.song', $song->slug).'"', false);
    }

    public function test_other_pages_keep_the_default_image(): void
    {
        $this->get('/')->assertOk()->assertSee('og-default.png', false)->assertDontSee('/social/songs/', false);
    }

    public function test_the_card_is_a_png_for_a_public_song(): void
    {
        if (! SocialCard::available()) {
            $this->markTestSkipped('GD with FreeType is not available here.');
        }
        $song = $this->song('Drawn Song');

        $response = $this->get(route('social.song', $song->slug))->assertOk()->assertHeader('Content-Type', 'image/png');
        $bytes = file_get_contents($response->baseResponse->getFile()->getPathname());

        $this->assertStringStartsWith("\x89PNG", $bytes);
        $this->assertSame([1200, 630], array_slice(getimagesizefromstring($bytes), 0, 2));
    }

    public function test_songs_that_are_not_public_have_no_card(): void
    {
        $hidden = $this->song('Hidden Song', ['rights_status' => 'unknown']);

        $this->get(route('social.song', $hidden->slug))->assertNotFound();
        $this->get('/social/songs/does-not-exist.png')->assertNotFound();
    }
}
