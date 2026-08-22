<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'option_text' => ucfirst($this->faker->words(2, true)),
            'is_correct' => false,
            'order' => 0,
        ];
    }
}
