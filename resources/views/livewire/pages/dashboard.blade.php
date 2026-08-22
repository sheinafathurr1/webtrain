<?php

use App\Models\Badge;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\{layout, state};

layout('layouts.app');

state([
    'badges' => function () {
        $earnedBadgeIds = Auth::user()->userBadges()->pluck('badge_id');

        return Badge::all()->map(fn (Badge $badge) => [
            'badge' => $badge,
            'earned' => $earnedBadgeIds->contains($badge->id),
        ]);
    },
    'courses' => function () {
        $completedLessonIds = UserProgress::where('user_id', Auth::id())->pluck('lesson_id');

        $courseIds = Lesson::whereIn('id', $completedLessonIds)
            ->with('module')
            ->get()
            ->pluck('module.course_id')
            ->unique();

        return Course::whereIn('id', $courseIds)
            ->with('track')
            ->get()
            ->map(fn (Course $course) => [
                'course' => $course,
                'percent' => $course->progressPercentFor(Auth::user()),
                'next' => $course->nextLessonFor(Auth::user()),
            ]);
    },
    'recentActivity' => fn () => UserProgress::where('user_id', Auth::id())
        ->with('lesson.module.course')
        ->latest('completed_at')
        ->limit(5)
        ->get(),
]);

?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total XP') }}</p>
                    <p class="text-3xl font-semibold">{{ auth()->user()->total_points }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Streak Saat Ini') }}</p>
                    <p class="text-3xl font-semibold">🔥 {{ auth()->user()->current_streak }} {{ __('hari') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Streak Terpanjang') }}</p>
                    <p class="text-3xl font-semibold">{{ auth()->user()->longest_streak }} {{ __('hari') }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h3 class="font-semibold mb-4">{{ __('Badge') }}</h3>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                    @foreach ($badges as $item)
                        <div class="text-center {{ $item['earned'] ? '' : 'opacity-30 grayscale' }}" title="{{ $item['badge']->description }}">
                            <div class="text-4xl">{{ $item['badge']->icon }}</div>
                            <p class="text-xs mt-1 text-gray-600 dark:text-gray-400">{{ $item['badge']->name }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h3 class="font-semibold mb-4">{{ __('Course Sedang Diambil') }}</h3>

                @forelse ($courses as $item)
                    <div class="py-4 first:pt-0 border-t first:border-t-0 border-gray-100 dark:border-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-medium">{{ $item['course']->title }}</p>
                                <p class="text-xs text-gray-400">{{ $item['course']->track->title }}</p>
                            </div>

                            @if ($item['next'])
                                <a href="{{ route('lessons.show', [$item['course'], $item['next']]) }}" wire:navigate>
                                    <x-primary-button type="button">{{ __('Lanjut Belajar') }}</x-primary-button>
                                </a>
                            @endif
                        </div>

                        <div class="mt-3">
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                <span>{{ __('Progress') }}</span>
                                <span>{{ $item['percent'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $item['percent'] }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Kamu belum mulai course apa pun.') }}
                        <a href="{{ route('courses.index') }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Jelajahi course') }}</a>
                    </p>
                @endforelse
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <h3 class="font-semibold mb-4">{{ __('Riwayat Lesson Selesai') }}</h3>

                @forelse ($recentActivity as $progress)
                    <div class="py-2 flex items-center justify-between text-sm border-t first:border-t-0 border-gray-100 dark:border-gray-700">
                        <div>
                            <a href="{{ route('lessons.show', [$progress->lesson->module->course, $progress->lesson]) }}" wire:navigate class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                                {{ $progress->lesson->title }}
                            </a>
                            <span class="text-xs text-gray-400">— {{ $progress->lesson->module->course->title }}</span>
                        </div>
                        <span class="text-xs text-gray-400">{{ $progress->completed_at?->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada lesson yang diselesaikan.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
