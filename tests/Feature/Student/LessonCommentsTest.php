<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\Module;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LessonCommentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected Course $course;

    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->student = User::factory()->create(['email_verified_at' => now()]);
        $this->student->assignRole('Student');

        $track = Track::factory()->create();
        $this->course = Course::factory()->create(['track_id' => $track->id, 'lock_lessons_sequentially' => false]);
        $module = Module::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'type' => Lesson::TYPE_TEXT,
            'is_published' => true,
        ]);
    }

    public function test_student_can_post_a_comment(): void
    {
        Volt::actingAs($this->student)
            ->test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->set('newCommentBody', 'Kenapa CSS margin bisa collapse ya?')
            ->call('postComment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('lesson_comments', [
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'body' => 'Kenapa CSS margin bisa collapse ya?',
        ]);
    }

    public function test_comment_body_is_required(): void
    {
        Volt::actingAs($this->student)
            ->test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->set('newCommentBody', '   ')
            ->call('postComment')
            ->assertHasErrors(['newCommentBody' => 'required']);
    }

    public function test_owner_can_delete_their_own_comment(): void
    {
        $comment = LessonComment::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'body' => 'Komentar saya',
        ]);

        Volt::actingAs($this->student)
            ->test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->set('confirmingDeleteId', $comment->id)
            ->call('deleteComment');

        $this->assertDatabaseMissing('lesson_comments', ['id' => $comment->id]);
    }

    public function test_other_student_cannot_delete_someone_elses_comment(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $owner->assignRole('Student');

        $comment = LessonComment::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $owner->id,
            'body' => 'Komentar orang lain',
        ]);

        Volt::actingAs($this->student)
            ->test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->set('confirmingDeleteId', $comment->id)
            ->call('deleteComment');

        $this->assertDatabaseHas('lesson_comments', ['id' => $comment->id]);
    }

    public function test_admin_can_delete_any_comment(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $comment = LessonComment::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'body' => 'Komentar siswa',
        ]);

        Volt::actingAs($admin)
            ->test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->set('confirmingDeleteId', $comment->id)
            ->call('deleteComment');

        $this->assertDatabaseMissing('lesson_comments', ['id' => $comment->id]);
    }

    public function test_comments_render_on_the_lesson_page(): void
    {
        LessonComment::create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'body' => 'Komentar unik untuk dicari di halaman',
        ]);

        $this->actingAs($this->student)
            ->get(route('lessons.show', [$this->course, $this->lesson]))
            ->assertOk()
            ->assertSee('Komentar unik untuk dicari di halaman');
    }
}
