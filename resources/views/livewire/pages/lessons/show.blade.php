<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use function Livewire\Volt\{computed, layout, mount, state};

layout('layouts.app');

state([
    'course' => null,
    'lesson' => null,
    'showSolution' => false,
    'quizAnswers' => [],
    'quizAttempt' => null,
    'retaking' => false,
]);

mount(function (Course $course, Lesson $lesson) {
    abort_unless($course->is_published && $lesson->is_published, 404);
    abort_unless($lesson->module->course_id === $course->id, 404);

    abort_if(
        $course->isLessonLockedFor($lesson, Auth::user()),
        403,
        'Lesson ini masih terkunci. Selesaikan lesson sebelumnya terlebih dahulu.'
    );

    $lesson->load('exercise');

    $this->course = $course;
    $this->lesson = $lesson;

    if ($lesson->type === Lesson::TYPE_QUIZ) {
        $lesson->load('quiz.questions.options');

        if ($lesson->quiz) {
            $this->quizAttempt = QuizAttempt::with(['answers.question.options', 'answers.selectedOption'])
                ->where('quiz_id', $lesson->quiz->id)
                ->where('user_id', Auth::id())
                ->latest('submitted_at')
                ->first();

            if (! $this->quizAttempt) {
                $this->resetQuizAnswers();
            }
        }
    }
});

$resetQuizAnswers = function () {
    $this->quizAnswers = [];

    foreach ($this->lesson->quiz->questions as $question) {
        $this->quizAnswers[$question->id] = ['selected_option_id' => null, 'answer_text' => ''];
    }
};

$orderedLessons = computed(fn () => $this->course->publishedLessons()->values());

$currentIndex = computed(fn () => $this->orderedLessons->search(fn (Lesson $l) => $l->id === $this->lesson->id));

$previousLesson = computed(fn () => $this->currentIndex > 0 ? $this->orderedLessons->get($this->currentIndex - 1) : null);

$nextLesson = computed(fn () => $this->orderedLessons->get($this->currentIndex + 1));

$isCompleted = computed(fn () => $this->lesson->isCompletedBy(Auth::user()));

$toggleComplete = function () {
    $progress = UserProgress::where('user_id', Auth::id())->where('lesson_id', $this->lesson->id);

    if ($progress->exists()) {
        $progress->delete();
    } else {
        UserProgress::create([
            'user_id' => Auth::id(),
            'lesson_id' => $this->lesson->id,
            'completed_at' => now(),
        ]);
    }
};

$submitQuiz = function () {
    $questions = $this->lesson->quiz->questions;

    foreach ($questions as $question) {
        $answer = $this->quizAnswers[$question->id] ?? [];

        $unanswered = $question->type === Question::TYPE_MULTIPLE_CHOICE
            ? blank($answer['selected_option_id'] ?? null)
            : blank($answer['answer_text'] ?? null);

        if ($unanswered) {
            $this->addError('quizAnswers', __('Jawab semua soal sebelum submit.'));

            return;
        }
    }

    $correctCount = 0;
    $graded = [];

    foreach ($questions as $question) {
        $answer = $this->quizAnswers[$question->id];
        $selectedOptionId = $answer['selected_option_id'] ? (int) $answer['selected_option_id'] : null;
        $isCorrect = $question->isAnswerCorrect($selectedOptionId, $answer['answer_text'] ?: null);

        if ($isCorrect) {
            $correctCount++;
        }

        $graded[] = [
            'question_id' => $question->id,
            'selected_option_id' => $selectedOptionId,
            'answer_text' => $answer['answer_text'] ?: null,
            'is_correct' => $isCorrect,
        ];
    }

    $total = $questions->count();

    $attempt = QuizAttempt::create([
        'quiz_id' => $this->lesson->quiz->id,
        'user_id' => Auth::id(),
        'score' => $total > 0 ? (int) round($correctCount / $total * 100) : 0,
        'correct_count' => $correctCount,
        'total_questions' => $total,
        'submitted_at' => now(),
    ]);

    foreach ($graded as $row) {
        $attempt->answers()->create($row);
    }

    $this->quizAttempt = $attempt->load(['answers.question.options', 'answers.selectedOption']);
    $this->retaking = false;
};

