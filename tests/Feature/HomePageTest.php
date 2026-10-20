<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders(): void
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
