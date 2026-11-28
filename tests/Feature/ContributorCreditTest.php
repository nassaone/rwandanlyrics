<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Lyric;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributorCreditTest extends TestCase
{
    use RefreshDatabase;

    private function song(string $title): Song
    {
        $artist = Artist::factory()->published()->create(['name' => $title.' Artist']);
        $song = Song::factory()->published()->create(['title' => $title]);
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);

        return $song;
    }

    public function test_a_song_without_lyrics_invites_visitors_to_add_them(): void
    {
        $song = $this->song('Empty One');

        $this->get(route('songs.show', $song->slug))->assertOk()
            ->assertSee('Lyrics are not available for this song')
            ->assertSee('Add lyrics')
            ->assertSee(route('contribute.create', ['song' => $song->slug]), false);
    }

    public function test_approved_contributions_are_credited_on_the_song_page(): void
    {
        $song = $this->song('Credited');
        $person = User::factory()->create(['name' => 'Aline Contributor']);
        Lyric::factory()->for($song)->create(['contributed_by' => $person->id]);

        $this->get(route('songs.show', $song->slug))->assertOk()
            ->assertSee('Lyrics contributed by')->assertSee('Aline Contributor');
    }

    public function test_lyrics_written_by_editors_show_no_contributor_line(): void
    {
        $song = $this->song('Editor Made');
        Lyric::factory()->for($song)->create();

        $this->get(route('songs.show', $song->slug))->assertOk()->assertDontSee('Lyrics contributed by');
    }
}
