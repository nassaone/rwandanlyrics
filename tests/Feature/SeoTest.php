<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function artist(string $name, string $slug): Artist
    {
        return Artist::forceCreate([
            'name' => $name, 'slug' => $slug, 'status' => 'published',
            'published_at' => now()->subDay(), 'verification_status' => 'unverified',
        ]);
    }

    private function song(string $title, string $slug, array $o = [], ?Artist $artist = null): Song
    {
        $song = Song::forceCreate(array_merge([
            'title' => $title, 'slug' => $slug, 'status' => 'published', 'published_at' => now()->subDay(),
            'rights_status' => 'authorized', 'verification_status' => 'unverified',
        ], $o));
        if ($artist) {
            $song->artists()->attach($artist->id, ['role' => 'primary']);
        }

        return $song;
    }

    private function album(string $title, string $slug): Album
    {
        return Album::forceCreate([
            'title' => $title, 'slug' => $slug, 'type' => 'album',
            'status' => 'published', 'published_at' => now()->subDay(),
        ]);
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /', false)->assertDontSee('Sitemap:');
    }

    public function test_robots_in_production_hides_private_areas_and_lists_the_sitemap(): void
    {
        config(['app.env' => 'production']);

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin', false)->assertSee('Disallow: /search', false)
            ->assertSee('Sitemap: ', false)->assertSee('/sitemap.xml', false);
    }

    public function test_sitemap_lists_only_public_indexable_songs(): void
    {
        $this->song('Visible Song', 'visible-song');
        $this->song('Draft Song', 'draft-song', ['status' => 'draft', 'published_at' => null]);
        $this->song('Unlicensed Song', 'unlicensed-song', ['rights_status' => 'unknown']);
        $this->song('Future Song', 'future-song', ['published_at' => now()->addDay()]);
        $this->song('Noindex Song', 'noindex-song', ['noindex' => true]);

        $this->get('/sitemap.xml')->assertOk()->assertSee('/sitemaps/songs-1.xml', false);

        $this->get('/sitemaps/songs-1.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/songs/visible-song', false)
            ->assertDontSee('draft-song')->assertDontSee('unlicensed-song')
            ->assertDontSee('future-song')->assertDontSee('noindex-song');
    }

    public function test_artists_and_albums_need_a_visible_song_to_be_in_the_sitemap(): void
    {
        $withSong = $this->artist('Has Songs', 'has-songs');
        $this->artist('No Songs', 'no-songs');
        $album = $this->album('Full Album', 'full-album');
        $this->album('Empty Album', 'empty-album');
        $this->song('Track', 'track', ['album_id' => $album->id], $withSong);

        $this->get('/sitemaps/artists-1.xml')->assertOk()->assertSee('/artists/has-songs', false)->assertDontSee('no-songs');
        $this->get('/sitemaps/albums-1.xml')->assertOk()->assertSee('/albums/full-album', false)->assertDontSee('empty-album');
    }

    public function test_unknown_sitemaps_are_404(): void
    {
        $this->get('/sitemaps/secrets-1.xml')->assertNotFound();
        $this->get('/sitemaps/songs-9.xml')->assertNotFound();
    }

    public function test_artist_page_without_visible_songs_is_noindex_but_still_viewable(): void
    {
        $empty = $this->artist('Empty One', 'empty-one');
        $full = $this->artist('Full One', 'full-one');
        $this->song('Hit', 'hit', [], $full);

        $this->get('/artists/empty-one')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow');
        $this->get('/artists/full-one')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_album_page_without_visible_songs_is_noindex(): void
    {
        $empty = $this->album('Empty Album', 'empty-album');
        $full = $this->album('Full Album', 'full-album');
        $this->song('Track', 'track', ['album_id' => $full->id]);

        $this->get('/albums/empty-album')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow');
        $this->get('/albums/full-album')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_song_page_has_structured_data_without_lyrics_and_a_generated_description(): void
    {
        $artist = $this->artist('Star Name', 'star-name');
        $song = $this->song('Structured Song', 'structured-song', [], $artist);
        $song->lyrics()->create(['body' => 'SECRETLYRICTEXT line', 'revision_number' => 1, 'is_current' => true]);

        $html = $this->get('/songs/structured-song')->assertOk()
            ->assertSee('<meta name="description" content="Lyrics of Structured Song by Star Name on', false)
            ->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
        $this->assertNotEmpty($m, 'JSON-LD block missing');
        $data = json_decode($m[1], true);
        $this->assertSame('MusicRecording', $data['@type']);
        $this->assertSame('Star Name', $data['byArtist'][0]['name']);
        $this->assertStringNotContainsString('SECRETLYRICTEXT', $m[1]);
    }

    public function test_editor_written_meta_description_wins(): void
    {
        $this->song('Custom Meta', 'custom-meta', ['seo_description' => 'A hand-written description & more.']);

        $this->get('/songs/custom-meta')->assertOk()
            ->assertSee('content="A hand-written description &amp; more."', false);
    }

    public function test_home_page_has_website_structured_data_with_search_action(): void
    {
        $this->get('/')->assertOk()->assertSee('"@type":"WebSite"', false)->assertSee('search_term_string', false);
    }
}
