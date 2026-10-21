<?php

namespace Tests\Feature\Admin;

use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtistMergeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::where('email', $role.'@example.test')->first() ?? User::forceCreate([
            'name' => ucfirst($role), 'email' => $role.'@example.test',
            'password' => bcrypt('password-123'), 'role' => $role,
        ]);
    }

    private function artist(string $name, string $slug): Artist
    {
        return Artist::forceCreate([
            'name' => $name, 'slug' => $slug, 'status' => 'published',
            'published_at' => now()->subDay(), 'verification_status' => 'unverified',
        ]);
    }

    public function test_admin_merges_a_duplicate_into_another_artist(): void
    {
        $dup = $this->artist('Duplicate Name', 'duplicate-name');
        $keep = $this->artist('Real Name', 'real-name');
        $song = Song::forceCreate([
            'title' => 'A Song', 'slug' => 'a-song', 'status' => 'draft',
            'rights_status' => 'unknown', 'verification_status' => 'unverified',
        ]);
        $song->artists()->attach($dup->id, ['role' => 'primary']);

        $this->actingAs($this->user('admin'))
            ->post(route('admin.artists.merge', $dup), ['target_id' => $keep->id])
            ->assertSessionHasNoErrors();

        $this->assertNull(Artist::find($dup->id));
        $this->assertTrue($song->fresh()->artists->contains('id', $keep->id));
        $this->assertContains('Duplicate Name', $keep->fresh()->aliases->pluck('alias')->all());
        $this->assertDatabaseHas('redirects', ['from_path' => '/artists/duplicate-name', 'to_path' => '/artists/real-name']);
        $this->get('/artists/duplicate-name')->assertStatus(301)->assertRedirect('/artists/real-name');
    }

    public function test_editors_cannot_merge(): void
    {
        $dup = $this->artist('One', 'one');
        $keep = $this->artist('Two', 'two');

        $this->actingAs($this->user('editor'))
            ->post(route('admin.artists.merge', $dup), ['target_id' => $keep->id])
            ->assertForbidden();

        $this->assertNotNull(Artist::find($dup->id));
    }

    public function test_cannot_merge_an_artist_into_itself(): void
    {
        $a = $this->artist('One', 'one');

        $this->actingAs($this->user('admin'))
            ->post(route('admin.artists.merge', $a), ['target_id' => $a->id])
            ->assertSessionHasErrors();

        $this->assertNotNull(Artist::find($a->id));
    }
}
