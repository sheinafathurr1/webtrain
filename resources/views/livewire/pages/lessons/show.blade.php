<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use App\Services\GamificationService;
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
    'completedLessonIds' => [],
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
    $course->load(['modules.lessons' => fn ($query) => $query->where('is_published', true)]);

    $this->course = $course;
    $this->lesson = $lesson;

    $this->completedLessonIds = UserProgress::where('user_id', Auth::id())
        ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
        ->pluck('lesson_id')
        ->all();

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
        $this->completedLessonIds = array_values(array_diff($this->completedLessonIds, [$this->lesson->id]));
    } else {
        UserProgress::create([
            'user_id' => Auth::id(),
            'lesson_id' => $this->lesson->id,
            'completed_at' => now(),
        ]);

        app(GamificationService::class)->recordLessonCompleted(Auth::user(), $this->lesson);
        $this->completedLessonIds[] = $this->lesson->id;
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

    app(GamificationService::class)->recordQuizSubmitted(Auth::user());
};

$retryQuiz = function () {
    $this->quizAttempt = null;
    $this->retaking = true;
    $this->resetQuizAnswers();
};

?>

<div>
    <x-slot:header>
        <p class="terminal-prompt">
            <span class="seg-user">guest@webtrain</span><span class="seg-sep">:~$</span>
            <a href="{{ route('courses.show', $course) }}" wire:navigate class="hover:text-ink-primary transition-colors duration-150">cd {{ $course->slug }}</a>/<span class="seg-cmd">{{ $lesson->slug }}</span>
        </p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 items-start">
                <!-- File-explorer sidebar -->
                <aside class="bg-surface border border-border-subtle rounded p-4 lg:sticky lg:top-6 order-2 lg:order-1">
                    <p class="font-mono text-xs uppercase tracking-widest text-ink-muted mb-3">{{ __('Lessons') }}</p>

                    @foreach ($course->modules as $module)
                        <div class="mb-4 last:mb-0">
                            <p class="tree-branch text-xs mb-1 truncate">{{ $module->title }}</p>
                            <ul class="space-y-0.5">
                                @foreach ($module->lessons as $navLesson)
                                    <li>
                                        @if ($navLesson->id === $lesson->id)
                                            <span class="tree-node-active block truncate text-sm px-2 py-1">{{ $navLesson->title }}</span>
                                        @elseif (in_array($navLesson->id, $completedLessonIds) || ! $course->isLessonLockedFor($navLesson, auth()->user()))
                                            <a href="{{ route('lessons.show', [$course, $navLesson]) }}" wire:navigate class="block truncate text-sm px-2 py-1 rounded text-ink-secondary hover:text-ink-primary hover:bg-canvas transition-colors duration-150">
                                                <span class="font-mono text-xs text-ink-muted">{{ in_array($navLesson->id, $completedLessonIds) ? '[x]' : '[ ]' }}</span>
                                                {{ $navLesson->title }}
                                            </a>
                                        @else
                                            <span class="block truncate text-sm px-2 py-1 text-ink-muted" title="{{ __('Terkunci') }}">
                                                <span class="font-mono text-xs">[locked]</span>
                                                {{ $navLesson->title }}
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </aside>

                <!-- Main content -->
                <div class="space-y-6 min-w-0 order-1 lg:order-2">
                    <div class="bg-surface border border-border-subtle rounded p-6">
                        <p class="font-mono text-xs uppercase tracking-widest text-ink-muted mb-2">{{ $lesson->type }}</p>
                        <h1 class="font-display text-2xl font-bold mb-6 text-ink-primary">{{ $lesson->title }}</h1>

                        @if ($lesson->type === Lesson::TYPE_TEXT)
                            <div class="prose prose-neutral dark:prose-invert max-w-none">
                                {!! Str::markdown($lesson->content ?? '') !!}
                            </div>
                        @elseif ($lesson->type === Lesson::TYPE_VIDEO)
                            @if ($lesson->youtubeEmbedUrl())
                                <div class="aspect-video">
                                    <iframe
                                        class="w-full h-full rounded"
                                        src="{{ $lesson->youtubeEmbedUrl() }}"
                                        title="{{ $lesson->title }}"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                    ></iframe>
                                </div>
                            @else
                                <p class="text-ink-secondary">{{ __('URL video tidak valid.') }}</p>
                            @endif
                        @elseif ($lesson->type === Lesson::TYPE_EXERCISE && $lesson->exercise)
                            <div class="space-y-4">
                                <div class="prose prose-neutral dark:prose-invert max-w-none">
                                    <p>{{ $lesson->exercise->instructions }}</p>
                                </div>

                                <p class="font-mono text-xs uppercase text-ink-muted tracking-wide">{{ __('Bahasa') }}: {{ $lesson->exercise->language }}</p>

                                @if ($lesson->exercise->expected_output)
                                    <div>
                                        <h3 class="text-sm font-semibold text-ink-secondary mb-1">{{ __('Expected Output') }}</h3>
                                        <p class="text-sm text-ink-secondary">{{ $lesson->exercise->expected_output }}</p>
                                    </div>
                                @endif

                                @if ($lesson->exercise->starter_code)
                                    <div
                                        wire:ignore
                                        data-playground
                                        x-data="codePlayground(@js($lesson->exercise->starter_code), @js($lesson->exercise->solution_code))"
                                    >
                                        <div class="flex items-center justify-between mb-1">
                                            <h3 class="text-sm font-semibold text-ink-secondary">{{ __('Playground') }}</h3>
                                            <div class="flex gap-3 text-xs">
                                                @if ($lesson->exercise->solution_code)
                                                    <button type="button" @click="loadSolution()" class="text-ink-secondary hover:text-ink-primary underline transition-colors duration-150">{{ __('Muat Solusi') }}</button>
                                                @endif
                                                <button type="button" @click="resetCode()" class="text-ink-secondary hover:text-ink-primary underline transition-colors duration-150">{{ __('Reset') }}</button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                            <div x-ref="editor" class="border border-border-subtle rounded overflow-auto text-sm" style="height: 22rem;"></div>
                                            <iframe x-ref="preview" sandbox="allow-scripts" title="{{ __('Preview') }}" class="w-full border border-border-subtle rounded bg-white" style="height: 22rem;"></iframe>
                                        </div>
                                    </div>
                                @endif

                                @if ($lesson->exercise->solution_code)
                                    <div>
                                        <button type="button" wire:click="$toggle('showSolution')" class="text-sm text-ink-secondary hover:text-ink-primary underline transition-colors duration-150">
                                            {{ $showSolution ? __('Sembunyikan Solusi (teks)') : __('Lihat Solusi (teks)') }}
                                        </button>

                                        @if ($showSolution)
                                            <pre class="mt-2 bg-canvas text-ink-primary text-sm rounded p-4 overflow-x-auto border border-border-subtle"><code>{{ $lesson->exercise->solution_code }}</code></pre>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @elseif ($lesson->type === Lesson::TYPE_QUIZ && $lesson->quiz)
                            <div class="space-y-4">
                                @if ($lesson->quiz->description)
                                    <div class="prose prose-neutral dark:prose-invert max-w-none">
                                        <p>{{ $lesson->quiz->description }}</p>
                                    </div>
                                @endif

                                @if ($lesson->quiz->questions->isEmpty())
                                    <p class="text-sm text-ink-secondary">{{ __('Quiz ini belum punya soal.') }}</p>
                                @elseif ($quizAttempt && ! $retaking)
                                    <div class="bg-canvas border border-border-subtle rounded p-4">
                                        <p class="font-mono text-2xl font-bold text-ink-primary tabular-nums">{{ $quizAttempt->score }}%</p>
                                        <p class="text-sm text-ink-secondary">
                                            {{ $quizAttempt->correct_count }} {{ __('dari') }} {{ $quizAttempt->total_questions }} {{ __('soal benar') }}
                                        </p>
                                    </div>

                                    <div class="space-y-4">
                                        @foreach ($quizAttempt->answers as $answer)
                                            <div class="border border-border-subtle rounded p-4">
                                                <p class="font-medium text-ink-primary">
                                                    <span class="font-mono text-xs {{ $answer->is_correct ? 'text-ink-primary' : 'text-danger' }}">{{ $answer->is_correct ? '[correct]' : '[incorrect]' }}</span>
                                                    {{ $loop->iteration }}. {{ $answer->question->question_text }}
                                                </p>

                                                @if ($answer->question->type === Question::TYPE_MULTIPLE_CHOICE)
                                                    <p class="text-sm mt-1 text-ink-secondary">
                                                        {{ __('Jawabanmu') }}: {{ $answer->selectedOption->option_text ?? '-' }}
                                                    </p>
                                                    @unless ($answer->is_correct)
                                                        <p class="text-sm text-ink-secondary">
                                                            {{ __('Jawaban benar') }}: {{ $answer->question->options->firstWhere('is_correct', true)?->option_text }}
                                                        </p>
                                                    @endunless
                                                @else
                                                    <p class="text-sm mt-1 text-ink-secondary">
                                                        {{ __('Jawabanmu') }}: {{ $answer->answer_text }}
                                                    </p>
                                                    @unless ($answer->is_correct)
                                                        <p class="text-sm text-ink-secondary">{{ __('Jawaban benar') }}: {{ $answer->question->correct_answer }}</p>
                                                    @endunless
                                                @endif

                                                @if ($answer->question->explanation)
                                                    <p class="text-sm text-ink-muted mt-2">
                                                        <span class="font-medium text-ink-secondary">{{ __('Pembahasan') }}:</span> {{ $answer->question->explanation }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    <x-secondary-button type="button" wire:click="retryQuiz">{{ __('Ulangi Quiz') }}</x-secondary-button>
                                @else
                                    <form wire:submit="submitQuiz" class="space-y-6">
                                        @error('quizAnswers')
                                            <p class="text-sm text-danger">{{ $message }}</p>
                                        @enderror

                                        @foreach ($lesson->quiz->questions as $question)
                                            <div class="border border-border-subtle rounded p-4">
                                                <p class="font-medium mb-2 text-ink-primary">{{ $loop->iteration }}. {{ $question->question_text }}</p>

                                                @if ($question->type === Question::TYPE_MULTIPLE_CHOICE)
                                                    <div class="space-y-2">
                                                        @foreach ($question->options as $option)
                                                            <label class="flex items-center gap-2 text-sm text-ink-secondary">
                                                                <input type="radio" wire:model="quizAnswers.{{ $question->id }}.selected_option_id" value="{{ $option->id }}" class="border-border-interactive text-ink-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-primary">
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

                    <div class="bg-surface border border-border-subtle rounded p-6 flex flex-wrap items-center justify-between gap-4">
                        @if ($this->isCompleted)
                            <button wire:click="toggleComplete" type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded border border-border-interactive text-sm font-medium text-ink-primary hover:border-ink-primary transition-colors duration-150">
                                <span class="font-mono text-xs">[done]</span> {{ __('Selesai — klik untuk batalkan') }}
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
    </div>
</div>
