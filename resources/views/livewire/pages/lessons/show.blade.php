<?php

use App\Models\Bookmark;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonComment;
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
    'newCommentBody' => '',
    'confirmingDeleteId' => null,
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

$isBookmarked = computed(fn () => $this->lesson->isBookmarkedBy(Auth::user()));

$toggleBookmark = function () {
    $existing = Bookmark::where('user_id', Auth::id())->where('lesson_id', $this->lesson->id)->first();

    if ($existing) {
        $existing->delete();

        return;
    }

    // firstOrCreate so a double-click can't throw on the
    // unique(user_id, lesson_id) constraint.
    Bookmark::firstOrCreate([
        'user_id' => Auth::id(),
        'lesson_id' => $this->lesson->id,
    ]);
};

// Capped so a heavily-discussed lesson can't force an unbounded load
// on every render; the newest 50 covers the active conversation. The
// header count is a separate cheap query so it stays accurate even
// past the cap.
$comments = computed(fn () => $this->lesson->comments()->with('user')->limit(50)->get());

$commentsCount = computed(fn () => $this->lesson->comments()->count());

$postComment = function () {
    $this->validate([
        'newCommentBody' => ['required', 'string', 'max:2000'],
    ]);

    LessonComment::create([
        'lesson_id' => $this->lesson->id,
        'user_id' => Auth::id(),
        'body' => trim($this->newCommentBody),
    ]);

    $this->newCommentBody = '';
};

$deleteComment = function () {
    $comment = LessonComment::find($this->confirmingDeleteId);

    if ($comment && ($comment->user_id === Auth::id() || Auth::user()->hasRole('Admin'))) {
        $comment->delete();
    }

    $this->confirmingDeleteId = null;
};

$toggleComplete = function () {
    $existing = UserProgress::where('user_id', Auth::id())->where('lesson_id', $this->lesson->id)->first();

    if ($existing) {
        $existing->delete();
        $this->completedLessonIds = array_values(array_diff($this->completedLessonIds, [$this->lesson->id]));

        return;
    }

    // firstOrCreate (not a plain create()) so a double-click doesn't
    // throw on the user_progress unique(user_id, lesson_id) constraint
    // — same pattern used by submitExercise/submitQuiz below.
    $progress = UserProgress::firstOrCreate(
        ['user_id' => Auth::id(), 'lesson_id' => $this->lesson->id],
        ['completed_at' => now()]
    );

    if ($progress->wasRecentlyCreated) {
        app(GamificationService::class)->recordLessonCompleted(Auth::user(), $this->lesson);
        $this->completedLessonIds[] = $this->lesson->id;
    }
};

