<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LyricFactory extends Factory
{
    public function definition(): array
    {
        return [
            'body' => "[DEMO PLACEHOLDER — not real lyrics]\n\nPlaceholder line one\nPlaceholder line two",
            'revision_number' => 1,
            'is_current' => true,
        ];
    }
}
