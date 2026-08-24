<?php

namespace Tests\Feature\Student;

use App\Models\Bookmark;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LessonBookmarkTest extends TestCase
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
        $this->course = Course::factory()->create(['track_id' => $track->id, 'lock_lessons_sequentially' => false]);
        $module = Module::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'type' => Lesson::TYPE_TEXT,
            'is_published' => true,
        ]);
    }

    public function test_student_can_bookmark_a_lesson(): void
    {
        Volt::actingAs($this->student)
            ->test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->call('toggleBookmark');

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
        ]);
    }

    public function test_toggling_bookmark_twice_removes_it(): void
    {
        Volt::actingAs($this->student)
            ->test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->call('toggleBookmark')
            ->call('toggleBookmark');

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
        ]);
    }

    public function test_bookmarking_twice_without_toggling_off_does_not_duplicate_or_error(): void
    {
        Bookmark::create(['user_id' => $this->student->id, 'lesson_id' => $this->lesson->id]);

        // Simulates a concurrent second request racing the first: the row
        // already exists, so toggleBookmark's delete branch should fire.
        Volt::actingAs($this->student)
            ->test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->call('toggleBookmark')
            ->assertHasNoErrors();

        $this->assertSame(0, Bookmark::where('user_id', $this->student->id)->where('lesson_id', $this->lesson->id)->count());
    }

    public function test_bookmarked_lesson_appears_on_dashboard(): void
    {
        Bookmark::create(['user_id' => $this->student->id, 'lesson_id' => $this->lesson->id]);

        $this->actingAs($this->student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($this->lesson->title)
            ->assertSee($this->course->title);
    }

    public function test_dashboard_shows_empty_state_without_bookmarks(): void
    {
        $this->actingAs($this->student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum ada lesson tersimpan');
    }

    public function test_removing_bookmark_from_dashboard(): void
    {
        $bookmark = Bookmark::create(['user_id' => $this->student->id, 'lesson_id' => $this->lesson->id]);

        $this->actingAs($this->student);

        Volt::test('pages.dashboard')->call('removeBookmark', $bookmark->id);

        $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
    }

    public function test_a_student_cannot_remove_another_students_bookmark(): void
    {
        $other = User::factory()->create(['email_verified_at' => now()]);
        $bookmark = Bookmark::create(['user_id' => $other->id, 'lesson_id' => $this->lesson->id]);

        $this->actingAs($this->student);

        Volt::test('pages.dashboard')->call('removeBookmark', $bookmark->id);

        $this->assertDatabaseHas('bookmarks', ['id' => $bookmark->id]);
    }
}
