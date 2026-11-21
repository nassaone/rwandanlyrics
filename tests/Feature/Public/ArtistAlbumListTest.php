<?php

namespace Tests\Feature\Public;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArtistAlbumListTest extends TestCase
{
    use RefreshDatabase;

    private function views(string $type, int $id, int $views): void
    {
        DB::table('daily_views')->insert([
            'type' => $type, 'item_id' => $id, 'viewed_on' => now()->toDateString(), 'views' => $views,
        ]);
    }

    private function album(string $title, array $attributes = []): Album
    {
        return Album::factory()->create(array_merge([
            'title' => $title, 'status' => 'published', 'published_at' => now()->subMinute(),
        ], $attributes));
    }

    public function test_artists_default_to_a_to_z_and_newest_sorts_by_publish_date(): void
    {
        Artist::factory()->published()->create(['name' => 'Zulu Star', 'published_at' => now()->subDay()]);
        Artist::factory()->published()->create(['name' => 'Alpha Star', 'published_at' => now()->subDays(5)]);

        $this->get(route('artists.index'))->assertOk()
            ->assertSeeInOrder(['Alpha Star', 'Zulu Star'])->assertSee('2 artists');
        $this->get(route('artists.index', ['sort' => 'newest']))->assertOk()
            ->assertSeeInOrder(['Zulu Star', 'Alpha Star']);
    }

    public function test_artists_genre_filter_uses_their_own_genres_and_their_songs(): void
    {
        $genre = Genre::factory()->create(['name' => 'Gospel', 'slug' => 'gospel']);
        $direct = Artist::factory()->published()->create(['name' => 'Praise Band']);
        $viaSong = Artist::factory()->published()->create(['name' => 'Song Band']);
        Artist::factory()->published()->create(['name' => 'Street Band']);

        $direct->genres()->attach($genre->id);
        $song = Song::factory()->published()->create();
        $song->artists()->attach($viaSong->id, ['role' => 'primary', 'position' => 0]);
        $song->genres()->attach($genre->id);

        $this->get(route('artists.index', ['genre' => 'gospel']))->assertOk()
            ->assertSee('Praise Band')->assertSee('Song Band')->assertDontSee('Street Band')
            ->assertSee('2 artists in Gospel')
            ->assertSee('content="noindex,follow"', false);
    }

    public function test_artists_trending_uses_recent_views(): void
    {
        $this->get(route('artists.index', ['sort' => 'trending']))->assertOk()->assertSee('Nothing is trending yet');

        $hot = Artist::factory()->published()->create(['name' => 'Hot Artist']);
        Artist::factory()->published()->create(['name' => 'Quiet Artist']);
        $this->views('artist', $hot->id, 10);

        $this->get(route('artists.index', ['sort' => 'trending']))->assertOk()
            ->assertSee('Hot Artist')->assertDontSee('Quiet Artist');
    }

    public function test_the_main_artist_list_stays_indexable(): void
    {
        Artist::factory()->published()->create();

        $this->get(route('artists.index'))->assertOk()->assertSee('content="index,follow"', false);
    }

    public function test_albums_list_shows_published_albums_only_and_sorts(): void
    {
        $this->album('Zeta Album', ['release_date' => '2020-01-01']);
        $this->album('Alpha Album', ['release_date' => '2024-01-01']);
        Album::factory()->create(['title' => 'Hidden Album']);

        $this->get(route('albums.index'))->assertOk()
            ->assertSeeInOrder(['Alpha Album', 'Zeta Album'])->assertDontSee('Hidden Album')->assertSee('2 albums');
        $this->get(route('albums.index', ['sort' => 'az']))->assertOk()
            ->assertSeeInOrder(['Alpha Album', 'Zeta Album']);
    }

    public function test_albums_genre_filter_and_trending(): void
    {
        $genre = Genre::factory()->create(['name' => 'Gospel', 'slug' => 'gospel']);
        $gospel = $this->album('Praise Album');
        $this->album('Street Album');
        Song::factory()->published()->create(['album_id' => $gospel->id])->genres()->attach($genre->id);

        $this->get(route('albums.index', ['genre' => 'gospel']))->assertOk()
            ->assertSee('Praise Album')->assertDontSee('Street Album')->assertSee('1 album in Gospel');

        $this->get(route('albums.index', ['sort' => 'trending']))->assertOk()->assertSee('Nothing is trending yet');
        $this->views('album', $gospel->id, 10);
        $this->get(route('albums.index', ['sort' => 'trending']))->assertOk()
            ->assertSee('Praise Album')->assertDontSee('Street Album');
    }

    public function test_empty_albums_page_has_a_clear_message(): void
    {
        $this->get(route('albums.index'))->assertOk()->assertSee('No albums published yet');
    }
}
