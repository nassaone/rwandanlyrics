<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->unique()->words(3, true)];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PublicationStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }
}
