<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_home_page_shows_honest_empty_state(): void
    {
        $this->get('/')->assertOk()->assertSee('Rwandanlyrics')->assertSee('No lyrics published yet');
    }

    public function test_unknown_page_returns_404(): void
    {
        $this->get('/does-not-exist')->assertNotFound()->assertSee('Page not found');
    }

    public function test_health_check_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}
