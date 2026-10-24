<?php

namespace Tests\Feature;

use App\Models\Media;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    public function test_falls_back_to_initial_without_image(): void
    {
        $this->blade('<x-avatar :name="$n" />', ['n' => 'davis d'])->assertSee('D');
    }

    public function test_renders_decorative_image_when_media_given(): void
    {
        $m = new Media(['disk' => 'public', 'path' => 'media/x.jpg', 'width' => 100, 'height' => 100]);
        $this->blade('<x-avatar :media="$m" name="X" />', ['m' => $m])
            ->assertSee('media/x.jpg', false)
            ->assertSee('alt=""', false);
    }
}
