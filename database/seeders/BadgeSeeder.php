<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'slug' => 'first-lesson',
                'name' => 'Langkah Pertama',
                'description' => 'Menyelesaikan lesson pertamamu.',
                'icon' => '🎯',
                'criteria_type' => Badge::CRITERIA_LESSONS_COMPLETED,
                'criteria_value' => 1,
            ],
            [
                'slug' => 'ten-lessons',
                'name' => 'Rajin Belajar',
                'description' => 'Menyelesaikan 10 lesson.',
                'icon' => '📚',
                'criteria_type' => Badge::CRITERIA_LESSONS_COMPLETED,
                'criteria_value' => 10,
            ],
            [
                'slug' => 'course-complete',
                'name' => 'Penakluk Course',
                'description' => 'Menyelesaikan satu course sampai 100%.',
                'icon' => '🏆',
                'criteria_type' => Badge::CRITERIA_COURSE_COMPLETED,
                'criteria_value' => null,
            ],
            [
                'slug' => 'quiz-perfect',
                'name' => 'Jagoan Quiz',
                'description' => 'Mendapat skor 100% di sebuah quiz.',
                'icon' => '🧠',
                'criteria_type' => Badge::CRITERIA_QUIZ_PERFECT_SCORE,
                'criteria_value' => null,
            ],
            [
                'slug' => 'streak-3',
                'name' => 'Streak 3 Hari',
                'description' => 'Belajar 3 hari berturut-turut.',
                'icon' => '🔥',
                'criteria_type' => Badge::CRITERIA_STREAK_DAYS,
                'criteria_value' => 3,
            ],
            [
                'slug' => 'streak-7',
                'name' => 'Streak 7 Hari',
                'description' => 'Belajar 7 hari berturut-turut.',
                'icon' => '🔥',
                'criteria_type' => Badge::CRITERIA_STREAK_DAYS,
                'criteria_value' => 7,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
        }
    }
}
