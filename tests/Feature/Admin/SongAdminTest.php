<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Models\User;
use App\Support\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SongAdminTest extends TestCase
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
            'title' => 'Test Song',
            'primary_artist_id' => $this->artist->id,
            'status' => 'draft',
            'verification_status' => 'unverified',
            'rights_status' => 'unknown',
        ], $override);
    }

    private function create(array $override = []): Song
    {
        $this->actingAs($this->editor)->post(route('admin.songs.store'), $this->payload($override))->assertSessionHasNoErrors();

        return Song::latest('id')->firstOrFail();
    }

    public function test_editor_creates_a_song_with_lyrics_genres_and_featured_artist(): void
    {
        $guest = Artist::factory()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($this->editor)->post(route('admin.songs.store'), $this->payload([
            'lyrics' => "Line one\r\nLine two",
            'genre_ids' => [$genre->id],
            'featured_artist_ids' => [$guest->id, $this->artist->id],
        ]))->assertRedirect();

        $song = Song::firstOrFail();
        $this->assertSame('test-song', $song->slug);
        $this->assertSame($this->editor->id, $song->created_by);
        $this->assertSame('primary', $song->artists->firstWhere('id', $this->artist->id)->pivot->role);
        $this->assertSame('featured', $song->artists->firstWhere('id', $guest->id)->pivot->role);
        $this->assertCount(2, $song->artists, 'the primary artist must not also be featured');
        $this->assertSame("Line one\nLine two", $song->currentLyric->body);
        $this->assertSame(1, $song->currentLyric->revision_number);
        $this->assertCount(1, $song->genres);
        $this->assertDatabaseHas('audit_logs', ['action' => 'song.created', 'user_id' => $this->editor->id, 'subject_id' => $song->id]);
    }

    public function test_changed_lyrics_create_a_revision_and_unchanged_lyrics_do_not(): void
    {
        $song = $this->create(['lyrics' => 'First version']);
        $url = route('admin.songs.update', $song);

        $this->actingAs($this->editor)->put($url, $this->payload(['lyrics' => 'Second version']))->assertSessionHasNoErrors();
        $song->refresh();
        $this->assertCount(2, $song->lyrics);
        $this->assertSame(2, $song->currentLyric->revision_number);
        $this->assertSame(1, $song->lyrics()->where('is_current', true)->count());

        $this->actingAs($this->editor)->put($url, $this->payload(['lyrics' => 'Second version']));
        $this->assertCount(2, $song->fresh()->lyrics);

        $this->actingAs($this->editor)->put($url, $this->payload(['lyrics' => '']));
        $song = $song->fresh();
        $this->assertNull($song->currentLyric);
        $this->assertCount(2, $song->lyrics, 'old revisions are kept');
    }

    public function test_slug_is_locked_after_publication_but_editable_before(): void
    {
        $draft = $this->create();
        $this->actingAs($this->editor)->put(route('admin.songs.update', $draft), $this->payload(['slug' => 'custom-slug']));
        $this->assertSame('custom-slug', $draft->fresh()->slug);

        $published = Song::factory()->published()->create(['title' => 'Locked']);
        $this->actingAs($this->editor)->put(route('admin.songs.update', $published), $this->payload([
            'title' => 'Locked', 'slug' => 'new-slug', 'status' => 'published', 'rights_status' => 'authorized',
        ]));
        $this->assertSame('locked', $published->fresh()->slug);
    }

    public function test_publishing_with_authorized_rights_makes_the_song_public_and_removing_rights_hides_it(): void
    {
        $song = $this->create(['lyrics' => 'Authorized words']);
        $this->get(route('songs.show', $song->slug))->assertNotFound();

        $this->actingAs($this->editor)->put(route('admin.songs.update', $song), $this->payload([
            'status' => 'published', 'rights_status' => 'authorized', 'rights_holder' => 'Test Holder', 'lyrics' => 'Authorized words',
        ]))->assertSessionHasNoErrors();

        $song->refresh();
        $this->assertNotNull($song->published_at);
        $this->get(route('songs.show', $song->slug))->assertOk()->assertSee('Authorized words');

        $this->actingAs($this->editor)->put(route('admin.songs.update', $song), $this->payload([
            'status' => 'published', 'rights_status' => 'removed', 'lyrics' => 'Authorized words',
        ]));

        $this->get(route('songs.show', $song->slug))->assertNotFound();
        $this->assertDatabaseHas('audit_logs', ['action' => 'song.rights_changed', 'subject_id' => $song->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'song.status_changed', 'subject_id' => $song->id]);
    }

    public function test_scheduling_requires_a_future_date_and_is_stored_in_utc(): void
    {
        $url = route('admin.songs.store');

        $this->actingAs($this->editor)->post($url, $this->payload(['status' => 'scheduled']))->assertSessionHasErrors('published_at');
        $this->actingAs($this->editor)->post($url, $this->payload(['status' => 'scheduled', 'published_at' => '2020-01-01T10:00']))->assertSessionHasErrors('published_at');

        $this->actingAs($this->editor)->post($url, $this->payload(['status' => 'scheduled', 'published_at' => '2035-01-01T12:00']))->assertSessionHasNoErrors();
        $song = Song::firstOrFail();
        $this->assertSame('scheduled', $song->status->value);
        $this->assertSame('2035-01-01 10:00', $song->published_at->format('Y-m-d H:i'));
    }

    public function test_validation_rules(): void
    {
        $url = route('admin.songs.store');
        $this->actingAs($this->editor);

        $this->post($url, $this->payload(['primary_artist_id' => null]))->assertSessionHasErrors('primary_artist_id');
        $this->post($url, $this->payload(['primary_artist_id' => 9999]))->assertSessionHasErrors('primary_artist_id');
        $this->post($url, $this->payload(['title' => '']))->assertSessionHasErrors('title');
        $this->post($url, $this->payload(['status' => 'bogus']))->assertSessionHasErrors('status');
        $this->post($url, $this->payload(['rights_status' => 'bogus']))->assertSessionHasErrors('rights_status');
        $this->post($url, $this->payload(['slug' => 'Not A Slug!']))->assertSessionHasErrors('slug');
        $this->post($url, $this->payload(['canonical_url' => 'javascript:alert(1)']))->assertSessionHasErrors('canonical_url');

        $this->create(['title' => 'Taken', 'slug' => 'taken']);
        $this->post($url, $this->payload(['title' => 'Other', 'slug' => 'taken']))->assertSessionHasErrors('slug');
    }

    public function test_only_admins_can_delete_songs(): void
    {
        $song = $this->create();

        $this->actingAs($this->editor)->delete(route('admin.songs.destroy', $song))->assertForbidden();
        $this->assertDatabaseHas('songs', ['id' => $song->id]);

        $this->actingAs(User::factory()->role(Role::Admin)->create())->delete(route('admin.songs.destroy', $song))->assertRedirect(route('admin.songs.index'));
        $this->assertDatabaseMissing('songs', ['id' => $song->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'song.deleted', 'subject_id' => $song->id]);
    }

    public function test_index_filters_by_title_and_status(): void
    {
        Song::factory()->create(['title' => 'Findable Tune']);
        Song::factory()->published()->create(['title' => 'Other Published']);

        $this->actingAs($this->editor)->get(route('admin.songs.index', ['q' => 'Findable']))
            ->assertOk()->assertSee('Findable Tune')->assertDontSee('Other Published');

        $this->actingAs($this->editor)->get(route('admin.songs.index', ['status' => 'published']))
            ->assertOk()->assertSee('Other Published')->assertDontSee('Findable Tune');
    }

    public function test_in_memory_visibility_matches_the_database_scope(): void
    {
        Song::factory()->published()->create();
        Song::factory()->create();
        Song::factory()->published()->create(['rights_status' => 'unknown']);
        Song::factory()->published()->create(['rights_status' => 'removed']);
        Song::factory()->published()->create(['published_at' => now()->addDay()]);
        Song::factory()->published()->create(['status' => 'archived']);

        $viaScope = Song::publiclyVisible()->pluck('id')->sort()->values()->all();
        $viaHelper = Song::all()->filter(fn ($song) => Visibility::isLive($song))->pluck('id')->sort()->values()->all();

        $this->assertSame($viaScope, $viaHelper);
        $this->assertCount(1, $viaScope);
    }

    public function test_dashboard_shows_real_counts_and_warnings(): void
    {
        Song::factory()->published()->count(2)->create();
        Song::factory()->create(['status' => 'draft']);
        Song::factory()->create(['status' => 'pending_review']);
        Song::factory()->published()->create(['rights_status' => 'unknown']);
        Artist::factory()->count(3)->create();

        $this->actingAs($this->editor)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['published_songs'] === 3 && $s['live_songs'] === 2
                && $s['draft_songs'] === 1 && $s['pending_songs'] === 1 && $s['artists'] === 4)
            ->assertViewHas('warnings', fn ($w) => $w['rights_blocked'] === 1 && $w['no_lyrics'] === 3)
            ->assertSee('Needs attention');
    }
}
