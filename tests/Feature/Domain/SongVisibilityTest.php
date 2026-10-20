<?php

namespace Tests\Feature\Domain;

use App\Enums\PublicationStatus;
use App\Enums\RightsStatus;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SongVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_live_and_authorized_songs_are_publicly_visible(): void
    {
        Song::factory()->published()->create(['title' => 'Good']);
        Song::factory()->create(['title' => 'Draft']);
        Song::factory()->published()->create(['title' => 'Unknown rights', 'rights_status' => RightsStatus::Unknown]);
        Song::factory()->published()->create(['title' => 'Restricted', 'rights_status' => RightsStatus::Restricted]);
        Song::factory()->published()->create(['title' => 'Removed', 'rights_status' => RightsStatus::Removed]);
        Song::factory()->published()->create(['title' => 'Future', 'published_at' => now()->addDay()]);
        Song::factory()->published()->create(['title' => 'Scheduled', 'status' => PublicationStatus::Scheduled]);
        Song::factory()->published()->create(['title' => 'Archived', 'status' => PublicationStatus::Archived]);

        $this->assertSame(['Good'], Song::publiclyVisible()->pluck('title')->all());
    }
}
