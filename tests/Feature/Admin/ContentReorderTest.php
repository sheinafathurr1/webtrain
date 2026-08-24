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

class ContentReorderTest extends TestCase
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

    public function test_admin_can_drag_a_track_to_a_new_position(): void
    {
        $a = Track::factory()->create(['title' => 'A', 'order' => 0]);
        $b = Track::factory()->create(['title' => 'B', 'order' => 1]);
        $c = Track::factory()->create(['title' => 'C', 'order' => 2]);

        $this->actingAs($this->admin);

        // Drag A (first) onto C (last) -> A is inserted immediately before C: B, A, C.
        Volt::test('pages.admin.tracks.index')->call('reorder', $a->id, $c->id);

        $this->assertSame(
            [$b->id, $a->id, $c->id],
            Track::orderBy('order')->pluck('id')->all()
        );
    }

    public function test_reorder_ignores_ids_outside_the_scoped_set(): void
    {
        $trackA = Track::factory()->create(['order' => 0]);
        $trackB = Track::factory()->create(['order' => 1]);

        $courseInA = Course::factory()->create(['track_id' => $trackA->id, 'order' => 0]);
        $courseInB = Course::factory()->create(['track_id' => $trackB->id, 'order' => 0]);

        $this->actingAs($this->admin);

        // Attempt to reorder using a course from a different track — must be a no-op.
        Volt::test('pages.admin.courses.index', ['track' => $trackA])
            ->call('reorder', $courseInA->id, $courseInB->id);

        $this->assertSame(0, $courseInB->fresh()->order);
        $this->assertSame(0, $courseInA->fresh()->order);
    }

    public function test_admin_can_drag_a_module_to_a_new_position(): void
    {
        $course = Course::factory()->create();
        $a = Module::factory()->create(['course_id' => $course->id, 'title' => 'A', 'order' => 0]);
        $b = Module::factory()->create(['course_id' => $course->id, 'title' => 'B', 'order' => 1]);

        $this->actingAs($this->admin);

        Volt::test('pages.admin.modules.index', ['course' => $course])->call('reorder', $b->id, $a->id);

        $this->assertSame(
            [$b->id, $a->id],
            Module::orderBy('order')->pluck('id')->all()
        );
    }

    public function test_admin_can_drag_a_lesson_to_a_new_position(): void
    {
        $module = Module::factory()->create();
        $a = Lesson::factory()->create(['module_id' => $module->id, 'title' => 'A', 'order' => 0]);
        $b = Lesson::factory()->create(['module_id' => $module->id, 'title' => 'B', 'order' => 1]);

        $this->actingAs($this->admin);

        Volt::test('pages.admin.lessons.index', ['module' => $module])->call('reorder', $b->id, $a->id);

        $this->assertSame(
            [$b->id, $a->id],
            Lesson::orderBy('order')->pluck('id')->all()
        );
    }
}
