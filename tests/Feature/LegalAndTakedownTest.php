<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalAndTakedownTest extends TestCase
{
    use RefreshDatabase;

    private function song(string $title, string $slug, array $overrides = []): Song
    {
        return Song::forceCreate(array_merge([
            'title' => $title, 'slug' => $slug, 'status' => 'published', 'published_at' => now()->subDay(),
            'rights_status' => 'authorized', 'verification_status' => 'unverified',
        ], $overrides));
    }

    public function test_policy_pages_render_with_the_configured_contact_emails(): void
    {
        config(['rwandanlyrics.contact' => [
            'copyright' => 'rights@example.org', 'corrections' => 'fixes@example.org', 'privacy' => 'privacy@example.org',
        ]]);

        $pages = [
            '/privacy' => ['Privacy Policy', 'We do not sell personal information', 'privacy@example.org'],
            '/copyright' => ['Copyright & Content Rights Policy', 'does not transfer ownership', 'rights@example.org'],
            '/takedown' => ['Copyright Takedown Requests', 'Copyright Takedown Request –', 'rights@example.org'],
            '/report-lyrics-errors' => ['Report Incorrect Lyrics', 'Found an error in the lyrics', 'fixes@example.org'],
        ];

        foreach ($pages as $path => [$title, $phrase, $email]) {
            $this->get($path)->assertOk()->assertSee($title)->assertSee($phrase)
                ->assertSee('href="mailto:'.$email.'"', false)
                ->assertDontSee('%COPYRIGHT_EMAIL%')->assertDontSee('%CORRECTIONS_EMAIL%')
                ->assertDontSee('%PRIVACY_EMAIL%')->assertDontSee('%SITE_URL%');
        }
    }

    public function test_footer_links_to_every_policy(): void
    {
        $html = $this->get('/')->assertOk();
        foreach (['legal.privacy', 'legal.copyright', 'legal.takedown', 'legal.corrections'] as $name) {
            $html->assertSee(route($name), false);
        }
    }

    public function test_song_page_links_to_reporting_and_contributors_can_suggest_a_correction(): void
    {
        $this->song('Linked Song', 'linked-song');

        $this->get('/songs/linked-song')->assertOk()
            ->assertSee('Report an error')->assertSee('Copyright and takedown requests')
            ->assertDontSee('Suggest a correction');

        $contributor = User::forceCreate(['name' => 'C', 'email' => 'c@example.test', 'password' => bcrypt('password-123'), 'role' => 'contributor']);
        $this->actingAs($contributor)->get('/songs/linked-song')->assertOk()->assertSee('Suggest a correction');
    }

    public function test_policy_pages_are_in_the_sitemap(): void
    {
        $this->get('/sitemaps/pages-1.xml')->assertOk()
            ->assertSee('/privacy', false)->assertSee('/copyright', false)
            ->assertSee('/takedown', false)->assertSee('/report-lyrics-errors', false);
    }

    public function test_songs_without_authorized_rights_vanish_from_page_search_and_sitemap(): void
    {
        $this->song('Vanish Control', 'vanish-control');
        foreach (['removed', 'restricted', 'pending', 'unknown'] as $status) {
            $this->song('Vanish '.ucfirst($status), 'vanish-'.$status, ['rights_status' => $status]);
        }

        $search = $this->get('/search?q=vanish')->assertOk()->assertSee('Vanish Control');
        $sitemap = $this->get('/sitemaps/songs-1.xml')->assertOk()->assertSee('vanish-control', false);

        foreach (['removed', 'restricted', 'pending', 'unknown'] as $status) {
            $this->get('/songs/vanish-'.$status)->assertNotFound();
            $search->assertDontSee('Vanish '.ucfirst($status));
            $sitemap->assertDontSee('vanish-'.$status, false);
        }
    }

    public function test_a_takedown_takes_effect_immediately_on_a_live_song(): void
    {
        $song = $this->song('Live Then Gone', 'live-then-gone');
        $this->get('/songs/live-then-gone')->assertOk();

        $song->forceFill(['rights_status' => 'removed'])->save();

        $this->get('/songs/live-then-gone')->assertNotFound();
        $this->get('/search?q=live then gone')->assertOk()->assertDontSee('Live Then Gone');
        $this->get('/sitemaps/songs-1.xml')->assertDontSee('live-then-gone', false);
    }

    public function test_preflight_fails_with_placeholder_settings(): void
    {
        $this->artisan('rwandanlyrics:preflight')->assertExitCode(1);
    }

    public function test_preflight_passes_with_launch_ready_settings(): void
    {
        config([
            'app.env' => 'production', 'app.debug' => false, 'session.secure' => true, 'mail.default' => 'smtp', 'app.url' => 'https://rwandanlyrics.example',
            'rwandanlyrics.contact' => ['copyright' => 'rights@example.org', 'corrections' => 'fixes@example.org', 'privacy' => 'privacy@example.org'],
        ]);

        $this->artisan('rwandanlyrics:preflight')->assertExitCode(0);
    }
}
