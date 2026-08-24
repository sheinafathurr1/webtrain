<?php

use App\Models\Badge;
use App\Models\Bookmark;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\UserProgress;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\{computed, layout, state};

layout('layouts.app');

state([
    'streakAtRisk' => function () {
        $user = Auth::user();

        if ($user->current_streak <= 0 || ! $user->last_activity_date) {
            return false;
        }

        return Carbon::parse($user->last_activity_date)->isSameDay(Carbon::today()->subDay());
    },
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

$bookmarkedLessons = computed(fn () => Auth::user()->bookmarks()
    ->with('lesson.module.course')
    ->latest()
    ->limit(20)
    ->get());

$removeBookmark = function (int $bookmarkId) {
    Bookmark::where('id', $bookmarkId)->where('user_id', Auth::id())->delete();
};

?>

<div>
    <x-slot:header>
        <h2 class="font-display font-extrabold text-2xl text-ink-primary">{{ __('Halo') }}, {{ explode(' ', auth()->user()->name)[0] }}! 👋</h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($streakAtRisk)
                @php $ongoing = $courses->first(fn ($item) => $item['percent'] < 100); @endphp
                <div class="rounded-2xl p-5 bg-gradient-to-r from-accent/15 to-danger/10 border border-accent/30 flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">⏰</span>
                        <div>
                            <p class="font-display font-bold text-ink-primary">
                                {{ __('Streak :n hari kamu bakal putus hari ini!', ['n' => auth()->user()->current_streak]) }}
                            </p>
                            <p class="text-sm text-ink-secondary">
                                {{ __('Selesaikan minimal 1 lesson sebelum tengah malam biar streak-nya lanjut.') }}
                            </p>
                        </div>
                    </div>

                    @if ($ongoing && $ongoing['next'])
                        <a href="{{ route('lessons.show', [$ongoing['course'], $ongoing['next']]) }}" wire:navigate>
                            <x-primary-button type="button">{{ __('Lanjut Belajar') }}</x-primary-button>
                        </a>
                    @else
                        <a href="{{ route('courses.index') }}" wire:navigate>
                            <x-primary-button type="button">{{ __('Jelajahi Course') }}</x-primary-button>
                        </a>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-2xl p-6 bg-gradient-to-br from-gold/15 to-gold/5 border border-gold/20">
                    <div class="w-9 h-9 rounded-full bg-gold/20 flex items-center justify-center text-lg mb-3">⭐</div>
                    <p class="text-sm text-ink-secondary font-medium">{{ __('Total XP') }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold text-ink-primary tabular-nums">{{ auth()->user()->total_points }}</p>
                </div>
                <div class="rounded-2xl p-6 bg-gradient-to-br from-accent/15 to-accent/5 border border-accent/20">
                    <div class="w-9 h-9 rounded-full bg-accent/20 flex items-center justify-center text-lg mb-3">🔥</div>
                    <p class="text-sm text-ink-secondary font-medium">{{ __('Streak Saat Ini') }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold text-ink-primary tabular-nums">🔥 {{ auth()->user()->current_streak }} {{ __('hari') }}</p>
                </div>
                <div class="rounded-2xl p-6 bg-gradient-to-br from-brand/15 to-brand/5 border border-brand/20">
                    <div class="w-9 h-9 rounded-full bg-brand/20 flex items-center justify-center text-lg mb-3">🏆</div>
                    <p class="text-sm text-ink-secondary font-medium">{{ __('Streak Terpanjang') }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold text-ink-primary tabular-nums">{{ auth()->user()->longest_streak }} {{ __('hari') }}</p>
                </div>
            </div>

            <div class="bg-surface border border-border rounded-2xl p-6">
                <h3 class="font-display font-bold text-lg text-ink-primary mb-4">{{ __('Badge') }}</h3>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                    @foreach ($badges as $item)
                        <div class="text-center rounded-xl p-3 {{ $item['earned'] ? 'bg-gold/10' : 'opacity-30 grayscale' }}" title="{{ $item['badge']->description }}">
                            <div class="text-4xl">{{ $item['badge']->icon }}</div>
                            <p class="text-xs mt-1 font-semibold text-ink-secondary">{{ $item['badge']->name }}</p>
                            <p class="text-[10px] font-bold mt-0.5 {{ $item['earned'] ? 'text-gold' : 'text-ink-muted' }}">{{ $item['earned'] ? __('DIDAPAT') : __('TERKUNCI') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-surface border border-border rounded-2xl p-6">
                <h3 class="font-display font-bold text-lg text-ink-primary mb-4">{{ __('Course Sedang Diambil') }}</h3>

                @forelse ($courses as $item)
                    <div class="py-4 first:pt-0 border-t first:border-t-0 border-border">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-ink-primary">{{ $item['course']->title }}</p>
                                <p class="text-xs text-ink-muted">{{ $item['course']->track->title }}</p>
                            </div>

                            @if ($item['next'])
                                <a href="{{ route('lessons.show', [$item['course'], $item['next']]) }}" wire:navigate>
                                    <x-primary-button type="button">{{ __('Lanjut Belajar') }}</x-primary-button>
                                </a>
                            @endif
                        </div>

                        <div class="mt-3">
                            <x-progress-bar :percent="$item['percent']" />
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-secondary">
                        {{ __('Kamu belum mulai course apa pun.') }}
                        <a href="{{ route('courses.index') }}" wire:navigate class="text-brand font-semibold underline hover:no-underline">{{ __('Jelajahi course') }}</a>
                    </p>
                @endforelse
            </div>

            <div class="bg-surface border border-border rounded-2xl p-6">
                <h3 class="font-display font-bold text-lg text-ink-primary mb-4">{{ __('Lesson Tersimpan') }}</h3>

                @forelse ($this->bookmarkedLessons as $bookmark)
                    <div class="py-2.5 flex items-center justify-between text-sm border-t first:border-t-0 border-border gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="text-gold shrink-0">★</span>
                            <div class="min-w-0">
                                <a href="{{ route('lessons.show', [$bookmark->lesson->module->course, $bookmark->lesson]) }}" wire:navigate class="font-medium text-ink-secondary hover:text-brand motion-safe:transition-colors duration-150 truncate block">
                                    {{ $bookmark->lesson->title }}
                                </a>
                                <span class="text-xs text-ink-muted">{{ $bookmark->lesson->module->course->title }}</span>
                            </div>
                        </div>
                        <button type="button" wire:click="removeBookmark({{ $bookmark->id }})" class="text-xs font-semibold text-ink-muted hover:text-danger motion-safe:transition-colors duration-150 shrink-0">{{ __('Hapus') }}</button>
                    </div>
                @empty
                    <p class="text-sm text-ink-secondary">{{ __('Belum ada lesson tersimpan. Klik ikon ★ di halaman lesson untuk menyimpannya.') }}</p>
                @endforelse
            </div>

            <div class="bg-surface border border-border rounded-2xl p-6">
                <h3 class="font-display font-bold text-lg text-ink-primary mb-4">{{ __('Riwayat Lesson Selesai') }}</h3>

                @forelse ($recentActivity as $progress)
                    <div class="py-2.5 flex items-center justify-between text-sm border-t first:border-t-0 border-border">
                        <div class="flex items-center gap-2.5">
                            <span class="w-6 h-6 rounded-full bg-brand/10 text-brand flex items-center justify-center text-xs shrink-0">✓</span>
                            <div>
                                <a href="{{ route('lessons.show', [$progress->lesson->module->course, $progress->lesson]) }}" wire:navigate class="font-medium text-ink-secondary hover:text-brand motion-safe:transition-colors duration-150">
                                    {{ $progress->lesson->title }}
                                </a>
                                <span class="text-xs text-ink-muted">— {{ $progress->lesson->module->course->title }}</span>
                            </div>
                        </div>
                        <span class="text-xs text-ink-muted tabular-nums shrink-0">{{ $progress->completed_at?->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-secondary">{{ __('Belum ada lesson yang diselesaikan.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
