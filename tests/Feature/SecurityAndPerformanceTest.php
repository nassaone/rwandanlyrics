<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityAndPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_sent_on_pages_and_on_errors(): void
    {
        foreach (['/', '/no-such-page'] as $path) {
            $this->get($path)
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
                ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
                ->assertHeader('Content-Security-Policy', "base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'");
        }
    }

    public function test_hsts_is_only_sent_in_production_over_https(): void
    {
        $this->get('https://example.test/')->assertHeaderMissing('Strict-Transport-Security');

        config(['app.env' => 'production']);

        $this->get('https://example.test/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        $this->get('http://example.test/')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_public_pages_run_the_same_number_of_queries_however_much_data_there_is(): void
    {
        $album = Album::forceCreate(['title' => 'Big Album', 'slug' => 'big-album', 'type' => 'album', 'status' => 'published', 'published_at' => now()->subDay()]);
        $lead = Artist::forceCreate(['name' => 'Lead Artist', 'slug' => 'lead-artist', 'status' => 'published', 'published_at' => now()->subDay(), 'verification_status' => 'unverified']);
        $genre = Genre::forceCreate(['name' => 'Rock', 'slug' => 'rock']);

        $this->seedSongs(1, 3, $album, $lead, $genre);
        $small = $this->queryCounts();

        $this->seedSongs(4, 23, $album, $lead, $genre);
        $large = $this->queryCounts();

        foreach ($small as $url => $count) {
            $this->assertSame($count, $large[$url], "Possible N+1 on {$url}: {$count} queries with 3 songs, {$large[$url]} with 23.");
        }
    }

    private function seedSongs(int $from, int $to, Album $album, Artist $lead, Genre $genre): void
    {
        for ($i = $from; $i <= $to; $i++) {
            $artist = Artist::forceCreate(['name' => "Artist {$i}", 'slug' => "artist-{$i}", 'status' => 'published', 'published_at' => now()->subDay(), 'verification_status' => 'unverified']);
            $song = Song::forceCreate([
                'title' => "Song {$i}", 'slug' => "song-{$i}", 'status' => 'published', 'published_at' => now()->subMinutes($i),
                'rights_status' => 'authorized', 'verification_status' => 'unverified', 'album_id' => $album->id,
            ]);
            $song->artists()->attach($lead->id, ['role' => 'primary']);
            $song->artists()->attach($artist->id, ['role' => 'featured']);
            $song->genres()->attach($genre->id);
        }
    }

    private function queryCounts(): array
    {
        $counts = [];
        foreach (['/', '/songs', '/artists', '/genres/rock', '/artists/lead-artist', '/albums/big-album', '/songs/song-1', '/search?q=song', '/sitemaps/songs-1.xml', '/sitemaps/artists-1.xml'] as $url) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get($url)->assertOk();
            $counts[$url] = count(DB::getQueryLog());
        }
        DB::disableQueryLog();

        return $counts;
    }
}
