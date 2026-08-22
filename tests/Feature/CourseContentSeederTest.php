<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use Database\Seeders\CourseContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_sample_course_structure(): void
    {
        $this->seed(CourseContentSeeder::class);

        $course = Course::where('slug', 'belajar-web-dev-dari-nol')->firstOrFail();

        $this->assertEquals('Frontend Fundamentals', $course->track->title);
        $this->assertCount(3, $course->modules);

        $moduleTitles = $course->modules->pluck('title')->all();
        $this->assertContains('Modul 1: HTML Dasar', $moduleTitles);
        $this->assertContains('Modul 2: CSS Dasar', $moduleTitles);
        $this->assertContains('Modul 3: JavaScript Dasar', $moduleTitles);

        $htmlModule = $course->modules->firstWhere('title', 'Modul 1: HTML Dasar');
        $this->assertCount(4, $htmlModule->lessons); // 2 text + 1 exercise + 1 quiz

        $cssModule = $course->modules->firstWhere('title', 'Modul 2: CSS Dasar');
        $this->assertCount(3, $cssModule->lessons);

        $jsModule = $course->modules->firstWhere('title', 'Modul 3: JavaScript Dasar');
        $this->assertCount(3, $jsModule->lessons);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CourseContentSeeder::class);
        $this->seed(CourseContentSeeder::class);

        $this->assertEquals(1, Course::count());
        $this->assertEquals(10, Lesson::count());
        $this->assertEquals(1, \App\Models\Quiz::count());
        $this->assertEquals(3, Question::count());
        $this->assertEquals(6, \App\Models\QuestionOption::count());
    }

    public function test_quiz_lesson_has_questions_and_a_correct_option_each(): void
    {
        $this->seed(CourseContentSeeder::class);

        $quizLesson = Lesson::where('type', Lesson::TYPE_QUIZ)->firstOrFail();

        $this->assertNotNull($quizLesson->quiz);
        $this->assertCount(3, $quizLesson->quiz->questions);

        foreach ($quizLesson->quiz->questions as $question) {
            if ($question->type === Question::TYPE_MULTIPLE_CHOICE) {
                $this->assertCount(1, $question->options->where('is_correct', true));
            } else {
                $this->assertNotEmpty($question->correct_answer);
            }
        }
    }

    public function test_exercise_lessons_have_exercise_data(): void
    {
        $this->seed(CourseContentSeeder::class);

        $exerciseLessons = Lesson::where('type', Lesson::TYPE_EXERCISE)->get();

        $this->assertCount(3, $exerciseLessons);

        foreach ($exerciseLessons as $lesson) {
            $this->assertNotNull($lesson->exercise);
            $this->assertNotEmpty($lesson->exercise->instructions);
            $this->assertNotEmpty($lesson->exercise->starter_code);
        }
    }
}
