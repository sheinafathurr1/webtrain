<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\CourseContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_content_pages_render_over_http(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(CourseContentSeeder::class);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $track = Track::firstOrFail();
        $course = Course::firstOrFail();
        $module = Module::firstOrFail();
        $lesson = Lesson::firstOrFail();

        $this->actingAs($admin);

        $this->get('/admin')->assertOk()->assertSee('Admin Panel');
        $this->get('/admin/tracks')->assertOk()->assertSee($track->title);
        $this->get(route('admin.courses.index', $track))->assertOk()->assertSee($course->title);
        $this->get(route('admin.modules.index', $course))->assertOk()->assertSee($module->title);
        $this->get(route('admin.lessons.index', $module))->assertOk()->assertSee($lesson->title);
    }
}
