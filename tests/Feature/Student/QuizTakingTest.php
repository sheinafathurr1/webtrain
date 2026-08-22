<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class QuizTakingTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected Course $course;

    protected Lesson $lesson;

    protected Quiz $quiz;

    protected Question $mcQuestion;

    protected QuestionOption $correctOption;

    protected QuestionOption $wrongOption;

    protected Question $saQuestion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create(['email_verified_at' => now()]);

        $track = Track::factory()->create();
        $this->course = Course::factory()->create(['track_id' => $track->id, 'lock_lessons_sequentially' => false]);
        $module = Module::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'type' => Lesson::TYPE_QUIZ,
            'is_published' => true,
        ]);

        $this->quiz = Quiz::factory()->create(['lesson_id' => $this->lesson->id]);

        $this->mcQuestion = Question::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'Apa kepanjangan HTML?',
            'explanation' => 'HTML = HyperText Markup Language',
            'order' => 1,
        ]);
        $this->correctOption = QuestionOption::factory()->create([
            'question_id' => $this->mcQuestion->id,
            'option_text' => 'HyperText Markup Language',
            'is_correct' => true,
        ]);
        $this->wrongOption = QuestionOption::factory()->create([
            'question_id' => $this->mcQuestion->id,
            'option_text' => 'High Tech Markup Language',
            'is_correct' => false,
        ]);

        $this->saQuestion = Question::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => Question::TYPE_SHORT_ANSWER,
            'question_text' => 'Tag untuk paragraf?',
            'correct_answer' => 'p',
            'order' => 2,
        ]);
    }

    public function test_student_can_view_quiz_questions(): void
    {
        $this->actingAs($this->student)
            ->get(route('lessons.show', [$this->course, $this->lesson]))
            ->assertOk()
            ->assertSee('Apa kepanjangan HTML?')
            ->assertSee('Tag untuk paragraf?');
    }

    public function test_submitting_with_all_correct_answers_scores_100(): void
    {
        $this->actingAs($this->student);

        $component = Volt::test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->set("quizAnswers.{$this->mcQuestion->id}.selected_option_id", $this->correctOption->id)
            ->set("quizAnswers.{$this->saQuestion->id}.answer_text", 'P')
            ->call('submitQuiz');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->student->id,
            'score' => 100,
            'correct_count' => 2,
            'total_questions' => 2,
        ]);
    }

    public function test_submitting_with_a_wrong_answer_scores_partial(): void
    {
        $this->actingAs($this->student);

        Volt::test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->set("quizAnswers.{$this->mcQuestion->id}.selected_option_id", $this->wrongOption->id)
            ->set("quizAnswers.{$this->saQuestion->id}.answer_text", 'p')
            ->call('submitQuiz');

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $this->quiz->id,
            'score' => 50,
            'correct_count' => 1,
        ]);

        $this->assertDatabaseHas('quiz_answers', [
            'question_id' => $this->mcQuestion->id,
            'selected_option_id' => $this->wrongOption->id,
            'is_correct' => false,
        ]);
    }

    public function test_submitting_without_answering_all_questions_is_rejected(): void
    {
        $this->actingAs($this->student);

        $component = Volt::test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->set("quizAnswers.{$this->mcQuestion->id}.selected_option_id", $this->correctOption->id)
            ->call('submitQuiz');

        $component->assertHasErrors('quizAnswers');

        $this->assertDatabaseMissing('quiz_attempts', ['quiz_id' => $this->quiz->id]);
    }

    public function test_student_can_retry_after_submitting(): void
    {
        $this->actingAs($this->student);

        $component = Volt::test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->set("quizAnswers.{$this->mcQuestion->id}.selected_option_id", $this->wrongOption->id)
            ->set("quizAnswers.{$this->saQuestion->id}.answer_text", 'p')
            ->call('submitQuiz');

        $component->call('retryQuiz')
            ->set("quizAnswers.{$this->mcQuestion->id}.selected_option_id", $this->correctOption->id)
            ->set("quizAnswers.{$this->saQuestion->id}.answer_text", 'p')
            ->call('submitQuiz');

        $this->assertEquals(2, \App\Models\QuizAttempt::where('quiz_id', $this->quiz->id)->count());
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $this->quiz->id,
            'score' => 100,
        ]);
    }

    public function test_revisiting_after_submission_shows_last_attempt_results(): void
    {
        $this->actingAs($this->student);

        Volt::test('pages.lessons.show', ['course' => $this->course, 'lesson' => $this->lesson])
            ->set("quizAnswers.{$this->mcQuestion->id}.selected_option_id", $this->correctOption->id)
            ->set("quizAnswers.{$this->saQuestion->id}.answer_text", 'p')
            ->call('submitQuiz');

        $this->get(route('lessons.show', [$this->course, $this->lesson]))
            ->assertOk()
            ->assertSee('100%')
            ->assertSee('HTML = HyperText Markup Language');
    }
}
