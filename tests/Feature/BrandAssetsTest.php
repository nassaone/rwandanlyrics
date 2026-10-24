<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_files_exist(): void
    {
        foreach (['favicon.svg', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png', 'og-default.png', 'site.webmanifest'] as $f) {
            $this->assertFileExists(public_path($f), $f);
        }
        $this->assertNotNull(json_decode(file_get_contents(public_path('site.webmanifest'))));
    }

    public function test_head_links_icons_and_share_image(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('rel="icon"', $html);
        $this->assertStringContainsString('rel="manifest"', $html);
        $this->assertStringContainsString('og:image', $html);
        $this->assertStringContainsString('summary_large_image', $html);
    }
}
