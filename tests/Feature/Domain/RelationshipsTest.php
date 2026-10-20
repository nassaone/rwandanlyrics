<?php

namespace Tests\Feature\Domain;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Lyric;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_song_has_primary_and_featured_artists_in_order(): void
    {
        $song = Song::factory()->create();
        $main = Artist::factory()->create();
        $guest = Artist::factory()->create();

        $song->artists()->attach($guest->id, ['role' => 'featured', 'position' => 1]);
        $song->artists()->attach($main->id, ['role' => 'primary', 'position' => 0]);

        $this->assertSame(['primary', 'featured'], $song->artists->pluck('pivot.role')->all());
    }

    public function test_song_genres_and_artist_genres(): void
    {
        $genre = Genre::factory()->create(['name' => 'Gospel']);
        $song = Song::factory()->create();
        $artist = Artist::factory()->create();

        $song->genres()->attach($genre);
        $artist->genres()->attach($genre);

        $this->assertSame('gospel', $genre->slug);
        $this->assertCount(1, $genre->songs);
        $this->assertCount(1, $genre->artists);
    }

    public function test_album_tracklist_is_ordered_and_singles_need_no_album(): void
    {
        $album = Album::factory()->create();
        Song::factory()->create(['title' => 'Second', 'album_id' => $album->id, 'track_number' => 2]);
        Song::factory()->create(['title' => 'First', 'album_id' => $album->id, 'track_number' => 1]);
        $single = Song::factory()->create(['title' => 'Single']);

        $this->assertSame(['First', 'Second'], $album->songs->pluck('title')->all());
        $this->assertNull($single->album_id);
    }

    public function test_current_lyric_is_the_latest_current_revision(): void
    {
        $song = Song::factory()->create();
        Lyric::factory()->for($song)->create(['revision_number' => 1, 'is_current' => false]);
        Lyric::factory()->for($song)->create(['revision_number' => 2, 'is_current' => true]);

        $this->assertSame(2, $song->currentLyric->revision_number);
        $this->assertCount(2, $song->lyrics);
    }
}
