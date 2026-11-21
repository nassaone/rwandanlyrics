<?php

namespace Tests\Feature;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchIndexingTest extends TestCase
{
    use RefreshDatabase;

    public function test_indexing_is_on_by_default_in_production(): void
    {
        config(['app.env' => 'production']);

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:', false);
        $this->get('/')->assertSee('content="index,follow"', false);
    }

    public function test_switching_it_off_hides_the_whole_site(): void
    {
        config(['app.env' => 'production']);
        app(SettingsService::class)->set(['search_indexing' => '0']);

        $robots = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString("Disallow: /\n", $robots);
        $this->assertStringNotContainsString('Sitemap:', $robots);
        $this->get('/')->assertSee('content="noindex,nofollow"', false);
    }

    public function test_switching_it_back_on_restores_indexing(): void
    {
        config(['app.env' => 'production']);
        $settings = app(SettingsService::class);
        $settings->set(['search_indexing' => '0']);
        $settings->set(['search_indexing' => '1']);

        $this->get('/robots.txt')->assertSee('Sitemap:', false);
        $this->get('/')->assertSee('content="index,follow"', false);
    }
}
