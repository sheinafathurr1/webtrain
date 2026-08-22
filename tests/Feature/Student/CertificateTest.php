<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Track;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
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
        $this->course = Course::factory()->create(['track_id' => $track->id, 'is_published' => true]);
        $module = Module::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
        ]);
    }

    public function test_certificate_is_blocked_before_course_is_completed(): void
    {
        $this->actingAs($this->student)
            ->get(route('courses.certificate', $this->course))
            ->assertForbidden();
    }

    public function test_certificate_downloads_as_a_pdf_once_course_is_100_percent_complete(): void
    {
        UserProgress::create(['user_id' => $this->student->id, 'lesson_id' => $this->lesson->id]);

        $response = $this->actingAs($this->student)->get(route('courses.certificate', $this->course));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('courses.certificate', $this->course))
            ->assertRedirect(route('login'));
    }
}
