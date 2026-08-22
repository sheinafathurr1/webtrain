<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\QuizAttempt;
use App\Models\Track;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\GamificationService;
use Carbon\Carbon;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GamificationService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);

        $this->service = app(GamificationService::class);
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_completing_a_lesson_awards_points(): void
    {
        $lesson = Lesson::factory()->create();
        UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lesson->id]);

        $this->service->recordLessonCompleted($this->user, $lesson);

        $this->assertEquals(GamificationService::POINTS_PER_LESSON, $this->user->fresh()->total_points);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->user->id,
            'lesson_id' => $lesson->id,
            'points' => GamificationService::POINTS_PER_LESSON,
        ]);
    }

    public function test_recording_the_same_lesson_twice_does_not_double_award_points(): void
    {
        $lesson = Lesson::factory()->create();
        UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lesson->id]);

        $this->service->recordLessonCompleted($this->user, $lesson);
        $this->service->recordLessonCompleted($this->user, $lesson);

        $this->assertEquals(GamificationService::POINTS_PER_LESSON, $this->user->fresh()->total_points);
        $this->assertEquals(1, \App\Models\PointTransaction::where('lesson_id', $lesson->id)->count());
    }

    public function test_streak_increments_on_consecutive_days_and_resets_after_a_gap(): void
    {
        $lessons = Lesson::factory()->count(3)->create();

        Carbon::setTestNow('2026-01-01 10:00:00');
        UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lessons[0]->id]);
        $this->service->recordLessonCompleted($this->user, $lessons[0]);
        $this->assertEquals(1, $this->user->fresh()->current_streak);

        Carbon::setTestNow('2026-01-02 10:00:00');
        UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lessons[1]->id]);
        $this->service->recordLessonCompleted($this->user, $lessons[1]);
        $this->assertEquals(2, $this->user->fresh()->current_streak);

        Carbon::setTestNow('2026-01-05 10:00:00'); // gap of days
        UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lessons[2]->id]);
        $this->service->recordLessonCompleted($this->user, $lessons[2]);

        $fresh = $this->user->fresh();
        $this->assertEquals(1, $fresh->current_streak);
        $this->assertEquals(2, $fresh->longest_streak);
    }

    public function test_completing_a_lesson_twice_in_the_same_day_does_not_bump_streak_twice(): void
    {
        $lessons = Lesson::factory()->count(2)->create();

        Carbon::setTestNow('2026-01-01 09:00:00');
        UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lessons[0]->id]);
        $this->service->recordLessonCompleted($this->user, $lessons[0]);

        Carbon::setTestNow('2026-01-01 18:00:00');
        UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lessons[1]->id]);
        $this->service->recordLessonCompleted($this->user, $lessons[1]);

        $this->assertEquals(1, $this->user->fresh()->current_streak);
    }

    public function test_first_lesson_badge_is_awarded(): void
    {
        $lesson = Lesson::factory()->create();
        UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lesson->id]);

        $this->service->recordLessonCompleted($this->user, $lesson);

        $this->assertTrue($this->user->userBadges()->whereHas('badge', fn ($q) => $q->where('slug', 'first-lesson'))->exists());
    }

    public function test_ten_lessons_badge_is_awarded_at_the_tenth_lesson(): void
    {
        $lessons = Lesson::factory()->count(10)->create();

        foreach ($lessons as $lesson) {
            UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lesson->id]);
            $this->service->recordLessonCompleted($this->user, $lesson);
        }

        $this->assertTrue($this->user->userBadges()->whereHas('badge', fn ($q) => $q->where('slug', 'ten-lessons'))->exists());
    }

    public function test_course_completed_badge_is_awarded_at_100_percent(): void
    {
        $track = Track::factory()->create();
        $course = Course::factory()->create(['track_id' => $track->id]);
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['module_id' => $module->id, 'is_published' => true]);

        UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lessons[0]->id]);
        $this->service->recordLessonCompleted($this->user, $lessons[0]);
        $this->assertFalse($this->user->fresh()->userBadges()->whereHas('badge', fn ($q) => $q->where('slug', 'course-complete'))->exists());

        UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lessons[1]->id]);
        $this->service->recordLessonCompleted($this->user, $lessons[1]);
        $this->assertTrue($this->user->fresh()->userBadges()->whereHas('badge', fn ($q) => $q->where('slug', 'course-complete'))->exists());
    }

    public function test_quiz_perfect_badge_is_awarded_after_a_100_percent_attempt(): void
    {
        $quiz = \App\Models\Quiz::factory()->create();

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->user->id,
            'score' => 100,
            'correct_count' => 1,
            'total_questions' => 1,
            'submitted_at' => now(),
        ]);

        $this->service->recordQuizSubmitted($this->user);

        $this->assertTrue($this->user->userBadges()->whereHas('badge', fn ($q) => $q->where('slug', 'quiz-perfect'))->exists());
    }

    public function test_streak_3_badge_is_awarded_after_three_consecutive_days(): void
    {
        $lessons = Lesson::factory()->count(3)->create();

        foreach ([1, 2, 3] as $i => $day) {
            Carbon::setTestNow("2026-02-0{$day} 10:00:00");
            UserProgress::create(['user_id' => $this->user->id, 'lesson_id' => $lessons[$i]->id]);
            $this->service->recordLessonCompleted($this->user, $lessons[$i]);
        }

        $this->assertTrue($this->user->fresh()->userBadges()->whereHas('badge', fn ($q) => $q->where('slug', 'streak-3'))->exists());
    }
}
