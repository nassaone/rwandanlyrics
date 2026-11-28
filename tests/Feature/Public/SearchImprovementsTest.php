<?php

namespace Tests\Feature\Public;

use App\Models\Artist;
use App\Models\Lyric;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private function artist(string $name, string $slug): Artist
    {
        return Artist::forceCreate(['name' => $name, 'slug' => $slug, 'status' => 'published', 'published_at' => now()->subDay(), 'verification_status' => 'unverified']);
    }

    private function song(string $title, string $slug, ?Artist $artist = null, array $overrides = []): Song
    {
        $song = Song::forceCreate(array_merge([
            'title' => $title, 'slug' => $slug, 'status' => 'published', 'published_at' => now()->subDay(),
            'rights_status' => 'authorized', 'verification_status' => 'unverified',
        ], $overrides));
        if ($artist) {
            $song->artists()->attach($artist->id, ['role' => 'primary']);
        }

        return $song;
    }

    public function test_words_can_be_in_any_order_across_title_and_artist(): void
    {
        $this->song('Detail', 'detail', $this->artist('Bruce Melodie', 'bruce-melodie'));

        $this->get('/search?q=melodie detail')->assertOk()->assertSee('Detail');
        $this->get('/search?q=detail bruce')->assertOk()->assertSee('Detail');
    }

    public function test_a_line_from_the_lyrics_finds_the_song_and_hides_non_public_ones(): void
    {
        $live = $this->song('Hidden Words', 'hidden-words');
        Lyric::factory()->for($live)->create(['body' => "Nta kintu nkunda kurusha wowe\nSecond line"]);
        $draft = $this->song('Draft Words', 'draft-words', null, ['status' => 'draft', 'published_at' => null]);
        Lyric::factory()->for($draft)->create(['body' => 'Nta kintu nkunda kurusha wowe']);

        $this->get('/search?q=kintu nkunda')->assertOk()
            ->assertSee('Found in lyrics')->assertSee('Hidden Words')->assertSee('kintu nkunda')
            ->assertDontSee('Draft Words');
    }

    public function test_a_misspelling_gets_a_did_you_mean_for_live_items_only(): void
    {
        $this->song('Inshallah', 'inshallah');
        $this->song('Hidden Gem', 'hidden-gem', null, ['status' => 'draft', 'published_at' => null]);

        $this->get('/search?q=Inshalah')->assertOk()->assertSee('Did you mean')->assertSee('Inshallah');
        $this->get('/search?q=Hiden Gem')->assertOk()->assertDontSee('Did you mean');
    }

    public function test_suggestions_endpoint_returns_songs_and_artists(): void
    {
        $this->song('Inshallah', 'inshallah', $this->artist('The Ben', 'the-ben'));

        $this->getJson('/search/suggest?q=inshal')->assertOk()
            ->assertJsonPath('songs.0.title', 'Inshallah')
            ->assertJsonPath('songs.0.artist', 'The Ben');
        $this->getJson('/search/suggest?q=the b')->assertOk()->assertJsonPath('artists.0.name', 'The Ben');
        $this->getJson('/search/suggest?q=a')->assertOk()->assertExactJson(['songs' => [], 'artists' => []]);
    }
}
