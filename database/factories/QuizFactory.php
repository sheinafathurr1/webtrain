<?php

namespace Database\Factories;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(['type' => Lesson::TYPE_QUIZ]),
            'title' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->sentence(),
        ];
    }
}
