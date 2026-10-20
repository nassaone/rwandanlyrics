<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtistFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->name()];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PublicationStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }
}
