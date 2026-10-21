<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login_and_contributors_are_refused(): void
    {
        foreach (['/admin/songs', '/admin/artists', '/admin/albums', '/admin/genres', '/admin/languages', '/admin/songs/create'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
            $this->actingAs(User::factory()->role(Role::Contributor)->create())->get($path)->assertForbidden();
            auth()->logout();
        }
    }

    public function test_contributors_cannot_write(): void
    {
        $contributor = User::factory()->role(Role::Contributor)->create();
        $artist = Artist::factory()->create();

        $this->actingAs($contributor)->post(route('admin.songs.store'), [
            'title' => 'Nope', 'primary_artist_id' => $artist->id, 'status' => 'published',
            'verification_status' => 'unverified', 'rights_status' => 'authorized',
        ])->assertForbidden();

        $this->assertDatabaseCount('songs', 0);
    }

    public function test_editors_can_open_every_admin_screen(): void
    {
        $editor = User::factory()->role(Role::Editor)->create();
        $song = Song::factory()->create();
        $artist = Artist::factory()->create();
        $genre = Genre::factory()->create();

        foreach ([
            route('admin.dashboard'), route('admin.songs.index'), route('admin.songs.create'), route('admin.songs.edit', $song),
            route('admin.artists.index'), route('admin.artists.create'), route('admin.artists.edit', $artist),
            route('admin.albums.index'), route('admin.albums.create'),
            route('admin.genres.index'), route('admin.genres.edit', $genre), route('admin.languages.index'),
        ] as $url) {
            $this->actingAs($editor)->get($url)->assertOk()->assertSee('content="noindex,nofollow"', false);
        }
    }

    public function test_audit_log_is_for_admins_only(): void
    {
        $this->actingAs(User::factory()->role(Role::Editor)->create())->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Admin)->create())->get(route('admin.audit.index'))->assertOk();
    }
}
