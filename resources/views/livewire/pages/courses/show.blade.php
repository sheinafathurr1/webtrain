<?php

use App\Models\Course;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\{layout, mount, state};

layout('layouts.app');

state(['course' => null, 'completedLessonIds' => []]);

mount(function (Course $course) {
    abort_unless($course->is_published, 404);

    $course->load(['track', 'modules.lessons' => fn ($query) => $query->where('is_published', true)]);

    $this->course = $course;

    $this->completedLessonIds = Auth::check()
        ? UserProgress::where('user_id', Auth::id())
            ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
            ->pluck('lesson_id')
            ->all()
        : [];
});

?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <a href="{{ route('courses.index') }}" wire:navigate class="text-gray-400 hover:underline">{{ __('Semua Course') }}</a>
            / {{ $course->title }}
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <p class="text-sm text-gray-400">{{ $course->track->title }}</p>
                <h1 class="text-2xl font-bold mt-1">{{ $course->title }}</h1>
                <p class="mt-3 text-gray-600 dark:text-gray-400">{{ $course->description }}</p>

                @php $percent = $course->progressPercentFor(auth()->user()); @endphp
                <div class="mt-6">
                    <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-1">
                        <span>{{ __('Progress') }}</span>
                        <span>{{ $percent }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                    </div>
                </div>

                @auth
                    @php $next = $course->nextLessonFor(auth()->user()); @endphp
                    @if ($next)
                        <a href="{{ route('lessons.show', [$course, $next]) }}" wire:navigate class="inline-block mt-6">
                            <x-primary-button>{{ $percent > 0 ? __('Lanjutkan Belajar') : __('Mulai Belajar') }}</x-primary-button>
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" wire:navigate class="inline-block mt-6">
                        <x-primary-button>{{ __('Login untuk Mulai Belajar') }}</x-primary-button>
                    </a>
                @endauth
            </div>

            @foreach ($course->modules as $module)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $module->title }}</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($module->lessons as $lesson)
                            @php $locked = $course->isLessonLockedFor($lesson, auth()->user()); @endphp
                            <li class="px-6 py-3 flex items-center justify-between text-sm">
                                <div class="flex items-center gap-3">
                                    @if (in_array($lesson->id, $completedLessonIds))
                                        <span class="text-green-500" title="{{ __('Selesai') }}">✓</span>
                                    @elseif ($locked)
                                        <span class="text-gray-300 dark:text-gray-600" title="{{ __('Terkunci') }}">🔒</span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">○</span>
                                    @endif

                                    @if ($locked)
                                        <span class="text-gray-400">{{ $lesson->title }}</span>
                                    @else
                                        <a href="{{ route('lessons.show', [$course, $lesson]) }}" wire:navigate class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $lesson->title }}
                                        </a>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-400 uppercase">{{ $lesson->type }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</div>
