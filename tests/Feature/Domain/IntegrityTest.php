<?php

namespace Tests\Feature\Domain;

use App\Models\Album;
use App\Models\Artist;
use App\Models\ArtistAlias;
use App\Models\Song;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_songs_with_the_same_title_get_distinct_slugs(): void
    {
        $a = Song::factory()->create(['title' => 'Same Title']);
        $b = Song::factory()->create(['title' => 'Same Title']);

        $this->assertSame('same-title', $a->slug);
        $this->assertSame('same-title-2', $b->slug);
    }

    public function test_duplicate_artist_names_are_rejected_regardless_of_case_and_spacing(): void
    {
        Artist::factory()->create(['name' => 'Demo Name']);

        $this->expectException(QueryException::class);
        Artist::factory()->create(['name' => 'DEMO  name ']);
    }

    public function test_alias_is_unique_per_artist(): void
    {
        $artist = Artist::factory()->create();
        ArtistAlias::create(['artist_id' => $artist->id, 'alias' => 'Nick Name']);

        $this->expectException(QueryException::class);
        ArtistAlias::create(['artist_id' => $artist->id, 'alias' => 'nick-name']);
    }

    public function test_deleting_an_album_keeps_its_songs(): void
    {
        $album = Album::factory()->create();
        $song = Song::factory()->create(['album_id' => $album->id]);

        $album->delete();

        $this->assertNull($song->fresh()->album_id);
        $this->assertDatabaseHas('songs', ['id' => $song->id]);
    }
}
