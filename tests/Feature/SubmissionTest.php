<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Song;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?string $email = null): User
    {
        $email ??= $role.'@example.test';

        return User::where('email', $email)->first() ?? User::forceCreate([
            'name' => ucfirst($role), 'email' => $email, 'password' => bcrypt('password-123'), 'role' => $role,
        ]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Brand New Song', 'artist_name' => 'Someone',
            'lyrics' => 'First line of the lyrics', 'rights_confirmed' => '1',
        ], $extra);
    }

    private function submission(User $owner, array $overrides = []): Submission
    {
        $submission = new Submission(['title' => 'Song X', 'artist_name' => 'Artist X', 'lyrics' => 'Some lyrics here', 'rights_confirmed' => true]);
        $submission->forceFill(array_merge(['user_id' => $owner->id, 'type' => 'new_song', 'status' => 'pending'], $overrides))->save();

        return $submission;
    }

    private function artist(): Artist
    {
        return Artist::forceCreate([
            'name' => 'Real Artist', 'slug' => 'real-artist', 'status' => 'published',
            'published_at' => now()->subDay(), 'verification_status' => 'unverified',
        ]);
    }

    public function test_only_contributors_and_above_can_open_the_contribution_area(): void
    {
        $this->get(route('contribute.index'))->assertRedirect();
        $this->actingAs($this->user('user'))->get(route('contribute.index'))->assertForbidden();
        $this->actingAs($this->user('contributor'))->get(route('contribute.index'))->assertOk();
        $this->actingAs($this->user('contributor'))->get(route('contribute.create'))->assertOk();
    }

    public function test_contributor_can_submit_a_new_song_and_it_starts_pending(): void
    {
        $this->actingAs($this->user('contributor'))->post(route('contribute.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $submission = Submission::firstOrFail();
        $this->assertSame('pending', $submission->status->value);
        $this->assertSame('new_song', $submission->type->value);
        $this->assertSame($this->user('contributor')->id, $submission->user_id);
        $this->assertSame(0, Song::count());
    }

    public function test_rights_confirmation_and_lyrics_are_required(): void
    {
        $c = $this->user('contributor');
        $this->actingAs($c)->post(route('contribute.store'), $this->payload(['rights_confirmed' => null]))->assertSessionHasErrors('rights_confirmed');
        $this->actingAs($c)->post(route('contribute.store'), $this->payload(['lyrics' => '']))->assertSessionHasErrors('lyrics');
        $this->assertSame(0, Submission::count());
    }

    public function test_mass_assignment_cannot_set_status_or_user(): void
    {
        $c = $this->user('contributor');
        $other = $this->user('admin');
        $this->actingAs($c)->post(route('contribute.store'), $this->payload(['status' => 'approved', 'user_id' => $other->id, 'reviewed_by' => $other->id]));

        $submission = Submission::firstOrFail();
        $this->assertSame('pending', $submission->status->value);
        $this->assertSame($c->id, $submission->user_id);
        $this->assertNull($submission->reviewed_by);
    }

    public function test_contributors_are_limited_to_ten_pending_submissions(): void
    {
        $c = $this->user('contributor');
        for ($i = 0; $i < 10; $i++) {
            $this->submission($c);
        }

        $this->actingAs($c)->post(route('contribute.store'), $this->payload())->assertSessionHasErrors('title');
        $this->assertSame(10, Submission::count());
    }

    public function test_contributors_only_see_their_own_submissions(): void
    {
        $mine = $this->submission($this->user('contributor'), ['title' => 'Mine Song']);
        $theirs = $this->submission($this->user('contributor', 'other@example.test'), ['title' => 'Theirs Song']);

        $this->actingAs($this->user('contributor'))->get(route('contribute.index'))
            ->assertSee('Mine Song')->assertDontSee('Theirs Song');
        $this->actingAs($this->user('contributor'))->get(route('contribute.show', $theirs))->assertForbidden();
        $this->actingAs($this->user('contributor'))->get(route('contribute.show', $mine))->assertOk();
        $this->actingAs($this->user('editor'))->get(route('contribute.show', $theirs))->assertOk();
    }

    public function test_review_queue_is_for_editors_only(): void
    {
        $submission = $this->submission($this->user('contributor'));

        $this->actingAs($this->user('contributor'))->get(route('admin.submissions.index'))->assertForbidden();
        $this->actingAs($this->user('contributor'))->post(route('admin.submissions.reject', $submission), ['review_notes' => 'nope nope'])->assertForbidden();
        $this->actingAs($this->user('editor'))->get(route('admin.submissions.index'))->assertOk()->assertSee('Song X');
        $this->actingAs($this->user('editor'))->get(route('admin.submissions.show', $submission))->assertOk();
    }

    public function test_approving_a_new_song_creates_a_hidden_draft_with_pending_rights(): void
    {
        $submission = $this->submission($this->user('contributor'), ['title' => 'Approved Song', 'lyrics' => "Line one\r\nLine two"]);
        $artist = $this->artist();

        $this->actingAs($this->user('editor'))
            ->post(route('admin.submissions.approve', $submission), ['artist_id' => $artist->id])
            ->assertSessionHasNoErrors();

        $song = Song::where('title', 'Approved Song')->firstOrFail();
        $this->assertSame('draft', $song->status->value);
        $this->assertSame('pending', $song->rights_status->value);
        $this->assertTrue($song->artists->contains('id', $artist->id));
        $this->assertSame("Line one\nLine two", $song->lyrics()->where('is_current', true)->first()->body);
        $this->assertSame(0, Song::publiclyVisible()->count());
        $this->assertSame('approved', $submission->fresh()->status->value);
        $this->assertSame($song->id, $submission->fresh()->result_song_id);
    }

    public function test_approving_a_new_song_requires_an_artist(): void
    {
        $submission = $this->submission($this->user('contributor'));

        $this->actingAs($this->user('editor'))->post(route('admin.submissions.approve', $submission), [])
            ->assertSessionHasErrors('artist_id');
        $this->assertSame(0, Song::count());
        $this->assertSame('pending', $submission->fresh()->status->value);
    }

    public function test_approving_a_correction_adds_a_revision_and_keeps_the_old_text(): void
    {
        $song = Song::forceCreate([
            'title' => 'Live Song', 'slug' => 'live-song', 'status' => 'published',
            'published_at' => now()->subDay(), 'rights_status' => 'authorized', 'verification_status' => 'unverified',
        ]);
        $song->lyrics()->create(['body' => 'Old lyrics', 'revision_number' => 1, 'is_current' => true]);
        $submission = $this->submission($this->user('contributor'), ['type' => 'correction', 'song_id' => $song->id, 'lyrics' => 'Corrected lyrics']);

        $this->actingAs($this->user('editor'))->post(route('admin.submissions.approve', $submission), [])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $song->lyrics()->count());
        $this->assertSame('Corrected lyrics', $song->lyrics()->where('is_current', true)->first()->body);
        $this->assertFalse((bool) $song->lyrics()->where('revision_number', 1)->first()->is_current);
        $this->assertSame('approved', $submission->fresh()->status->value);
    }

    public function test_declining_requires_a_reason_and_creates_nothing(): void
    {
        $submission = $this->submission($this->user('contributor'));
        $editor = $this->user('editor');

        $this->actingAs($editor)->post(route('admin.submissions.reject', $submission), [])->assertSessionHasErrors('review_notes');
        $this->actingAs($editor)->post(route('admin.submissions.reject', $submission), ['review_notes' => 'Not enough proof of rights'])
            ->assertSessionHasNoErrors();

        $fresh = $submission->fresh();
        $this->assertSame('rejected', $fresh->status->value);
        $this->assertSame('Not enough proof of rights', $fresh->review_notes);
        $this->assertSame($editor->id, $fresh->reviewed_by);
        $this->assertSame(0, Song::count());
    }

    public function test_a_submission_cannot_be_reviewed_twice(): void
    {
        $submission = $this->submission($this->user('contributor'));
        $editor = $this->user('editor');
        $artist = $this->artist();

        $this->actingAs($editor)->post(route('admin.submissions.approve', $submission), ['artist_id' => $artist->id]);
        $this->actingAs($editor)->post(route('admin.submissions.approve', $submission), ['artist_id' => $artist->id])
            ->assertSessionHasErrors('review');
        $this->actingAs($editor)->post(route('admin.submissions.reject', $submission), ['review_notes' => 'too late now'])
            ->assertSessionHasErrors('review');

        $this->assertSame(1, Song::count());
        $this->assertSame('approved', $submission->fresh()->status->value);
    }

    public function test_correction_form_prefills_from_a_visible_song_only(): void
    {
        $song = Song::forceCreate([
            'title' => 'Prefill Me', 'slug' => 'prefill-me', 'status' => 'published',
            'published_at' => now()->subDay(), 'rights_status' => 'authorized', 'verification_status' => 'unverified',
        ]);
        $draft = Song::forceCreate([
            'title' => 'Hidden Draft', 'slug' => 'hidden-draft', 'status' => 'draft',
            'rights_status' => 'unknown', 'verification_status' => 'unverified',
        ]);
        $c = $this->user('contributor');

        $this->actingAs($c)->get(route('contribute.create', ['song' => $song->slug]))->assertOk()->assertSee('Prefill Me');
        $this->actingAs($c)->get(route('contribute.create', ['song' => $draft->slug]))->assertNotFound();
    }
}
