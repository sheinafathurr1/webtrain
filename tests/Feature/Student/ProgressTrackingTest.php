<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\User;
use App\Models\UserProgress;
use Database\Seeders\CourseContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProgressTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CourseContentSeeder::class);

        $this->student = User::factory()->create(['email_verified_at' => now()]);
        $this->course = Course::firstOrFail();
    }

    public function test_second_lesson_is_locked_until_the_first_is_completed(): void
    {
        $lessons = $this->course->publishedLessons()->values();

        $this->actingAs($this->student)
            ->get(route('lessons.show', [$this->course, $lessons->get(1)]))
            ->assertForbidden();
    }

    public function test_completing_a_lesson_unlocks_the_next_one(): void
    {
        $lessons = $this->course->publishedLessons()->values();

        UserProgress::create(['user_id' => $this->student->id, 'lesson_id' => $lessons->first()->id]);

        $this->actingAs($this->student)
            ->get(route('lessons.show', [$this->course, $lessons->get(1)]))
            ->assertOk();
    }

    public function test_lessons_are_unlocked_when_sequential_locking_is_disabled(): void
    {
        $this->course->update(['lock_lessons_sequentially' => false]);
        $lessons = $this->course->publishedLessons()->values();

        $this->actingAs($this->student)
            ->get(route('lessons.show', [$this->course, $lessons->get(2)]))
            ->assertOk();
    }

    public function test_marking_a_lesson_complete_creates_progress(): void
    {
        $lesson = $this->course->publishedLessons()->first();

        $this->actingAs($this->student);

        Volt::test('pages.lessons.show', ['course' => $this->course, 'lesson' => $lesson])
            ->call('toggleComplete');

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_toggling_complete_twice_removes_progress(): void
    {
        $lesson = $this->course->publishedLessons()->first();

        $this->actingAs($this->student);

        $component = Volt::test('pages.lessons.show', ['course' => $this->course, 'lesson' => $lesson]);
        $component->call('toggleComplete');
        $component->call('toggleComplete');

        $this->assertDatabaseMissing('user_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_marking_a_lesson_complete_awards_gamification_points(): void
    {
        $lesson = $this->course->publishedLessons()->first();

        $this->actingAs($this->student);

        Volt::test('pages.lessons.show', ['course' => $this->course, 'lesson' => $lesson])
            ->call('toggleComplete');

        $this->assertEquals(
            \App\Services\GamificationService::POINTS_PER_LESSON,
            $this->student->fresh()->total_points
        );
    }

    public function test_dashboard_shows_course_progress_after_completing_a_lesson(): void
    {
        $lesson = $this->course->publishedLessons()->first();

        UserProgress::create(['user_id' => $this->student->id, 'lesson_id' => $lesson->id]);

        $this->actingAs($this->student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($this->course->title)
            ->assertSee('10%');
    }

    public function test_dashboard_shows_empty_state_without_progress(): void
    {
        $this->actingAs($this->student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kamu belum mulai course apa pun.');
    }
}
