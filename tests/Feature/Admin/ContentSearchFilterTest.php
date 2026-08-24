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

class ContentSearchFilterTest extends TestCase
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

    public function test_track_search_filters_by_title_or_slug(): void
    {
        Track::factory()->create(['title' => 'Frontend Fundamentals', 'is_published' => true]);
        Track::factory()->create(['title' => 'Backend Dasar', 'is_published' => true]);

        $this->actingAs($this->admin);

        $component = Volt::test('pages.admin.tracks.index')->set('search', 'Frontend');

        $component->assertSee('Frontend Fundamentals')->assertDontSee('Backend Dasar');
    }

    public function test_track_status_filter_narrows_by_published_state(): void
    {
        Track::factory()->create(['title' => 'Published Track', 'is_published' => true]);
        Track::factory()->create(['title' => 'Draft Track', 'is_published' => false]);

        $this->actingAs($this->admin);

        $component = Volt::test('pages.admin.tracks.index')->set('status', 'draft');

        $component->assertSee('Draft Track')->assertDontSee('Published Track');
    }

    public function test_track_reset_filters_clears_search_and_status(): void
    {
        Track::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('pages.admin.tracks.index')
            ->set('search', 'x')
            ->set('status', 'draft')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('status', '');
    }

    public function test_course_search_is_scoped_to_the_current_track(): void
    {
        $trackA = Track::factory()->create();
        $trackB = Track::factory()->create();
        Course::factory()->create(['track_id' => $trackA->id, 'title' => 'Course In A']);
        Course::factory()->create(['track_id' => $trackB->id, 'title' => 'Course In B']);

        $this->actingAs($this->admin);

        Volt::test('pages.admin.courses.index', ['track' => $trackA])
            ->set('search', 'Course In')
            ->assertSee('Course In A')
            ->assertDontSee('Course In B');
    }

    public function test_module_search_filters_within_the_course(): void
    {
        $course = Course::factory()->create();
        Module::factory()->create(['course_id' => $course->id, 'title' => 'HTML Dasar']);
        Module::factory()->create(['course_id' => $course->id, 'title' => 'CSS Dasar']);

        $this->actingAs($this->admin);

        $component = Volt::test('pages.admin.modules.index', ['course' => $course])->set('search', 'HTML');

        $component->assertSee('HTML Dasar')->assertDontSee('CSS Dasar');
    }

    public function test_lesson_filters_by_type_and_status(): void
    {
        $module = Module::factory()->create();
        Lesson::factory()->create(['module_id' => $module->id, 'title' => 'Quiz Lesson', 'type' => Lesson::TYPE_QUIZ, 'is_published' => true]);
        Lesson::factory()->create(['module_id' => $module->id, 'title' => 'Text Lesson', 'type' => Lesson::TYPE_TEXT, 'is_published' => false]);

        $this->actingAs($this->admin);

        $component = Volt::test('pages.admin.lessons.index', ['module' => $module])->set('typeFilter', Lesson::TYPE_QUIZ);

        $component->assertSee('Quiz Lesson')->assertDontSee('Text Lesson');

        $component->set('typeFilter', '')->set('status', 'draft');

        $component->assertSee('Text Lesson')->assertDontSee('Quiz Lesson');
    }

    public function test_no_match_shows_empty_filtered_state(): void
    {
        Track::factory()->create(['title' => 'Something Else']);

        $this->actingAs($this->admin);

        Volt::test('pages.admin.tracks.index')
            ->set('search', 'zzz-not-found')
            ->assertSee('Tidak ada track yang cocok');
    }
}
