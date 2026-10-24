<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_has_theme_script_and_toggle(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('theme-init.js', false)
            ->assertSee('rlToggleTheme()', false)
            ->assertSee('Switch between light and dark theme');
        $this->assertFileExists(public_path('theme-init.js'));
    }
}
