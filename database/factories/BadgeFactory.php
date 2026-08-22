<?php

namespace Database\Factories;

use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;

class BadgeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name' => ucfirst($this->faker->words(2, true)),
            'description' => $this->faker->sentence(),
            'icon' => '🏅',
            'criteria_type' => Badge::CRITERIA_LESSONS_COMPLETED,
            'criteria_value' => 1,
        ];
    }
}
