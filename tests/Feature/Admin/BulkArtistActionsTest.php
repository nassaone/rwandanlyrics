<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkArtistActionsTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(Role $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function artist(string $name, string $status = 'draft'): Artist
    {
        return Artist::factory()->create(['name' => $name, 'status' => $status, 'published_at' => null]);
    }

    public function test_publish_ticked_artists(): void
    {
        $a = $this->artist('Artist A');
        $b = $this->artist('Artist B');
        $untouched = $this->artist('Artist C');

        $this->actingAs($this->userWith(Role::Admin))
            ->post(route('admin.artists.bulk'), ['action' => 'publish', 'scope' => 'page', 'ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertSame('published', $a->refresh()->status->value);
        $this->assertNotNull($a->published_at);
        $this->assertSame('published', $b->refresh()->status->value);
        $this->assertSame('draft', $untouched->refresh()->status->value);
    }

    public function test_editors_cannot_use_bulk_actions(): void
    {
        $a = $this->artist('Artist A');

        $this->actingAs($this->userWith(Role::Editor))
            ->post(route('admin.artists.bulk'), ['action' => 'publish', 'scope' => 'page', 'ids' => [$a->id]])
            ->assertForbidden();

        $this->assertSame('draft', $a->refresh()->status->value);
    }

    public function test_apply_to_all_matching_respects_the_filter(): void
    {
        $a = $this->artist('Draft One');
        $b = $this->artist('Draft Two');
        $archived = $this->artist('Archived One', 'archived');

        $this->actingAs($this->userWith(Role::Admin))
            ->post(route('admin.artists.bulk'), ['action' => 'publish', 'scope' => 'all', 'status' => 'draft'])
            ->assertRedirect();

        $this->assertSame('published', $a->refresh()->status->value);
        $this->assertSame('published', $b->refresh()->status->value);
        $this->assertSame('archived', $archived->refresh()->status->value);
    }

    public function test_move_to_draft_and_empty_selection(): void
    {
        $live = $this->artist('Live One', 'published');
        $admin = $this->userWith(Role::Admin);

        $this->actingAs($admin)
            ->post(route('admin.artists.bulk'), ['action' => 'unpublish', 'scope' => 'page', 'ids' => [$live->id]]);
        $this->assertSame('draft', $live->refresh()->status->value);

        $this->actingAs($admin)
            ->post(route('admin.artists.bulk'), ['action' => 'publish', 'scope' => 'page'])
            ->assertSessionHas('status', 'Tick at least one artist first.');
    }

    public function test_the_list_shows_bulk_controls_to_admins(): void
    {
        $this->artist('Artist A');

        $this->actingAs($this->userWith(Role::Admin))
            ->get(route('admin.artists.index'))->assertOk()->assertSee('Select all on this page');
    }
}
