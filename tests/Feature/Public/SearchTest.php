<?php

namespace Tests\Feature\Public;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function artist(string $name, string $slug, bool $published = true): Artist
    {
        return Artist::forceCreate([
            'name' => $name, 'slug' => $slug,
            'status' => $published ? 'published' : 'draft',
            'published_at' => $published ? now()->subDay() : null,
            'verification_status' => 'unverified',
        ]);
    }

    private function song(string $title, string $slug, array $overrides = [], ?Artist $artist = null): Song
    {
        $song = Song::forceCreate(array_merge([
            'title' => $title, 'slug' => $slug, 'status' => 'published',
            'published_at' => now()->subDay(), 'rights_status' => 'authorized',
            'verification_status' => 'unverified',
        ], $overrides));
        if ($artist) {
            $song->artists()->attach($artist->id, ['role' => 'primary']);
        }

        return $song;
    }

    public function test_empty_and_short_queries_show_guidance_and_are_noindex(): void
    {
        $this->get('/search')->assertOk()->assertSee('Type a song, artist or album name')
            ->assertSee('noindex', false);
        $this->get('/search?q=a')->assertOk()->assertSee('at least 2 characters');
    }

    public function test_finds_visible_songs_by_title_and_hides_everything_else(): void
    {
        $this->song('Umuriro Wanjye', 'umuriro-wanjye');
        $this->song('Umuriro Draft', 'umuriro-draft', ['status' => 'draft', 'published_at' => null]);
        $this->song('Umuriro Unlicensed', 'umuriro-unlicensed', ['rights_status' => 'unknown']);
        $this->song('Umuriro Future', 'umuriro-future', ['published_at' => now()->addDay()]);

        $this->get('/search?q=umuriro')->assertOk()
            ->assertSee('Umuriro Wanjye')
            ->assertDontSee('Umuriro Draft')
            ->assertDontSee('Umuriro Unlicensed')
            ->assertDontSee('Umuriro Future');
    }

    public function test_finds_songs_by_artist_name_and_alias(): void
    {
        $artist = $this->artist('Davis D', 'davis-d');
        $artist->aliases()->forceCreate(['alias' => 'Davis Dee']);
        $this->song('Hit One', 'hit-one', [], $artist);

        $this->get('/search?q=Davis D')->assertOk()->assertSee('Hit One');
        $this->get('/search?q=Davis Dee')->assertOk()->assertSee('Hit One');
    }

    public function test_artists_and_albums_are_found_only_when_published(): void
    {
        $this->artist('Visible Star', 'visible-star');
        $this->artist('Visible Hidden', 'visible-hidden', false);
        Album::forceCreate([
            'title' => 'Visible Album', 'slug' => 'visible-album', 'type' => 'album',
            'status' => 'published', 'published_at' => now()->subDay(),
        ]);

        $this->get('/search?q=visible')->assertOk()
            ->assertSee('Visible Star')->assertSee('Visible Album')
            ->assertDontSee('Visible Hidden');
    }

    public function test_exact_title_ranks_above_partial_matches(): void
    {
        $this->song('Big Love Song', 'big-love-song', ['published_at' => now()->subHour()]);
        $this->song('Love', 'love', ['published_at' => now()->subDays(5)]);

        $this->get('/search?q=love')->assertOk()->assertSeeInOrder(['Love', 'Big Love Song']);
    }

    public function test_wildcard_characters_are_treated_literally(): void
    {
        $this->song('Plain Title', 'plain-title');

        $this->get('/search?q=%25%25')->assertOk()->assertDontSee('Plain Title')->assertSee('No results');
        $this->get('/search?q=__')->assertOk()->assertDontSee('Plain Title');
    }

    public function test_very_long_queries_do_not_break_the_page(): void
    {
        $this->get('/search?q='.str_repeat('a', 500))->assertOk();
    }
}
