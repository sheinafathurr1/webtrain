<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lesson_id' => Lesson::factory(),
            'status' => UserProgress::STATUS_COMPLETED,
            'completed_at' => now(),
        ];
    }
}
