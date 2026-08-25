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

    public function test_admin_can_save_a_full_new_track_order(): void
    {
        $a = Track::factory()->create(['title' => 'A', 'order' => 0]);
        $b = Track::factory()->create(['title' => 'B', 'order' => 1]);
        $c = Track::factory()->create(['title' => 'C', 'order' => 2]);

        $this->actingAs($this->admin);

        Volt::test('pages.admin.tracks.index')->call('saveOrder', [$c->id, $a->id, $b->id]);

        $this->assertSame(
            [$c->id, $a->id, $b->id],
            Track::orderBy('order')->pluck('id')->all()
        );
    }

    public function test_saving_order_turns_off_reordering_mode(): void
    {
        $track = Track::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('pages.admin.tracks.index')
            ->set('reordering', true)
            ->call('saveOrder', [$track->id])
            ->assertSet('reordering', false);
    }

    public function test_dragging_a_row_from_the_first_to_the_last_position_works_in_one_move(): void
    {
        // Regression test: the old per-drop reorder() action only ever
        // moved a row relative to the row it was dropped on, which broke
        // down for drags spanning more than an adjacent row. saveOrder()
        // takes the client's full final order in one call, so a drag from
        // position 0 all the way to position 4 must "just work".
        $tracks = collect(range(0, 4))->map(
            fn ($i) => Track::factory()->create(['title' => "Track {$i}", 'order' => $i])
        );

        $this->actingAs($this->admin);

        $newOrder = $tracks->skip(1)->pluck('id')->push($tracks->first()->id)->values()->all();

        Volt::test('pages.admin.tracks.index')->call('saveOrder', $newOrder);

        $this->assertSame($newOrder, Track::orderBy('order')->pluck('id')->all());
    }

    public function test_save_order_ignores_ids_outside_the_scoped_set(): void
    {
        $trackA = Track::factory()->create(['order' => 0]);
        $trackB = Track::factory()->create(['order' => 1]);

        $courseInA = Course::factory()->create(['track_id' => $trackA->id, 'order' => 0]);
        $courseInB = Course::factory()->create(['track_id' => $trackB->id, 'order' => 0]);

        $this->actingAs($this->admin);

        // Attempt to reorder using a course from a different track — that
        // id must be dropped, leaving courseInB's order untouched.
        Volt::test('pages.admin.courses.index', ['track' => $trackA])
            ->call('saveOrder', [$courseInB->id, $courseInA->id]);

        $this->assertSame(0, $courseInB->fresh()->order);
        $this->assertSame(0, $courseInA->fresh()->order);
    }

    public function test_admin_can_save_a_new_module_order(): void
    {
        $course = Course::factory()->create();
        $a = Module::factory()->create(['course_id' => $course->id, 'title' => 'A', 'order' => 0]);
        $b = Module::factory()->create(['course_id' => $course->id, 'title' => 'B', 'order' => 1]);

        $this->actingAs($this->admin);

        Volt::test('pages.admin.modules.index', ['course' => $course])->call('saveOrder', [$b->id, $a->id]);

        $this->assertSame(
            [$b->id, $a->id],
            Module::orderBy('order')->pluck('id')->all()
        );
    }

    public function test_admin_can_save_a_new_lesson_order(): void
    {
        $module = Module::factory()->create();
        $a = Lesson::factory()->create(['module_id' => $module->id, 'title' => 'A', 'order' => 0]);
        $b = Lesson::factory()->create(['module_id' => $module->id, 'title' => 'B', 'order' => 1]);

        $this->actingAs($this->admin);

        Volt::test('pages.admin.lessons.index', ['module' => $module])->call('saveOrder', [$b->id, $a->id]);

        $this->assertSame(
            [$b->id, $a->id],
            Lesson::orderBy('order')->pluck('id')->all()
        );
    }
}
