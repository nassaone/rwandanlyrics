<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Lyric;
use App\Models\Song;
use App\Models\User;
use App\Services\LyricsQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextSongWithoutLyricsTest extends TestCase
{
    use RefreshDatabase;

    private function song(string $title, bool $lyrics): Song
    {
        $song = Song::factory()->create(['title' => $title]);
        if ($lyrics) {
            Lyric::factory()->for($song)->create();
        }

        return $song;
    }

    public function test_next_skips_songs_that_have_lyrics_and_wraps_around(): void
    {
        $a = $this->song('A', false);
        $b = $this->song('B', true);
        $c = $this->song('C', false);
        $queue = app(LyricsQueue::class);

        $this->assertSame($c->id, $queue->next($a)->id);
        $this->assertSame($a->id, $queue->next($c)->id);
        $this->assertSame(2, $queue->remaining());
    }

    public function test_next_never_returns_the_same_song_and_is_null_when_done(): void
    {
        $only = $this->song('Only', false);
        $queue = app(LyricsQueue::class);

        $this->assertNull($queue->next($only));

        Lyric::factory()->for($only)->create();
        $other = $this->song('Other', true);

        $this->assertNull($queue->next($other));
        $this->assertSame(0, $queue->remaining());
    }

    public function test_editor_shows_the_counter_and_the_next_button(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => Role::Admin])->save();
        $a = $this->song('Needs One', false);
        $this->song('Needs Two', false);

        $this->actingAs($admin)->get(route('admin.songs.edit', $a))->assertOk()
            ->assertSee('2 songs still need lyrics')
            ->assertSee('Save and open next song without lyrics');
    }

    public function test_the_list_filter_shows_only_songs_without_lyrics(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => Role::Admin])->save();
        $this->song('Needs Words', false);
        $this->song('Has Words', true);

        $this->actingAs($admin)->get(route('admin.songs.index', ['filter' => 'needs_lyrics']))->assertOk()
            ->assertSee('Needs Words')->assertDontSee('Has Words');
    }
}
