<?php

namespace Tests\Feature\Admin;

use App\Models\Artist;
use App\Models\Genre;
use App\Models\Redirect;
use App\Models\User;
use App\Support\Redirects;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::where('email', $role.'@example.test')->first() ?? User::forceCreate([
            'name' => ucfirst($role), 'email' => $role.'@example.test',
            'password' => bcrypt('password-123'), 'role' => $role,
        ]);
    }

    public function test_changing_a_published_artist_slug_creates_a_301_redirect(): void
    {
        $artist = Artist::forceCreate([
            'name' => 'Old Name', 'slug' => 'old-name', 'status' => 'published',
            'published_at' => now()->subDay(), 'verification_status' => 'unverified',
        ]);

        $this->actingAs($this->user('editor'))->put(route('admin.artists.update', $artist), [
            'name' => 'Old Name', 'slug' => 'new-name', 'status' => 'published', 'verification_status' => 'unverified',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('redirects', ['from_path' => '/artists/old-name', 'to_path' => '/artists/new-name']);
        $this->get('/artists/old-name')->assertStatus(301)->assertRedirect('/artists/new-name');
        $this->get('/artists/new-name')->assertOk();
    }

    public function test_changing_an_unpublished_artist_slug_creates_no_redirect(): void
    {
        $artist = Artist::forceCreate([
            'name' => 'Draft', 'slug' => 'draft', 'status' => 'draft', 'verification_status' => 'unverified',
        ]);

        $this->actingAs($this->user('editor'))->put(route('admin.artists.update', $artist), [
            'name' => 'Draft', 'slug' => 'draft-two', 'status' => 'draft', 'verification_status' => 'unverified',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, Redirect::count());
    }

    public function test_genre_slug_change_creates_a_redirect(): void
    {
        $genre = Genre::forceCreate(['name' => 'Afrobeat', 'slug' => 'afrobeat']);

        $this->actingAs($this->user('editor'))->put(route('admin.genres.update', $genre), [
            'name' => 'Afrobeat', 'slug' => 'afro-beat',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('redirects', ['from_path' => '/genres/afrobeat', 'to_path' => '/genres/afro-beat']);
    }

    public function test_chains_collapse_to_one_hop(): void
    {
        Redirects::record('/artists/a', '/artists/b');
        Redirects::record('/artists/b', '/artists/c');

        $this->assertDatabaseHas('redirects', ['from_path' => '/artists/a', 'to_path' => '/artists/c']);
        $this->assertDatabaseHas('redirects', ['from_path' => '/artists/b', 'to_path' => '/artists/c']);
    }

    public function test_reverting_a_slug_removes_the_looping_redirect(): void
    {
        Redirects::record('/artists/a', '/artists/b');
        Redirects::record('/artists/b', '/artists/a');

        $this->assertDatabaseMissing('redirects', ['from_path' => '/artists/a']);
        $this->assertDatabaseHas('redirects', ['from_path' => '/artists/b', 'to_path' => '/artists/a']);
    }

    public function test_manual_redirect_works_and_is_validated(): void
    {
        $editor = $this->user('editor');

        $this->actingAs($editor)->post(route('admin.redirects.store'), ['from_path' => '/old-page', 'to_path' => '/songs'])
            ->assertSessionHasNoErrors();
        $this->get('/old-page')->assertStatus(301)->assertRedirect('/songs');

        $this->actingAs($editor)->post(route('admin.redirects.store'), ['from_path' => '/admin/secret', 'to_path' => '/songs'])
            ->assertSessionHasErrors('from_path');
        $this->actingAs($editor)->post(route('admin.redirects.store'), ['from_path' => '/same', 'to_path' => '/same'])
            ->assertSessionHasErrors();
        $this->actingAs($editor)->post(route('admin.redirects.store'), ['from_path' => '/old-page', 'to_path' => '/artists'])
            ->assertSessionHasErrors('from_path');
        $this->actingAs($editor)->post(route('admin.redirects.store'), ['from_path' => 'https://evil.test', 'to_path' => '/songs'])
            ->assertSessionHasErrors('from_path');
    }

    public function test_only_admins_can_delete_redirects(): void
    {
        $redirect = Redirect::forceCreate(['from_path' => '/x', 'to_path' => '/y', 'status_code' => 301]);

        $this->actingAs($this->user('editor'))->delete(route('admin.redirects.destroy', $redirect))->assertForbidden();
        $this->actingAs($this->user('admin'))->delete(route('admin.redirects.destroy', $redirect))->assertRedirect();
        $this->assertSame(0, Redirect::count());
    }

    public function test_unknown_paths_still_return_404(): void
    {
        $this->get('/no-such-page')->assertNotFound();
    }
}
