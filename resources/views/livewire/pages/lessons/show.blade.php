<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use function Livewire\Volt\{computed, layout, mount, state};

layout('layouts.app');

state(['course' => null, 'lesson' => null, 'showSolution' => false]);

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
});

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

                        @if ($lesson->exercise->starter_code)
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('Starter Code') }}</h3>
                                <pre class="bg-gray-900 text-gray-100 text-sm rounded-md p-4 overflow-x-auto"><code>{{ $lesson->exercise->starter_code }}</code></pre>
                            </div>
                        @endif

                        @if ($lesson->exercise->expected_output)
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('Expected Output') }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $lesson->exercise->expected_output }}</p>
                            </div>
                        @endif

                        @if ($lesson->exercise->solution_code)
                            <div>
                                <button type="button" wire:click="$toggle('showSolution')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $showSolution ? __('Sembunyikan Solusi') : __('Lihat Solusi') }}
                                </button>

                                @if ($showSolution)
                                    <pre class="mt-2 bg-gray-900 text-gray-100 text-sm rounded-md p-4 overflow-x-auto"><code>{{ $lesson->exercise->solution_code }}</code></pre>
                                @endif
                            </div>
                        @endif

                        <p class="text-xs text-gray-400">
                            {{ __('Editor & live preview interaktif untuk latihan ini hadir di Fase 4 (Code Playground).') }}
                        </p>
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
