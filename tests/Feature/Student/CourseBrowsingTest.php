<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\CourseContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_published_courses(): void
    {
        $this->seed(CourseContentSeeder::class);

        $course = Course::firstOrFail();

        $this->get('/courses')->assertOk()->assertSee($course->title);
    }

    public function test_unpublished_courses_are_hidden_from_the_listing(): void
    {
        $track = Track::factory()->create(['is_published' => true]);
        Course::factory()->create(['track_id' => $track->id, 'title' => 'Draft Course', 'is_published' => false]);

        $this->get('/courses')->assertOk()->assertDontSee('Draft Course');
    }

    public function test_guest_can_view_a_published_course_detail_page(): void
    {
        $this->seed(CourseContentSeeder::class);

        $course = Course::firstOrFail();

        $this->get(route('courses.show', $course))->assertOk()->assertSee($course->title);
    }

    public function test_viewing_an_unpublished_course_returns_404(): void
    {
        $track = Track::factory()->create();
        $course = Course::factory()->create(['track_id' => $track->id, 'is_published' => false]);

        $this->get(route('courses.show', $course))->assertNotFound();
    }

    public function test_guest_is_redirected_to_login_when_opening_a_lesson(): void
    {
        $this->seed(CourseContentSeeder::class);

        $course = Course::firstOrFail();
        $lesson = $course->publishedLessons()->first();

        $this->get(route('lessons.show', [$course, $lesson]))->assertRedirect(route('login'));
    }

    public function test_authenticated_student_can_view_the_first_lesson(): void
    {
        $this->seed(CourseContentSeeder::class);

        $student = User::factory()->create(['email_verified_at' => now()]);
        $course = Course::firstOrFail();
        $lesson = $course->publishedLessons()->first();

        $this->actingAs($student)
            ->get(route('lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertSee($lesson->title);
    }
}
