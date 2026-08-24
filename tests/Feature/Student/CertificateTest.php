<?php

namespace Tests\Feature\Student;

use App\Models\Certificate;
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

    public function test_completing_the_course_issues_a_persistent_certificate_record(): void
    {
        UserProgress::create(['user_id' => $this->student->id, 'lesson_id' => $this->lesson->id]);

        $this->actingAs($this->student)->get(route('courses.certificate', $this->course));

        $this->assertDatabaseHas('certificates', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }

    public function test_downloading_the_certificate_twice_keeps_the_same_code(): void
    {
        UserProgress::create(['user_id' => $this->student->id, 'lesson_id' => $this->lesson->id]);

        $this->actingAs($this->student)->get(route('courses.certificate', $this->course));
        $this->actingAs($this->student)->get(route('courses.certificate', $this->course));

        $this->assertSame(1, Certificate::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());
    }

    public function test_certificate_code_verifies_publicly_without_auth(): void
    {
        UserProgress::create(['user_id' => $this->student->id, 'lesson_id' => $this->lesson->id]);

        $this->actingAs($this->student)->get(route('courses.certificate', $this->course));

        $certificate = Certificate::where('user_id', $this->student->id)->where('course_id', $this->course->id)->firstOrFail();

        $this->get(route('certificates.verify', $certificate->code))
            ->assertOk()
            ->assertSee($this->student->name)
            ->assertSee($this->course->title)
            ->assertSee('Sertifikat Valid');
    }

    public function test_unknown_certificate_code_shows_not_found_state(): void
    {
        $this->get(route('certificates.verify', 'WT-NOPE-0000'))
            ->assertOk()
            ->assertSee('Tidak Ditemukan');
    }
}
