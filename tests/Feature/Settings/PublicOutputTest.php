<?php

namespace Tests\Feature\Settings;

use App\Enums\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicOutputTest extends TestCase
{
    use RefreshDatabase;

    private function save(array $v): void
    {
        app(SettingsService::class)->set($v);
    }

    private function codes(): array
    {
        return [
            'head_code' => '<meta name="x-head-test" content="1">',
            'body_start_code' => '<!-- body-start-test -->',
            'body_end_code' => '<!-- body-end-test -->',
            'ad_home_middle' => '<ins>HOME-AD-TEST</ins>',
            'ad_list_top' => '<ins>LIST-AD-TEST</ins>',
            'ga4_id' => 'G-ABCD1234',
            'plausible_domain' => 'example.rw',
        ];
    }

    public function test_nothing_is_output_while_ads_switch_is_off(): void
    {
        $this->save($this->codes() + ['ads_enabled' => false]);
        foreach (['/', '/songs'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            foreach (['x-head-test', 'body-start-test', 'body-end-test', 'AD-TEST', 'googletagmanager', 'plausible'] as $needle) {
                $this->assertStringNotContainsString($needle, $html, "$needle leaked on $path");
            }
        }
    }

    public function test_code_slots_and_analytics_appear_when_switch_is_on(): void
    {
        $this->save($this->codes() + ['ads_enabled' => true]);
        $home = $this->get('/')->assertOk()->getContent();
        foreach (['x-head-test', 'body-start-test', 'body-end-test', 'HOME-AD-TEST', 'G-ABCD1234', 'data-domain="example.rw"'] as $needle) {
            $this->assertStringContainsString($needle, $home);
        }
        $this->get('/songs')->assertOk()->assertSee('LIST-AD-TEST', false);
    }

    public function test_nothing_runs_on_admin_pages(): void
    {
        $this->save($this->codes() + ['ads_enabled' => true]);
        $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin->value]));
        $html = $this->get('/admin/settings/general')->assertOk()->getContent();
        $this->assertStringNotContainsString('body-start-test', $html);
        $this->assertStringNotContainsString('googletagmanager', $html);
    }

    public function test_banner_and_social_links_show(): void
    {
        $this->save(['maintenance_banner' => 'Planned break on Sunday', 'social_youtube' => 'https://youtube.com/@ijwi']);
        $this->get('/')->assertOk()
            ->assertSee('Planned break on Sunday')
            ->assertSee('https://youtube.com/@ijwi', false)
            ->assertSee('YouTube');
    }

    public function test_ads_txt_is_served_only_when_set(): void
    {
        $this->get('/ads.txt')->assertNotFound();
        $this->save(['ads_txt' => 'google.com, pub-123, DIRECT, f08c47fec0942fa0']);
        $this->get('/ads.txt')->assertOk()->assertSee('pub-123')
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    }
}
