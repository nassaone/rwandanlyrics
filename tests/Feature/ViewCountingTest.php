<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Services\ViewStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ViewCountingTest extends TestCase
{
    use RefreshDatabase;

    private const UA = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/120.0 Mobile Safari/537.36';

    private function song(string $title, string $slug, array $o = []): Song
    {
        return Song::forceCreate(array_merge([
            'title' => $title, 'slug' => $slug, 'status' => 'published', 'published_at' => now()->subDay(),
            'rights_status' => 'authorized', 'verification_status' => 'unverified',
        ], $o));
    }

    private function user(string $role): User
    {
        return User::forceCreate(['name' => ucfirst($role), 'email' => $role.'@example.test', 'password' => bcrypt('password-123'), 'role' => $role]);
    }

    private function visit(string $path, array $headers = [], string $ip = '10.0.0.1', ?string $ua = null)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders(array_merge(['User-Agent' => $ua ?? self::UA], $headers))->get($path);
    }

    private function views(string $type, int $id): int
    {
        return (int) DB::table('daily_views')->where(['type' => $type, 'item_id' => $id])->sum('views');
    }

    public function test_one_view_per_visitor_per_page_per_day(): void
    {
        $song = $this->song('Counted Song', 'counted-song');

        $this->visit('/songs/counted-song')->assertOk();
        $this->visit('/songs/counted-song')->assertOk();
        $this->assertSame(1, $this->views('song', $song->id));

        $this->visit('/songs/counted-song', [], '10.0.0.2')->assertOk();
        $this->assertSame(2, $this->views('song', $song->id));
        $this->assertSame(2, DB::table('view_events')->count());
    }

    public function test_artist_and_album_pages_are_counted(): void
    {
        $artist = Artist::forceCreate(['name' => 'Seen Artist', 'slug' => 'seen-artist', 'status' => 'published', 'published_at' => now()->subDay(), 'verification_status' => 'unverified']);
        $album = Album::forceCreate(['title' => 'Seen Album', 'slug' => 'seen-album', 'type' => 'album', 'status' => 'published', 'published_at' => now()->subDay()]);

        $this->visit('/artists/seen-artist')->assertOk();
        $this->visit('/albums/seen-album')->assertOk();

        $this->assertSame(1, $this->views('artist', $artist->id));
        $this->assertSame(1, $this->views('album', $album->id));
    }

    public function test_bots_prefetch_do_not_track_staff_and_missing_pages_are_not_counted(): void
    {
        $song = $this->song('Ignored Views', 'ignored-views');
        $draft = $this->song('Hidden Draft', 'hidden-draft', ['status' => 'draft', 'published_at' => null]);

        $this->visit('/songs/ignored-views', [], '10.0.0.3', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')->assertOk();
        $this->get('/songs/ignored-views')->assertOk(); // default test client has no browser user agent
        $this->visit('/songs/ignored-views', ['DNT' => '1'], '10.0.0.4')->assertOk();
        $this->visit('/songs/ignored-views', ['Sec-GPC' => '1'], '10.0.0.5')->assertOk();
        $this->visit('/songs/ignored-views', ['Sec-Purpose' => 'prefetch'], '10.0.0.6')->assertOk();
        $this->visit('/songs/hidden-draft', [], '10.0.0.7')->assertNotFound();

        $this->actingAs($this->user('editor'));
        $this->visit('/songs/ignored-views', [], '10.0.0.8')->assertOk();

        $this->assertSame(0, $this->views('song', $song->id));
        $this->assertSame(0, $this->views('song', $draft->id));
        $this->assertSame(0, DB::table('view_events')->count());
    }

    public function test_counting_can_be_switched_off(): void
    {
        $song = $this->song('Switch Off', 'switch-off');
        config(['rwandanlyrics.analytics.enabled' => false]);

        $this->visit('/songs/switch-off')->assertOk();

        $this->assertSame(0, $this->views('song', $song->id));
    }

    public function test_no_personal_data_is_stored(): void
    {
        $this->song('Private Visit', 'private-visit');

        $this->visit('/songs/private-visit', [], '203.0.113.9')->assertOk();

        $row = DB::table('view_events')->first();
        $this->assertSame(64, strlen($row->visitor_hash));
        $this->assertStringNotContainsString('203.0.113.9', json_encode($row));
        $this->assertFalse(Schema::hasColumn('view_events', 'ip'));
        $this->assertFalse(Schema::hasColumn('view_events', 'user_agent'));
        $this->assertFalse(Schema::hasColumn('view_events', 'user_id'));
    }

    public function test_the_same_visitor_is_counted_again_on_a_new_day_with_a_different_hash(): void
    {
        $song = $this->song('Two Days', 'two-days');

        $this->visit('/songs/two-days')->assertOk();
        $this->travel(1)->days();
        $this->visit('/songs/two-days')->assertOk();

        $this->assertSame(2, $this->views('song', $song->id));
        $this->assertSame(2, DB::table('view_events')->distinct()->count('visitor_hash'));
    }

    public function test_prune_removes_old_visitor_rows_but_keeps_daily_totals(): void
    {
        DB::table('view_events')->insert([
            ['type' => 'song', 'item_id' => 1, 'visitor_hash' => str_repeat('a', 64), 'viewed_on' => now()->subDays(3)->toDateString()],
            ['type' => 'song', 'item_id' => 1, 'visitor_hash' => str_repeat('b', 64), 'viewed_on' => now()->toDateString()],
        ]);
        DB::table('daily_views')->insert(['type' => 'song', 'item_id' => 1, 'viewed_on' => now()->subDays(3)->toDateString(), 'views' => 7]);

        $this->artisan('rwandanlyrics:prune-analytics')->assertExitCode(0);

        $this->assertSame(1, DB::table('view_events')->count());
        $this->assertSame(1, DB::table('daily_views')->count());
    }

    public function test_popular_list_uses_only_real_counts_and_only_visible_songs(): void
    {
        $a = $this->song('Song A', 'song-a');
        $b = $this->song('Song B', 'song-b');
        $c = $this->song('Song C', 'song-c', ['rights_status' => 'removed']);
        $today = now()->toDateString();
        DB::table('daily_views')->insert([
            ['type' => 'song', 'item_id' => $a->id, 'viewed_on' => $today, 'views' => 5],
            ['type' => 'song', 'item_id' => $b->id, 'viewed_on' => $today, 'views' => 2],
            ['type' => 'song', 'item_id' => $c->id, 'viewed_on' => $today, 'views' => 9],
        ]);

        $this->assertSame(['Song A'], app(ViewStats::class)->popularSongs()->pluck('title')->all());
        $this->get('/')->assertOk()->assertSee('Trending now')->assertDontSee('Song C');
    }

    public function test_popular_section_is_hidden_when_nothing_qualifies(): void
    {
        $this->song('Quiet Song', 'quiet-song');

        $this->get('/')->assertOk()->assertDontSee('Trending now');
    }

    public function test_admin_analytics_page_is_for_editors_and_shows_real_totals(): void
    {
        $song = $this->song('Top Of Charts', 'top-of-charts');
        DB::table('daily_views')->insert(['type' => 'song', 'item_id' => $song->id, 'viewed_on' => now()->toDateString(), 'views' => 12]);

        $this->get(route('admin.analytics.index'))->assertRedirect();
        $this->actingAs($this->user('contributor'))->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($this->user('editor'))->get(route('admin.analytics.index'))->assertOk()
            ->assertSee('Total page views')->assertSee('Top Of Charts')->assertSee('12 views');
    }
}
