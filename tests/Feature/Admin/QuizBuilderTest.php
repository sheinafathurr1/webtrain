<?php

namespace Tests\Feature\Admin;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class QuizBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $this->admin->assignRole('Admin');

        $module = Module::factory()->create();
        $this->lesson = Lesson::factory()->create(['module_id' => $module->id, 'type' => Lesson::TYPE_QUIZ]);
    }

    public function test_visiting_the_builder_creates_a_quiz_for_the_lesson(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.quizzes.builder', $this->lesson))
            ->assertOk();

        $this->assertDatabaseHas('quizzes', ['lesson_id' => $this->lesson->id]);
    }

    public function test_admin_can_add_a_multiple_choice_question(): void
    {
        $this->actingAs($this->admin);

        $component = Volt::test('pages.admin.quizzes.builder', ['lesson' => $this->lesson])
            ->set('questionText', 'Apa kepanjangan HTML?')
            ->set('qType', Question::TYPE_MULTIPLE_CHOICE)
            ->set('options.0.text', 'HyperText Markup Language')
            ->set('options.1.text', 'High Tech Modern Language')
            ->set('correctOptionIndex', 0)
            ->call('saveQuestion');

        $component->assertHasNoErrors();

        $question = Question::where('question_text', 'Apa kepanjangan HTML?')->firstOrFail();
        $this->assertEquals(Question::TYPE_MULTIPLE_CHOICE, $question->type);
        $this->assertCount(2, $question->options);
        $this->assertTrue($question->options->firstWhere('option_text', 'HyperText Markup Language')->is_correct);
        $this->assertFalse($question->options->firstWhere('option_text', 'High Tech Modern Language')->is_correct);
    }

    public function test_multiple_choice_requires_a_correct_option_selected(): void
    {
        $this->actingAs($this->admin);

        Volt::test('pages.admin.quizzes.builder', ['lesson' => $this->lesson])
            ->set('questionText', 'Soal tanpa jawaban benar')
            ->set('qType', Question::TYPE_MULTIPLE_CHOICE)
            ->set('options.0.text', 'A')
            ->set('options.1.text', 'B')
            ->call('saveQuestion')
            ->assertHasErrors('correctOptionIndex');

        $this->assertDatabaseMissing('questions', ['question_text' => 'Soal tanpa jawaban benar']);
    }

    public function test_admin_can_add_a_short_answer_question(): void
    {
        $this->actingAs($this->admin);

        Volt::test('pages.admin.quizzes.builder', ['lesson' => $this->lesson])
            ->set('questionText', 'Tag untuk paragraf?')
            ->set('qType', Question::TYPE_SHORT_ANSWER)
            ->set('correctAnswer', 'p')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $question = Question::where('question_text', 'Tag untuk paragraf?')->firstOrFail();
        $this->assertEquals('p', $question->correct_answer);
        $this->assertCount(0, $question->options);
    }

    public function test_non_admin_cannot_access_quiz_builder(): void
    {
        $student = User::factory()->create(['email_verified_at' => now()]);
        $student->assignRole('Student');

        $this->actingAs($student)
            ->get(route('admin.quizzes.builder', $this->lesson))
            ->assertForbidden();
    }
}
