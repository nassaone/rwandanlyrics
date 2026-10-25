<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SongYouTubeTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private Artist $artist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->editor = User::factory()->role(Role::Editor)->create();
        $this->artist = Artist::factory()->published()->create();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Video Song',
            'primary_artist_id' => $this->artist->id,
            'status' => 'draft',
            'verification_status' => 'unverified',
            'rights_status' => 'unknown',
        ], $override);
    }

    public function test_saving_a_link_stores_only_the_video_id(): void
    {
        $this->actingAs($this->editor)
            ->post(route('admin.songs.store'), $this->payload(['youtube_url' => 'https://youtu.be/dQw4w9WgXcQ?t=10']))
            ->assertSessionHasNoErrors();

        $this->assertSame('dQw4w9WgXcQ', Song::latest('id')->firstOrFail()->youtube_id);
    }

    public function test_a_link_that_is_not_youtube_is_rejected(): void
    {
        $this->actingAs($this->editor)
            ->post(route('admin.songs.store'), $this->payload(['youtube_url' => 'https://vimeo.com/123456']))
            ->assertSessionHasErrors('youtube_url');

        $this->assertSame(0, Song::count());
    }

    public function test_a_blank_link_is_allowed_and_stores_nothing(): void
    {
        $this->actingAs($this->editor)
            ->post(route('admin.songs.store'), $this->payload(['youtube_url' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull(Song::latest('id')->firstOrFail()->youtube_id);
    }

    public function test_clearing_the_link_removes_the_video(): void
    {
        $song = Song::factory()->create(['youtube_id' => 'dQw4w9WgXcQ']);

        $this->actingAs($this->editor)
            ->put(route('admin.songs.update', $song), $this->payload(['title' => $song->title, 'youtube_url' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull($song->fresh()->youtube_id);
    }

    public function test_saving_without_the_field_keeps_the_existing_video(): void
    {
        $song = Song::factory()->create(['youtube_id' => 'dQw4w9WgXcQ']);

        $this->actingAs($this->editor)
            ->put(route('admin.songs.update', $song), $this->payload(['title' => $song->title]))
            ->assertSessionHasNoErrors();

        $this->assertSame('dQw4w9WgXcQ', $song->fresh()->youtube_id);
    }

    public function test_the_song_page_shows_the_player_only_when_a_video_is_set(): void
    {
        $with = Song::factory()->published()->create(['youtube_id' => 'dQw4w9WgXcQ']);
        $without = Song::factory()->published()->create();

        foreach ([$with, $without] as $song) {
            $song->artists()->attach($this->artist->id, ['role' => 'primary', 'position' => 0]);
        }

        $this->get(route('songs.show', $with->slug))
            ->assertOk()
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('https://www.youtube.com/watch?v=dQw4w9WgXcQ', false);

        $this->get(route('songs.show', $without->slug))
            ->assertOk()
            ->assertDontSee('youtube-nocookie.com', false)
            ->assertDontSee('Open on YouTube');
    }
}
