<?php

namespace Tests\Feature\Admin;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_scheduled_content_is_published_and_future_content_is_left_alone(): void
    {
        $due = Song::factory()->create(['status' => 'scheduled', 'published_at' => now()->subMinute(), 'rights_status' => 'authorized']);
        $later = Song::factory()->create(['status' => 'scheduled', 'published_at' => now()->addHour(), 'rights_status' => 'authorized']);
        $artist = Artist::factory()->create(['status' => 'scheduled', 'published_at' => now()->subMinute()]);
        $album = Album::factory()->create(['status' => 'scheduled', 'published_at' => now()->subMinute()]);

        $this->artisan('rwandanlyrics:publish-scheduled')->assertExitCode(0);

        $this->assertSame('published', $due->fresh()->status->value);
        $this->assertSame('scheduled', $later->fresh()->status->value);
        $this->assertSame('published', $artist->fresh()->status->value);
        $this->assertSame('published', $album->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.scheduled_published', 'subject_id' => $due->id, 'user_id' => null]);
        $this->get(route('songs.show', $due->slug))->assertOk();
        $this->get(route('songs.show', $later->slug))->assertNotFound();
    }
}