$submitExercise = function () {
    $progress = UserProgress::firstOrCreate(
        ['user_id' => Auth::id(), 'lesson_id' => $this->lesson->id],
        ['completed_at' => now()]
    );

    if ($progress->wasRecentlyCreated) {
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

    $progress = UserProgress::firstOrCreate(
        ['user_id' => Auth::id(), 'lesson_id' => $this->lesson->id],
        ['completed_at' => now()]
    );

    if ($progress->wasRecentlyCreated) {
        app(GamificationService::class)->recordLessonCompleted(Auth::user(), $this->lesson);
        $this->completedLessonIds[] = $this->lesson->id;
    }

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
        <p class="text-sm text-ink-muted">
            <a href="{{ route('courses.show', $course) }}" wire:navigate class="hover:text-brand font-medium motion-safe:transition-colors duration-150">{{ $course->title }}</a>
            <span class="mx-1">/</span>
            <span class="text-ink-primary font-semibold">{{ $lesson->title }}</span>
        </p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 items-start">
                <!-- Lesson list sidebar -->
                <aside class="bg-surface border border-border rounded-2xl p-4 lg:sticky lg:top-6 order-2 lg:order-1">
                    <p class="text-xs font-bold uppercase tracking-widest text-ink-muted mb-3">{{ __('Lessons') }}</p>

                    @foreach ($course->modules as $module)
                        <div class="mb-4 last:mb-0">
                            <p class="text-xs font-bold text-ink-muted mb-1 truncate">{{ $module->title }}</p>
                            <ul class="space-y-0.5">
                                @foreach ($module->lessons as $navLesson)
                                    <li>
                                        @if ($navLesson->id === $lesson->id)
                                            <span class="flex items-center gap-2 truncate text-sm px-2.5 py-1.5 rounded-xl bg-gradient-to-r from-brand to-accent text-white font-semibold">{{ $navLesson->title }}</span>
                                        @elseif (in_array($navLesson->id, $completedLessonIds) || ! $course->isLessonLockedFor($navLesson, auth()->user(), $completedLessonIds))
                                            <a href="{{ route('lessons.show', [$course, $navLesson]) }}" wire:navigate class="flex items-center gap-2 truncate text-sm px-2.5 py-1.5 rounded-xl text-ink-secondary hover:text-brand hover:bg-brand/5 motion-safe:transition-colors duration-150">
                                                <span class="w-4 h-4 rounded-full {{ in_array($navLesson->id, $completedLessonIds) ? 'bg-brand/20 text-brand' : 'border border-border' }} flex items-center justify-center text-[9px] shrink-0">{{ in_array($navLesson->id, $completedLessonIds) ? '✓' : '' }}</span>
                                                <span class="truncate">{{ $navLesson->title }}</span>
                                            </a>
                                        @else
                                            <span class="flex items-center gap-2 truncate text-sm px-2.5 py-1.5 text-ink-muted" title="{{ __('Terkunci') }}">
                                                <span class="text-xs shrink-0">🔒</span>
                                                <span class="truncate">{{ $navLesson->title }}</span>
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
                    <div class="bg-surface border border-border rounded-2xl p-6">
                        <div class="flex items-start justify-between gap-3">
                            <x-badge color="brand" class="uppercase mb-2">{{ $lesson->type }}</x-badge>

                            <button
                                type="button"
                                wire:click="toggleBookmark"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold shrink-0 motion-safe:transition-colors duration-150 {{ $this->isBookmarked ? 'text-gold' : 'text-ink-muted hover:text-gold' }}"
                                title="{{ $this->isBookmarked ? __('Hapus dari tersimpan') : __('Simpan lesson ini') }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="{{ $this->isBookmarked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3.5A1.5 1.5 0 016.5 2h7A1.5 1.5 0 0115 3.5v14l-5-3-5 3v-14z" />
                                </svg>
                                <span class="hidden sm:inline">{{ $this->isBookmarked ? __('Tersimpan') : __('Simpan') }}</span>
                            </button>
                        </div>

                        <h1 class="font-display text-2xl sm:text-3xl font-extrabold mb-6 text-ink-primary">{{ $lesson->title }}</h1>

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

                                <x-badge color="accent" class="uppercase">{{ __('Bahasa') }}: {{ $lesson->exercise->language }}</x-badge>

                                @if ($lesson->exercise->expected_output)
                                    <div class="rounded-xl bg-gold/5 border border-gold/20 p-4">
                                        <h3 class="text-sm font-bold text-gold mb-1">{{ __('Expected Output') }}</h3>
                                        <p class="text-sm text-ink-secondary">{{ $lesson->exercise->expected_output }}</p>
                                    </div>
                                @endif

                                @if ($lesson->exercise->starter_code)
                                    <div
                                        wire:ignore
                                        data-playground
                                        x-data="codePlayground(@js($lesson->exercise->starter_code), @js($lesson->exercise->solution_code), @js($lesson->exercise->checks ?? []))"
                                        class="rounded-2xl border border-border bg-canvas p-3"
                                    >
                                        <div class="flex items-center justify-between mb-1 px-1">
                                            <h3 class="text-sm font-bold text-ink-secondary">{{ __('Playground') }}</h3>
                                            <div class="flex gap-3 text-xs">
                                                @if ($lesson->exercise->solution_code)
                                                    <button type="button" @click="loadSolution()" class="font-semibold text-brand hover:text-brand-dark motion-safe:transition-colors duration-150">{{ __('Muat Solusi') }}</button>
                                                @endif
                                                <button type="button" @click="resetCode()" class="font-semibold text-ink-secondary hover:text-ink-primary motion-safe:transition-colors duration-150">{{ __('↺ Reset') }}</button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                            <div x-ref="editor" class="border border-border rounded-xl overflow-auto text-sm" style="height: 22rem;"></div>
                                            <iframe x-ref="preview" sandbox="allow-scripts" title="{{ __('Preview') }}" class="w-full border border-border rounded-xl bg-white" style="height: 22rem;"></iframe>
                                        </div>

                                        <template x-if="checks.length">
                                            <div class="mt-3 px-1">
                                                <button
                                                    type="button"
                                                    @click="runChecks()"
                                                    :disabled="checking"
                                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-dark dark:bg-brand text-white text-sm font-display font-semibold disabled:opacity-50 motion-safe:transition-opacity duration-150"
                                                >
                                                    <span x-show="!checking">{{ __('✓ Cek Jawaban') }}</span>
                                                    <span x-show="checking" x-cloak>{{ __('Mengecek...') }}</span>
                                                </button>

                                                <template x-if="checkResults">
                                                    <div class="mt-3 space-y-1.5">
                                                        <template x-for="(result, i) in checkResults" :key="i">
                                                            <p class="text-sm flex items-center gap-2" :class="result.pass ? 'text-brand' : 'text-danger'">
                                                                <template x-if="result.pass">
                                                                    <span>✓ {{ __('Benar') }}</span>
                                                                </template>
                                                                <template x-if="!result.pass">
                                                                    <span>✗ {{ __('Belum sesuai') }}: <span x-text="result.selector"></span></span>
                                                                </template>
                                                            </p>
                                                        </template>

                                                        <template x-if="checkResults.every((r) => r.pass)">
                                                            <p class="mt-2 text-sm font-semibold text-brand">🎉 {{ __('Semua benar! Lesson ini otomatis ditandai selesai.') }}</p>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                @endif

                                @if ($lesson->exercise->solution_code)
                                    <div>
                                        <button type="button" wire:click="$toggle('showSolution')" class="text-sm font-semibold text-brand hover:text-brand-dark motion-safe:transition-colors duration-150">
                                            {{ $showSolution ? __('Sembunyikan Solusi (teks)') : __('Lihat Solusi (teks)') }}
                                        </button>

                                        @if ($showSolution)
                                            <pre class="mt-2 bg-canvas text-ink-primary text-sm rounded-xl p-4 overflow-x-auto border border-border"><code>{{ $lesson->exercise->solution_code }}</code></pre>
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
                                    <div class="rounded-2xl p-5 bg-gradient-to-br from-brand to-accent text-white">
                                        <p class="font-display text-4xl font-extrabold tabular-nums">{{ $quizAttempt->score }}%</p>
                                        <p class="text-sm text-white/90 font-medium">
                                            {{ $quizAttempt->correct_count }} {{ __('dari') }} {{ $quizAttempt->total_questions }} {{ __('soal benar') }}
                                        </p>
                                    </div>

                                    <div class="space-y-4">
                                        @foreach ($quizAttempt->answers as $answer)
                                            <div class="border rounded-xl p-4 {{ $answer->is_correct ? 'border-brand/30 bg-brand/5' : 'border-danger/30 bg-danger/5' }}">
                                                <p class="font-medium text-ink-primary">
                                                    <x-badge :color="$answer->is_correct ? 'brand' : 'danger'">{{ $answer->is_correct ? __('Benar') : __('Salah') }}</x-badge>
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
                                            <p class="text-sm text-danger font-medium">{{ $message }}</p>
                                        @enderror

                                        @foreach ($lesson->quiz->questions as $question)
                                            <div class="border border-border rounded-xl p-4">
                                                <p class="font-medium mb-2 text-ink-primary">{{ $loop->iteration }}. {{ $question->question_text }}</p>

                                                @if ($question->type === Question::TYPE_MULTIPLE_CHOICE)
                                                    <div class="space-y-2">
                                                        @foreach ($question->options as $option)
                                                            <label class="flex items-center gap-2 text-sm text-ink-secondary">
                                                                <input type="radio" wire:model="quizAnswers.{{ $question->id }}.selected_option_id" value="{{ $option->id }}" class="border-border text-brand focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
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

                    <div class="bg-surface border border-border rounded-2xl p-6 flex flex-wrap items-center justify-between gap-4">
                        @if ($lesson->type === Lesson::TYPE_QUIZ)
                            @if ($this->isCompleted)
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand/10 text-sm font-semibold text-brand">
                                    <span>✓</span> {{ __('Selesai') }}
                                </span>
                            @else
                                <span class="text-sm text-ink-muted">{{ __('Submit quiz di atas untuk menyelesaikan lesson ini.') }}</span>
                            @endif
                        @elseif ($lesson->type === Lesson::TYPE_EXERCISE && $lesson->exercise?->hasChecks())
                            @if ($this->isCompleted)
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand/10 text-sm font-semibold text-brand">
                                    <span>✓</span> {{ __('Selesai') }}
                                </span>
                            @else
                                <span class="text-sm text-ink-muted">{{ __('Klik "Cek Jawaban" di atas sampai semua benar untuk menyelesaikan lesson ini.') }}</span>
                            @endif
                        @elseif ($this->isCompleted)
                            <button wire:click="toggleComplete" type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand/10 text-sm font-semibold text-brand hover:bg-brand/20 motion-safe:transition-colors duration-150">
                                <span>✓</span> {{ __('Selesai — klik untuk batalkan') }}
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
                                @if ($course->isLessonLockedFor($this->nextLesson, auth()->user(), $completedLessonIds))
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

                    <div class="bg-surface border border-border rounded-2xl p-6">
                        <h3 class="font-display font-bold text-lg text-ink-primary mb-4">
                            {{ __('Diskusi') }} <span class="text-ink-muted font-normal text-base">({{ $this->commentsCount }})</span>
                        </h3>

                        <form wire:submit="postComment" class="mb-6">
                            <textarea
                                wire:model="newCommentBody"
                                rows="3"
                                placeholder="{{ __('Tulis pertanyaan atau komentar tentang lesson ini...') }}"
                                class="w-full rounded-xl border-border bg-canvas text-sm text-ink-primary placeholder:text-ink-muted focus:border-brand focus:ring-brand"
                            ></textarea>
                            @error('newCommentBody')
                                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                            @enderror

                            <div class="mt-2 flex justify-end">
                                <x-primary-button type="submit">{{ __('Kirim Komentar') }}</x-primary-button>
                            </div>
                        </form>

                        @forelse ($this->comments as $comment)
                            <div class="flex items-start gap-3 py-3 border-t first:border-t-0 border-border">
                                <span class="w-8 h-8 rounded-full bg-gradient-to-br from-brand to-accent text-white flex items-center justify-center text-xs font-display font-bold shrink-0">
                                    {{ Str::of($comment->user->name)->substr(0, 1)->upper() }}
                                </span>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-semibold text-sm text-ink-primary">{{ $comment->user->name }}</span>
                                        <span class="text-xs text-ink-muted">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-sm text-ink-secondary mt-0.5 whitespace-pre-wrap break-words">{{ $comment->body }}</p>
                                </div>

                                @if ($comment->user_id === auth()->id() || auth()->user()->hasRole('Admin'))
                                    <button
                                        type="button"
                                        wire:click="confirmingDeleteId = {{ $comment->id }}"
                                        class="text-xs font-semibold text-ink-muted hover:text-danger motion-safe:transition-colors duration-150 shrink-0"
                                    >
                                        {{ __('Hapus') }}
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-ink-secondary">{{ __('Belum ada komentar. Jadilah yang pertama bertanya atau berbagi!') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-confirm-delete-modal
        title="{{ __('Hapus komentar?') }}"
        message="{{ __('Komentar yang dihapus tidak bisa dikembalikan.') }}"
        action="deleteComment"
    />
</div>
