<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\CourseContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodePlaygroundTest extends TestCase
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

    public function test_exercise_lesson_renders_the_playground_with_wire_ignore(): void
    {
        $lessons = $this->course->publishedLessons()->values();
        $exerciseLesson = $lessons->firstWhere('type', Lesson::TYPE_EXERCISE);

        $this->markLessonsCompleteUpTo($exerciseLesson);

        $response = $this->actingAs($this->student)
            ->get(route('lessons.show', [$this->course, $exerciseLesson]));

        $response->assertOk();
        $response->assertSee('data-playground', false);
        $response->assertSee('wire:ignore', false);
        $response->assertSee('codePlayground(', false);
        // starter_code is embedded JS-string-escaped (via @js()), not HTML-escaped —
        // check for a distinctive plain-text fragment from it instead of the raw value.
        $response->assertSee('Latihan HTML');
    }

    public function test_text_lesson_does_not_render_the_playground(): void
    {
        $lessons = $this->course->publishedLessons()->values();
        $textLesson = $lessons->firstWhere('type', Lesson::TYPE_TEXT);

        $response = $this->actingAs($this->student)
            ->get(route('lessons.show', [$this->course, $textLesson]));

        $response->assertOk();
        $response->assertDontSee('data-playground', false);
    }

    private function markLessonsCompleteUpTo(Lesson $target): void
    {
        foreach ($this->course->publishedLessons() as $lesson) {
            if ($lesson->id === $target->id) {
                break;
            }

            \App\Models\UserProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $lesson->id,
            ]);
        }
    }
}
