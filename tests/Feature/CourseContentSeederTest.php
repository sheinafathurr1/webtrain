<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
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

        foreach ($course->modules as $module) {
            $this->assertCount(3, $module->lessons);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CourseContentSeeder::class);
        $this->seed(CourseContentSeeder::class);

        $this->assertEquals(1, Course::count());
        $this->assertEquals(9, Lesson::count());
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
