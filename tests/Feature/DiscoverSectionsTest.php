<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoverSectionsTest extends TestCase
{
    use RefreshDatabase;

    private function artist(string $name, string $slug, string $status = 'published'): Artist
    {
        return Artist::forceCreate(['name' => $name, 'slug' => $slug, 'status' => $status, 'published_at' => now()->subDay(), 'verification_status' => 'unverified']);
    }

    private function song(string $title, string $slug, Artist $artist, Genre $genre, string $status = 'published'): Song
    {
        $song = Song::forceCreate([
            'title' => $title, 'slug' => $slug, 'status' => $status, 'published_at' => now()->subMinute(),
            'rights_status' => 'authorized', 'verification_status' => 'unverified',
        ]);
        $song->artists()->attach($artist->id, ['role' => 'primary']);
        $song->genres()->attach($genre->id);

        return $song;
    }

    private function seed(): array
    {
        $rock = Genre::forceCreate(['name' => 'Rock', 'slug' => 'rock']);
        $a = $this->artist('Lead Artist', 'lead-artist');
        $b = $this->artist('Other Band', 'other-band');
        $this->song('Song One', 'song-one', $a, $rock);
        $this->song('Song Two', 'song-two', $b, $rock);

        return [$rock, $a, $b];
    }

    public function test_public_pages_have_a_keep_exploring_block_but_the_home_page_does_not(): void
    {
        $this->seed();

        $this->get('/artists/lead-artist')->assertOk()
            ->assertSee('Keep exploring')->assertSee('Song Two')->assertSee('Other Band');
        $this->get('/')->assertOk()->assertDontSee('Keep exploring');
    }

    public function test_artist_page_shows_similar_artists_and_more_lyrics(): void
    {
        $this->seed();

        $this->get('/artists/lead-artist')->assertOk()
            ->assertSee('Similar artists')->assertSee('More lyrics you may like');
    }

    public function test_genre_page_shows_top_artists(): void
    {
        $this->seed();

        $this->get('/genres/rock')->assertOk()->assertSee('Top artists in Rock')->assertSee('Lead Artist');
    }

    public function test_unpublished_content_never_appears_in_the_blocks(): void
    {
        [$rock, $a] = $this->seed();
        $ghost = $this->artist('Ghost Band', 'ghost-band', 'draft');
        $this->song('Hidden Draft', 'hidden-draft', $ghost, $rock, 'draft');

        $this->get('/artists/lead-artist')->assertOk()
            ->assertDontSee('Ghost Band')->assertDontSee('Hidden Draft');
    }
}
