<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => $this->faker->sentence().'?',
            'explanation' => $this->faker->sentence(),
            'correct_answer' => null,
            'order' => 0,
        ];
    }
}