$retryQuiz = function () {
    $this->quizAttempt = null;
    $this->retaking = true;
    $this->resetQuizAnswers();
};

?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <a href="{{ route('courses.show', $course) }}" wire:navigate class="text-gray-400 hover:underline">{{ $course->title }}</a>
            / {{ $lesson->title }}
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h1 class="text-2xl font-bold mb-6">{{ $lesson->title }}</h1>

                @if ($lesson->type === Lesson::TYPE_TEXT)
                    <div class="prose dark:prose-invert max-w-none">
                        {!! Str::markdown($lesson->content ?? '') !!}
                    </div>
                @elseif ($lesson->type === Lesson::TYPE_VIDEO)
                    @if ($lesson->youtubeEmbedUrl())
                        <div class="aspect-video">
                            <iframe
                                class="w-full h-full rounded-md"
                                src="{{ $lesson->youtubeEmbedUrl() }}"
                                title="{{ $lesson->title }}"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">{{ __('URL video tidak valid.') }}</p>
                    @endif
                @elseif ($lesson->type === Lesson::TYPE_EXERCISE && $lesson->exercise)
                    <div class="space-y-4">
                        <div class="prose dark:prose-invert max-w-none">
                            <p>{{ $lesson->exercise->instructions }}</p>
                        </div>

                        <p class="text-xs uppercase text-gray-400 tracking-wide">{{ __('Bahasa') }}: {{ $lesson->exercise->language }}</p>

                        @if ($lesson->exercise->expected_output)
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('Expected Output') }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $lesson->exercise->expected_output }}</p>
                            </div>
                        @endif

                        @if ($lesson->exercise->starter_code)
                            <div
                                wire:ignore
                                data-playground
                                x-data="codePlayground(@js($lesson->exercise->starter_code), @js($lesson->exercise->solution_code))"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Playground') }}</h3>
                                    <div class="flex gap-3 text-xs">
                                        @if ($lesson->exercise->solution_code)
                                            <button type="button" @click="loadSolution()" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Muat Solusi') }}</button>
                                        @endif
                                        <button type="button" @click="resetCode()" class="text-gray-500 dark:text-gray-400 hover:underline">{{ __('Reset') }}</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                    <div x-ref="editor" class="border border-gray-200 dark:border-gray-700 rounded-md overflow-auto text-sm" style="height: 22rem;"></div>
                                    <iframe x-ref="preview" sandbox="allow-scripts" title="{{ __('Preview') }}" class="w-full border border-gray-200 dark:border-gray-700 rounded-md bg-white" style="height: 22rem;"></iframe>
                                </div>
                            </div>
                        @endif

                        @if ($lesson->exercise->solution_code)
                            <div>
                                <button type="button" wire:click="$toggle('showSolution')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $showSolution ? __('Sembunyikan Solusi (teks)') : __('Lihat Solusi (teks)') }}
                                </button>

                                @if ($showSolution)
                                    <pre class="mt-2 bg-gray-900 text-gray-100 text-sm rounded-md p-4 overflow-x-auto"><code>{{ $lesson->exercise->solution_code }}</code></pre>
                                @endif
                            </div>
                        @endif
                    </div>
                @elseif ($lesson->type === Lesson::TYPE_QUIZ && $lesson->quiz)
                    <div class="space-y-4">
                        @if ($lesson->quiz->description)
                            <div class="prose dark:prose-invert max-w-none">
                                <p>{{ $lesson->quiz->description }}</p>
                            </div>
                        @endif

                        @if ($lesson->quiz->questions->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Quiz ini belum punya soal.') }}</p>
                        @elseif ($quizAttempt && ! $retaking)
                            <div class="bg-indigo-50 dark:bg-indigo-900/30 rounded-md p-4">
                                <p class="text-2xl font-bold text-indigo-700 dark:text-indigo-300">{{ $quizAttempt->score }}%</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $quizAttempt->correct_count }} {{ __('dari') }} {{ $quizAttempt->total_questions }} {{ __('soal benar') }}
                                </p>
                            </div>

                            <div class="space-y-4">
                                @foreach ($quizAttempt->answers as $answer)
                                    <div class="border rounded-md p-4 {{ $answer->is_correct ? 'border-green-300 dark:border-green-700' : 'border-red-300 dark:border-red-700' }}">
                                        <p class="font-medium">{{ $loop->iteration }}. {{ $answer->question->question_text }}</p>

                                        @if ($answer->question->type === Question::TYPE_MULTIPLE_CHOICE)
                                            <p class="text-sm mt-1 {{ $answer->is_correct ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ __('Jawabanmu') }}: {{ $answer->selectedOption->option_text ?? '-' }}
                                            </p>
                                            @unless ($answer->is_correct)
                                                <p class="text-sm text-green-600 dark:text-green-400">
                                                    {{ __('Jawaban benar') }}: {{ $answer->question->options->firstWhere('is_correct', true)?->option_text }}
                                                </p>
                                            @endunless
                                        @else
                                            <p class="text-sm mt-1 {{ $answer->is_correct ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ __('Jawabanmu') }}: {{ $answer->answer_text }}
                                            </p>
                                            @unless ($answer->is_correct)
                                                <p class="text-sm text-green-600 dark:text-green-400">{{ __('Jawaban benar') }}: {{ $answer->question->correct_answer }}</p>
                                            @endunless
                                        @endif

                                        @if ($answer->question->explanation)
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                <span class="font-medium">{{ __('Pembahasan') }}:</span> {{ $answer->question->explanation }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <x-secondary-button type="button" wire:click="retryQuiz">{{ __('Ulangi Quiz') }}</x-secondary-button>
                        @else
                            <form wire:submit="submitQuiz" class="space-y-6">
                                @error('quizAnswers')
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror

                                @foreach ($lesson->quiz->questions as $question)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4">
                                        <p class="font-medium mb-2">{{ $loop->iteration }}. {{ $question->question_text }}</p>

                                        @if ($question->type === Question::TYPE_MULTIPLE_CHOICE)
                                            <div class="space-y-2">
                                                @foreach ($question->options as $option)
                                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                        <input type="radio" wire:model="quizAnswers.{{ $question->id }}.selected_option_id" value="{{ $option->id }}">
                                                        {{ $option->option_text }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        @else
                                            <x-text-input wire:model="quizAnswers.{{ $question->id }}.answer_text" class="block w-full" type="text" placeholder="{{ __('Jawaban kamu') }}" />
                                        @endif
                                    </div>
                                @endforeach

                                <x-primary-button type="submit">{{ __('Submit Quiz') }}</x-primary-button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 flex flex-wrap items-center justify-between gap-4">
                @if ($this->isCompleted)
                    <button wire:click="toggleComplete" type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 text-sm font-medium">
                        ✓ {{ __('Selesai — klik untuk batalkan') }}
                    </button>
                @else
                    <x-primary-button wire:click="toggleComplete" type="button">{{ __('Tandai Selesai') }}</x-primary-button>
                @endif

                <div class="flex gap-3">
                    @if ($this->previousLesson)
                        <a href="{{ route('lessons.show', [$course, $this->previousLesson]) }}" wire:navigate>
                            <x-secondary-button type="button">{{ __('← Sebelumnya') }}</x-secondary-button>
                        </a>
                    @endif

                    @if ($this->nextLesson)
                        @if ($course->isLessonLockedFor($this->nextLesson, auth()->user()))
                            <x-secondary-button type="button" disabled>{{ __('Selanjutnya →') }}</x-secondary-button>
                        @else
                            <a href="{{ route('lessons.show', [$course, $this->nextLesson]) }}" wire:navigate>
                                <x-primary-button type="button">{{ __('Selanjutnya →') }}</x-primary-button>
                            </a>
                        @endif
                    @else
                        <a href="{{ route('courses.show', $course) }}" wire:navigate>
                            <x-primary-button type="button">{{ __('Selesai — Kembali ke Course') }}</x-primary-button>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
