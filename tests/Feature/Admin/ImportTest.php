<?php

namespace Tests\Feature\Admin;

use App\Enums\PublicationStatus;
use App\Enums\Role;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function admin(): User
    {
        return User::factory()->role(Role::from('super_admin'))->create();
    }

    private function preview(User $user, string $csv)
    {
        return $this->actingAs($user)->post(route('admin.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('songs.csv', $csv),
        ]);
    }

    private function importCsv(User $user, string $csv): void
    {
        $response = $this->preview($user, $csv);
        $response->assertOk();
        $this->actingAs($user)->post(route('admin.import.commit'), ['token' => $response->viewData('token')])
            ->assertRedirect(route('admin.import.index'));
    }

    private const CSV = "artist,title,youtube_url,genre,featured_artists,release_date\n"
        ."Meddy,Song One,https://youtu.be/dQw4w9WgXcQ,Afrobeats;RnB,The Ben,2024-05-01\n"
        ."Meddy,Song Two,,,,\n"
        ."Bruce Melodie,,,,,\n";

    public function test_guests_and_editors_cannot_use_the_importer(): void
    {
        $this->get(route('admin.import.index'))->assertRedirect();
        $this->actingAs(User::factory()->role(Role::Editor)->create())
            ->get(route('admin.import.index'))->assertForbidden();
    }

    public function test_preview_shows_counts_and_saves_nothing(): void
    {
        $response = $this->preview($this->admin(), self::CSV);

        $response->assertOk()->assertSee('Preview import');
        $summary = $response->viewData('plan')['summary'];
        $this->assertSame(2, $summary['songs']);
        $this->assertSame(3, $summary['new_artists']);
        $this->assertSame(0, Artist::count());
        $this->assertSame(0, Song::count());
    }

    public function test_commit_creates_drafts_and_never_publishes(): void
    {
        $this->importCsv($this->admin(), self::CSV);

        $this->assertSame(3, Artist::count());
        $this->assertSame(2, Song::count());
        $this->assertSame(2, Genre::count());
        $this->assertSame(0, Artist::where('status', '!=', PublicationStatus::Draft->value)->count());
        $this->assertSame(0, Song::where('status', '!=', PublicationStatus::Draft->value)->count());
        $this->assertSame(2, Song::where('rights_status', 'unknown')->count());

        $one = Song::where('title', 'Song One')->firstOrFail();
        $this->assertSame('dQw4w9WgXcQ', $one->youtube_id);
        $this->assertEqualsCanonicalizing(['Meddy', 'The Ben'], $one->artists->pluck('name')->all());
    }

    public function test_running_the_same_file_twice_creates_no_duplicates(): void
    {
        $admin = $this->admin();
        $this->importCsv($admin, self::CSV);
        $this->importCsv($admin, self::CSV);

        $this->assertSame(3, Artist::count());
        $this->assertSame(2, Song::count());
    }

    public function test_existing_artists_are_reused(): void
    {
        Artist::factory()->create(['name' => 'Meddy']);

        $this->importCsv($this->admin(), "artist,title\nMeddy,New Song\n");

        $this->assertSame(1, Artist::count());
        $this->assertSame(1, Artist::where('name', 'Meddy')->firstOrFail()->songs()->count());
    }

    public function test_invalid_youtube_link_is_an_error_and_the_row_is_not_imported(): void
    {
        $admin = $this->admin();
        $response = $this->preview($admin, "artist,title,youtube_url\nMeddy,Bad Link,https://vimeo.com/123\nMeddy,Good,\n");

        $this->assertSame(1, $response->viewData('plan')['summary']['errors']);

        $this->actingAs($admin)->post(route('admin.import.commit'), ['token' => $response->viewData('token')]);
        $this->assertSame(['Good'], Song::pluck('title')->all());
    }

    public function test_file_without_an_artist_column_is_rejected(): void
    {
        $this->preview($this->admin(), "name,x\nA,b\n")->assertSessionHasErrors('file');
    }

    public function test_semicolon_files_with_a_bom_are_read(): void
    {
        $this->importCsv($this->admin(), "\xEF\xBB\xBFartist;title\nTest Artist;Test Song\n");

        $this->assertSame(1, Song::where('title', 'Test Song')->count());
    }

    public function test_lyrics_column_creates_a_current_revision(): void
    {
        $this->importCsv($this->admin(), "artist,title,lyrics\nMeddy,With Words,\"Line one\nLine two\"\n");

        $song = Song::where('title', 'With Words')->firstOrFail();
        $this->assertSame("Line one\nLine two", $song->lyrics()->where('is_current', true)->value('body'));
    }

    public function test_files_with_too_many_rows_are_rejected(): void
    {
        $csv = "artist,title\n".str_repeat("A,T\n", 501);

        $this->preview($this->admin(), $csv)->assertSessionHasErrors('file');
    }

    public function test_someone_elses_token_is_refused(): void
    {
        $response = $this->preview($this->admin(), self::CSV);
        $other = User::factory()->role(Role::from('super_admin'))->create();

        $this->actingAs($other)->post(route('admin.import.commit'), ['token' => $response->viewData('token')])
            ->assertForbidden();
    }

    public function test_templates_download(): void
    {
        $admin = $this->admin();

        $songs = $this->actingAs($admin)->get(route('admin.import.template', 'songs'))->assertOk();
        $this->assertStringContainsString('artist,title,youtube_url', $songs->streamedContent());

        $artists = $this->actingAs($admin)->get(route('admin.import.template', 'artists'))->assertOk();
        $this->assertStringContainsString('"Bruce Melodie",RW', $artists->streamedContent());
    }
}
