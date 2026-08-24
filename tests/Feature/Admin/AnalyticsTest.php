<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Track;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_non_admins_cannot_view_analytics(): void
    {
        $student = User::factory()->create(['email_verified_at' => now()]);
        $student->assignRole('Student');

        $this->actingAs($student)->get('/admin/analytics')->assertForbidden();
    }

    public function test_analytics_shows_engagement_and_quiz_stats(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $track = Track::factory()->create(['is_published' => true]);
        $course = Course::factory()->create(['track_id' => $track->id, 'is_published' => true, 'title' => 'Course Analitik']);
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'type' => Lesson::TYPE_TEXT, 'is_published' => true]);
        $quiz = Quiz::create(['lesson_id' => $lesson->id, 'title' => 'Quiz Analitik']);

        $student1 = User::factory()->create(['email_verified_at' => now()]);
        $student1->assignRole('Student');
        $student2 = User::factory()->create(['email_verified_at' => now()]);
        $student2->assignRole('Student');

        UserProgress::create(['user_id' => $student1->id, 'lesson_id' => $lesson->id]);
        UserProgress::create(['user_id' => $student2->id, 'lesson_id' => $lesson->id]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student1->id,
            'score' => 80,
            'correct_count' => 4,
            'total_questions' => 5,
            'submitted_at' => now(),
        ]);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student2->id,
            'score' => 60,
            'correct_count' => 3,
            'total_questions' => 5,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/analytics');

        $response->assertOk();
        $response->assertSee('Course Analitik');
        $response->assertSee('Quiz Analitik');
        $response->assertSee('70%'); // average quiz score: (80+60)/2
        $response->assertSee('2'); // total students engaged
    }
}
