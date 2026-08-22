<?php

namespace Tests\Feature\Student;

use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\GamificationService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardGamificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_xp_streak_and_earned_badges(): void
    {
        $this->seed(BadgeSeeder::class);

        $student = User::factory()->create(['email_verified_at' => now()]);
        $lesson = Lesson::factory()->create();

        UserProgress::create(['user_id' => $student->id, 'lesson_id' => $lesson->id]);
        app(GamificationService::class)->recordLessonCompleted($student, $lesson);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee((string) GamificationService::POINTS_PER_LESSON);
        $response->assertSee('Langkah Pertama');
        $response->assertSee('🔥 1 hari');
    }

    public function test_unearned_badges_are_still_listed_but_visually_muted(): void
    {
        $this->seed(BadgeSeeder::class);

        $student = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Jagoan Quiz');
        $response->assertSee('grayscale', false);
    }
}
