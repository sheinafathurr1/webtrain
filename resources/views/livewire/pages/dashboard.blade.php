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
        <p class="terminal-prompt"><span class="seg-user">guest@webtrain</span><span class="seg-sep">:~$</span> <span class="seg-cmd">cat dashboard</span></p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface border border-border-subtle rounded p-6">
                    <p class="text-sm text-ink-secondary">{{ __('Total XP') }}</p>
                    <p class="mt-1 font-mono text-3xl font-semibold text-ink-primary tabular-nums">{{ auth()->user()->total_points }}</p>
                </div>
                <div class="bg-surface border border-border-subtle rounded p-6">
                    <p class="text-sm text-ink-secondary">{{ __('Streak Saat Ini') }}</p>
                    <p class="mt-1 font-mono text-3xl font-semibold text-ink-primary tabular-nums">🔥 {{ auth()->user()->current_streak }} {{ __('hari') }}</p>
                </div>
                <div class="bg-surface border border-border-subtle rounded p-6">
                    <p class="text-sm text-ink-secondary">{{ __('Streak Terpanjang') }}</p>
                    <p class="mt-1 font-mono text-3xl font-semibold text-ink-primary tabular-nums">{{ auth()->user()->longest_streak }} {{ __('hari') }}</p>
                </div>
            </div>

            <div class="bg-surface border border-border-subtle rounded p-6">
                <h3 class="font-display font-semibold text-ink-primary mb-4">{{ __('Badge') }}</h3>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                    @foreach ($badges as $item)
                        <div class="text-center {{ $item['earned'] ? '' : 'opacity-30 grayscale' }}" title="{{ $item['badge']->description }}">
                            <div class="text-4xl">{{ $item['badge']->icon }}</div>
                            <p class="text-xs mt-1 text-ink-secondary">{{ $item['badge']->name }}</p>
                            <p class="text-[10px] font-mono mt-0.5 text-ink-muted">{{ $item['earned'] ? '[earned]' : '[locked]' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-surface border border-border-subtle rounded p-6">
                <h3 class="font-display font-semibold text-ink-primary mb-4">{{ __('Course Sedang Diambil') }}</h3>

                @forelse ($courses as $item)
                    <div class="py-4 first:pt-0 border-t first:border-t-0 border-border-subtle">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-ink-primary">{{ $item['course']->title }}</p>
                                <p class="text-xs text-ink-muted">{{ $item['course']->track->title }}</p>
                            </div>

                            @if ($item['next'])
                                <a href="{{ route('lessons.show', [$item['course'], $item['next']]) }}" wire:navigate>
                                    <x-primary-button type="button">{{ __('Lanjut Belajar') }}</x-primary-button>
                                </a>
                            @endif
                        </div>

                        <div class="mt-3">
                            <x-ascii-bar :percent="$item['percent']" />
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-secondary">
                        {{ __('Kamu belum mulai course apa pun.') }}
                        <a href="{{ route('courses.index') }}" wire:navigate class="text-ink-primary underline hover:no-underline">{{ __('Jelajahi course') }}</a>
                    </p>
                @endforelse
            </div>

            <div class="bg-surface border border-border-subtle rounded p-6">
                <h3 class="font-display font-semibold text-ink-primary mb-4">{{ __('Riwayat Lesson Selesai') }}</h3>

                @forelse ($recentActivity as $progress)
                    <div class="py-2 flex items-center justify-between text-sm border-t first:border-t-0 border-border-subtle">
                        <div>
                            <a href="{{ route('lessons.show', [$progress->lesson->module->course, $progress->lesson]) }}" wire:navigate class="text-ink-secondary hover:text-ink-primary transition-colors duration-150">
                                {{ $progress->lesson->title }}
                            </a>
                            <span class="text-xs text-ink-muted">— {{ $progress->lesson->module->course->title }}</span>
                        </div>
                        <span class="text-xs font-mono text-ink-muted tabular-nums">{{ $progress->completed_at?->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-secondary">{{ __('Belum ada lesson yang diselesaikan.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
