<?php

namespace Tests\Feature\Public;

use App\Enums\PublicationStatus;
use App\Enums\RightsStatus;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Lyric;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function visibleSong(array $attributes = [], ?Artist $artist = null): Song
    {
        $artist ??= Artist::factory()->published()->create();
        $song = Song::factory()->published()->create($attributes);
        $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);
        Lyric::factory()->for($song)->create(['body' => 'Lyric body of '.$song->title]);

        return $song;
    }

    public function test_home_shows_visible_content_and_hides_everything_else(): void
    {
        $this->visibleSong(['title' => 'Visible Song']);
        Song::factory()->create(['title' => 'Hidden Draft']);
        Song::factory()->published()->create(['title' => 'Hidden Rights', 'rights_status' => RightsStatus::Unknown]);
        Song::factory()->published()->create(['title' => 'Removed Song', 'rights_status' => RightsStatus::Removed]);

        $this->get('/')->assertOk()
            ->assertSee('Visible Song')
            ->assertSee('Latest lyrics')
            ->assertDontSee('No lyrics published yet')
            ->assertDontSee('Hidden Draft')
            ->assertDontSee('Hidden Rights')
            ->assertDontSee('Removed Song');
    }

    public function test_song_page_shows_title_artist_and_lyrics(): void
    {
        $artist = Artist::factory()->published()->create(['name' => 'Page Artist']);
        $song = $this->visibleSong(['title' => 'Page Song'], $artist);

        $this->get(route('songs.show', $song->slug))->assertOk()
            ->assertSee('Page Song')
            ->assertSee('Page Artist')
            ->assertSee('Lyric body of Page Song')
            ->assertSee('content="index,follow"', false);
    }

    public function test_songs_that_are_not_publicly_visible_return_404(): void
    {
        $draft = Song::factory()->create();
        $unknown = Song::factory()->published()->create(['rights_status' => RightsStatus::Unknown]);
        $removed = Song::factory()->published()->create(['rights_status' => RightsStatus::Removed]);
        $archived = Song::factory()->published()->create(['status' => PublicationStatus::Archived]);

        foreach ([$draft, $unknown, $removed, $archived] as $song) {
            $this->get(route('songs.show', $song->slug))->assertNotFound();
        }
        $this->get('/songs/does-not-exist')->assertNotFound();
    }

    public function test_noindex_flag_is_respected_on_song_page(): void
    {
        $song = $this->visibleSong(['noindex' => true]);

        $this->get(route('songs.show', $song->slug))->assertOk()->assertSee('content="noindex,follow"', false);
    }

    public function test_artist_page_lists_only_visible_songs_and_unpublished_artist_is_404(): void
    {
        $artist = Artist::factory()->published()->create(['name' => 'Listed Artist']);
        $this->visibleSong(['title' => 'Shown Song'], $artist);
        $hidden = Song::factory()->create(['title' => 'Unshown Draft']);
        $hidden->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);

        $this->get(route('artists.show', $artist->slug))->assertOk()
            ->assertSee('Listed Artist')->assertSee('Shown Song')->assertDontSee('Unshown Draft');

        $draftArtist = Artist::factory()->create();
        $this->get(route('artists.show', $draftArtist->slug))->assertNotFound();
    }

    public function test_album_page_shows_ordered_visible_tracks_only(): void
    {
        $album = Album::factory()->published()->create();
        $this->visibleSong(['title' => 'Second Track', 'album_id' => $album->id, 'track_number' => 2]);
        $this->visibleSong(['title' => 'First Track', 'album_id' => $album->id, 'track_number' => 1]);
        Song::factory()->create(['title' => 'Hidden Track', 'album_id' => $album->id, 'track_number' => 3]);

        $this->get(route('albums.show', $album->slug))->assertOk()
            ->assertSeeInOrder(['First Track', 'Second Track'])
            ->assertDontSee('Hidden Track');

        $draftAlbum = Album::factory()->create();
        $this->get(route('albums.show', $draftAlbum->slug))->assertNotFound();
    }

    public function test_genre_page_paginates_visible_songs(): void
    {
        $genre = Genre::factory()->create(['name' => 'Pagination Genre']);
        for ($i = 0; $i < 25; $i++) {
            $this->visibleSong()->genres()->attach($genre->id);
        }

        $this->get(route('genres.show', $genre->slug))->assertOk()
            ->assertViewHas('songs', fn ($songs) => $songs->count() === 20 && $songs->total() === 25);
        $this->get(route('genres.show', $genre->slug).'?page=2')->assertOk()
            ->assertViewHas('songs', fn ($songs) => $songs->count() === 5);
    }

    public function test_empty_genre_page_is_noindex(): void
    {
        $genre = Genre::factory()->create();

        $this->get(route('genres.show', $genre->slug))->assertOk()
            ->assertSee('content="noindex,follow"', false);
    }

    public function test_listing_pages_render(): void
    {
        $this->visibleSong();

        $this->get(route('songs.index'))->assertOk();
        $this->get(route('artists.index'))->assertOk();
        $this->get(route('genres.index'))->assertOk();
    }

    public function test_song_page_query_count_stays_small(): void
    {
        $song = $this->visibleSong();
        $song->genres()->attach(Genre::factory()->create()->id);

        DB::enableQueryLog();
        $this->get(route('songs.show', $song->slug))->assertOk();

        $this->assertLessThanOrEqual(15, count(DB::getQueryLog()));
    }

    public function test_home_page_query_count_stays_small(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->visibleSong();
        }

        DB::enableQueryLog();
        $this->get('/')->assertOk();

        $this->assertLessThanOrEqual(12, count(DB::getQueryLog()));
    }
}
