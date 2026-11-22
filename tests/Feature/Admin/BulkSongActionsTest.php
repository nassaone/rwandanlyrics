<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Lyric;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkSongActionsTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(Role $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function draft(string $title, bool $lyrics, string $status = 'draft'): Song
    {
        $song = Song::factory()->create(['title' => $title, 'status' => $status, 'rights_status' => 'unknown', 'published_at' => null]);
        if ($lyrics) {
            Lyric::factory()->for($song)->create();
        }

        return $song;
    }

    public function test_publish_and_authorize_also_publishes_songs_without_lyrics(): void
    {
        $with = $this->draft('With Words', true);
        $without = $this->draft('No Words', false);

        $this->actingAs($this->userWith(Role::Admin))
            ->post(route('admin.songs.bulk'), ['action' => 'publish', 'scope' => 'page', 'ids' => [$with->id, $without->id]])
            ->assertRedirect();

        $with->refresh();
        $this->assertSame('published', $with->status->value);
        $this->assertSame('authorized', $with->rights_status->value);
        $this->assertNotNull($with->published_at);
        $this->assertSame('published', $without->refresh()->status->value);
        $this->assertSame(2, Song::publiclyVisible()->count());
    }

    public function test_editors_cannot_use_bulk_actions(): void
    {
        $song = $this->draft('With Words', true);

        $this->actingAs($this->userWith(Role::Editor))
            ->post(route('admin.songs.bulk'), ['action' => 'publish', 'scope' => 'page', 'ids' => [$song->id]])
            ->assertForbidden();

        $this->assertSame('draft', $song->refresh()->status->value);
    }

    public function test_apply_to_all_matching_respects_the_filter(): void
    {
        $a = $this->draft('Draft A', true);
        $b = $this->draft('Draft B', true);
        $archived = $this->draft('Archived', true, 'archived');

        $this->actingAs($this->userWith(Role::Admin))
            ->post(route('admin.songs.bulk'), ['action' => 'publish', 'scope' => 'all', 'status' => 'draft'])
            ->assertRedirect();

        $this->assertSame('published', $a->refresh()->status->value);
        $this->assertSame('published', $b->refresh()->status->value);
        $this->assertSame('archived', $archived->refresh()->status->value);
    }

    public function test_move_to_draft_unpublishes(): void
    {
        $song = $this->draft('Live One', true);
        $song->update(['status' => 'published', 'rights_status' => 'authorized', 'published_at' => now()->subDay()]);

        $this->actingAs($this->userWith(Role::Admin))
            ->post(route('admin.songs.bulk'), ['action' => 'unpublish', 'scope' => 'page', 'ids' => [$song->id]]);

        $this->assertSame('draft', $song->refresh()->status->value);
    }

    public function test_nothing_ticked_changes_nothing(): void
    {
        $song = $this->draft('With Words', true);

        $this->actingAs($this->userWith(Role::Admin))
            ->post(route('admin.songs.bulk'), ['action' => 'publish', 'scope' => 'page'])
            ->assertSessionHas('status', 'Tick at least one song first.');

        $this->assertSame('draft', $song->refresh()->status->value);
    }
}
