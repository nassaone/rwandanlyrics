<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Enums\RightsStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SongFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->unique()->words(3, true)];
    }

    /** Published, live, and rights-authorized — i.e. publicly visible. */
    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PublicationStatus::Published,
            'published_at' => now()->subMinute(),
            'rights_status' => RightsStatus::Authorized,
        ]);
    }
}
