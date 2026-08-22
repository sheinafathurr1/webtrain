<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ContentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $this->admin->assignRole('Admin');
    }

    public function test_admin_can_create_a_track(): void
    {
        $this->actingAs($this->admin);

        Volt::test('pages.admin.tracks.index')
            ->set('title', 'Frontend Fundamentals')
            ->set('description', 'Belajar dasar frontend')
            ->call('save');

        $this->assertDatabaseHas('tracks', [
            'title' => 'Frontend Fundamentals',
            'slug' => 'frontend-fundamentals',
        ]);
    }

    public function test_admin_can_create_a_course_under_a_track(): void
    {
        $track = Track::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('pages.admin.courses.index', ['track' => $track])
            ->set('title', 'HTML & CSS Dasar')
            ->call('save');

        $this->assertDatabaseHas('courses', [
            'title' => 'HTML & CSS Dasar',
            'track_id' => $track->id,
        ]);
    }

    public function test_admin_can_create_a_module_under_a_course(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('pages.admin.modules.index', ['course' => $course])
            ->set('title', 'Bab 1: Pengenalan HTML')
            ->call('save');

        $this->assertDatabaseHas('modules', [
            'title' => 'Bab 1: Pengenalan HTML',
            'course_id' => $course->id,
        ]);
    }

    public function test_admin_can_create_a_text_lesson(): void
    {
        $module = Module::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('pages.admin.lessons.index', ['module' => $module])
            ->set('title', 'Apa itu HTML?')
            ->set('type', Lesson::TYPE_TEXT)
            ->set('content', 'HTML adalah bahasa markup...')
            ->call('save');

        $this->assertDatabaseHas('lessons', [
            'title' => 'Apa itu HTML?',
            'module_id' => $module->id,
            'type' => Lesson::TYPE_TEXT,
        ]);
    }

    public function test_admin_can_create_an_exercise_lesson_with_exercise_data(): void
    {
        $module = Module::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('pages.admin.lessons.index', ['module' => $module])
            ->set('title', 'Latihan: Membuat Heading')
            ->set('type', Lesson::TYPE_EXERCISE)
            ->set('exercise_language', 'html')
            ->set('exercise_instructions', 'Buat elemen <h1> dengan teks "Hello"')
            ->set('exercise_starter_code', '<!-- tulis di sini -->')
            ->call('save');

        $lesson = Lesson::where('title', 'Latihan: Membuat Heading')->firstOrFail();

        $this->assertEquals(Lesson::TYPE_EXERCISE, $lesson->type);
        $this->assertNotNull($lesson->exercise);
        $this->assertEquals('html', $lesson->exercise->language);
        $this->assertStringContainsString('Hello', $lesson->exercise->instructions);
    }

    public function test_text_lesson_requires_content(): void
    {
        $module = Module::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('pages.admin.lessons.index', ['module' => $module])
            ->set('title', 'Lesson tanpa konten')
            ->set('type', Lesson::TYPE_TEXT)
            ->set('content', '')
            ->call('save')
            ->assertHasErrors('content');
    }

    public function test_non_admin_cannot_reach_admin_content_pages(): void
    {
        $student = User::factory()->create(['email_verified_at' => now()]);
        $student->assignRole('Student');

        $this->actingAs($student)->get('/admin/tracks')->assertForbidden();
    }

    public function test_deleting_a_track_cascades_to_courses(): void
    {
        $track = Track::factory()->create();
        $course = Course::factory()->create(['track_id' => $track->id]);

        $this->actingAs($this->admin);

        Volt::test('pages.admin.tracks.index')
            ->set('confirmingDeleteId', $track->id)
            ->call('delete');

        $this->assertDatabaseMissing('tracks', ['id' => $track->id]);
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }
}
