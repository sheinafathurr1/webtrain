<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'title' => ucfirst($this->faker->words(4, true)),
            'type' => Lesson::TYPE_TEXT,
            'order' => 0,
            'is_published' => true,
            'content' => $this->faker->paragraphs(3, true),
            'video_url' => null,
        ];
    }
}
