<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Album;
use App\Models\Artist;
use App\Models\ArtistAlias;
use App\Models\Genre;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->editor = User::factory()->role(Role::Editor)->create();
    }

    private function artistPayload(array $override = []): array
    {
        return array_merge(['name' => 'New Artist', 'status' => 'draft', 'verification_status' => 'unverified'], $override);
    }

    public function test_artist_is_created_with_deduplicated_aliases(): void
    {
        $this->actingAs($this->editor)->post(route('admin.artists.store'), $this->artistPayload([
            'aliases' => "Alias One\nalias one\n\nAlias Two\nNew Artist",
            'country_code' => 'rw',
        ]))->assertSessionHasNoErrors();

        $artist = Artist::firstOrFail();
        $this->assertSame('new-artist', $artist->slug);
        $this->assertSame('RW', $artist->country_code);
        $this->assertEqualsCanonicalizing(['Alias One', 'Alias Two'], $artist->aliases->pluck('alias')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'artist.created', 'subject_id' => $artist->id]);
    }

    public function test_duplicate_artist_names_and_alias_conflicts_are_rejected(): void
    {
        $existing = Artist::factory()->create(['name' => 'Demo Artist']);
        $other = Artist::factory()->create(['name' => 'Someone Else']);
        ArtistAlias::create(['artist_id' => $other->id, 'alias' => 'Stage Name']);

        $this->actingAs($this->editor)->post(route('admin.artists.store'), $this->artistPayload(['name' => 'DEMO  artist']))
            ->assertSessionHasErrors('name');
        $this->actingAs($this->editor)->post(route('admin.artists.store'), $this->artistPayload(['name' => 'Stage-Name']))
            ->assertSessionHasErrors('name');

        $this->actingAs($this->editor)->put(route('admin.artists.update', $existing), $this->artistPayload(['name' => 'Demo Artist']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('artists', 2);
    }

    public function test_album_requires_an_artist_and_syncs_artists(): void
    {
        $artist = Artist::factory()->create();

        $this->actingAs($this->editor)->post(route('admin.albums.store'), ['title' => 'No Artist', 'type' => 'album', 'status' => 'draft'])
            ->assertSessionHasErrors('artist_ids');

        $this->actingAs($this->editor)->post(route('admin.albums.store'), [
            'title' => 'Good Album', 'type' => 'ep', 'status' => 'draft', 'artist_ids' => [$artist->id],
        ])->assertSessionHasNoErrors();

        $album = Album::firstOrFail();
        $this->assertSame('good-album', $album->slug);
        $this->assertSame('ep', $album->type->value);
        $this->assertCount(1, $album->artists);
        $this->assertDatabaseHas('audit_logs', ['action' => 'album.created', 'subject_id' => $album->id]);
    }

    public function test_languages_need_unique_names_and_codes(): void
    {
        $this->actingAs($this->editor)->post(route('admin.languages.store'), ['name' => 'Kinyarwanda', 'code' => 'rw'])->assertSessionHasNoErrors();
        $this->actingAs($this->editor)->post(route('admin.languages.store'), ['name' => 'Kinyarwanda'])->assertSessionHasErrors('name');
        $this->actingAs($this->editor)->post(route('admin.languages.store'), ['name' => 'Other', 'code' => 'rw'])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('languages', 1);
    }

    public function test_only_admins_can_delete_catalog_records(): void
    {
        $genre = Genre::factory()->create();
        $language = Language::factory()->create();
        $album = Album::factory()->create();
        $artist = Artist::factory()->create();
        $admin = User::factory()->role(Role::Admin)->create();

        foreach ([
            route('admin.genres.destroy', $genre), route('admin.languages.destroy', $language),
            route('admin.albums.destroy', $album), route('admin.artists.destroy', $artist),
        ] as $url) {
            $this->actingAs($this->editor)->delete($url)->assertForbidden();
            $this->actingAs($admin)->delete($url)->assertRedirect();
        }

        $this->assertDatabaseCount('genres', 0);
        $this->assertDatabaseCount('languages', 0);
        $this->assertDatabaseCount('albums', 0);
        $this->assertDatabaseCount('artists', 0);
    }

    public function test_audit_log_page_lists_activity_for_admins(): void
    {
        $this->actingAs($this->editor)->post(route('admin.genres.store'), ['name' => 'Logged Genre']);

        $this->actingAs(User::factory()->role(Role::Admin)->create())->get(route('admin.audit.index'))
            ->assertOk()->assertSee('genre.created')->assertSee('Logged Genre');
    }
}
