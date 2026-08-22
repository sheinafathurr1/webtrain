<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => ucfirst($this->faker->words(4, true)),
            'description' => $this->faker->sentence(),
            'order' => 0,
        ];
    }
}
