<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonExercise;
use App\Models\Module;
use App\Models\PointTransaction;
use App\Models\Track;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ExerciseAutoGradingTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected Course $course;

    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create(['email_verified_at' => now()]);

        $track = Track::factory()->create();
        $this->course = Course::factory()->create(['track_id' => $track->id, 'lock_lessons_sequentially' => false]);
        $module = Module::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'type' => Lesson::TYPE_EXERCISE,
            'is_published' => true,
        ]);

        LessonExercise::create([
            'lesson_id' => $this->lesson->id,
            'language' => 'html',
            'instructions' => 'Buat elemen <h1> berisi "Halo".',
            'starter_code' => '<h1></h1>',
            'checks' => [
                ['type' => 'text', 'selector' => 'h1', 'expected' => 'Halo'],
            ],
        ]);
    }

    public function test_submitting_exercise_marks_lesson_complete_and_awards_points(): void
    {
        $this->actingAs($this->student);

        Volt::test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->call('submitExercise');

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'points' => GamificationService::POINTS_PER_LESSON,
        ]);
    }

    public function test_submitting_exercise_twice_does_not_double_award_points(): void
    {
        $this->actingAs($this->student);

        $component = Volt::test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson]);
        $component->call('submitExercise');
        $component->call('submitExercise');

        $this->assertSame(1, UserProgress::where('user_id', $this->student->id)->where('lesson_id', $this->lesson->id)->count());
        $this->assertSame(1, PointTransaction::where('user_id', $this->student->id)->where('lesson_id', $this->lesson->id)->count());
    }
}
