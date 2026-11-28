<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_link_the_full_favicon_set_and_the_files_exist(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('favicon.ico?v=2', false)
            ->assertSee('favicon-96.png?v=2', false)
            ->assertSee('favicon.svg?v=2', false)
            ->assertSee('apple-touch-icon.png?v=2', false);

        foreach (['favicon.ico', 'favicon.svg', 'favicon-48.png', 'favicon-96.png', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png'] as $file) {
            $this->assertFileExists(public_path($file));
        }
    }
}
