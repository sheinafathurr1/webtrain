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
        <p class="text-sm text-ink-muted">
            <a href="{{ route('courses.index') }}" wire:navigate class="hover:text-brand font-medium motion-safe:transition-colors duration-150">{{ __('Semua Course') }}</a>
            <span class="mx-1">/</span>
            <span class="text-ink-primary font-semibold">{{ $course->title }}</span>
        </p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-surface border border-border rounded-2xl p-6">
                <p class="text-xs font-bold uppercase tracking-widest text-brand">{{ $course->track->title }}</p>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold mt-1 text-ink-primary">{{ $course->title }}</h1>
                <p class="mt-3 text-ink-secondary">{{ $course->description }}</p>

                @php $percent = $course->progressPercentFor(auth()->user()); @endphp
                <div class="mt-6 max-w-sm">
                    <x-progress-bar :percent="$percent" />
                </div>

                @auth
                    @if ($percent === 100)
                        <a href="{{ route('courses.certificate', $course) }}" class="inline-block mt-6">
                            <x-primary-button>{{ __('🎓 Download Sertifikat') }}</x-primary-button>
                        </a>
                    @else
                        @php $next = $course->nextLessonFor(auth()->user()); @endphp
                        @if ($next)
                            <a href="{{ route('lessons.show', [$course, $next]) }}" wire:navigate class="inline-block mt-6">
                                <x-primary-button>{{ $percent > 0 ? __('Lanjutkan Belajar') : __('Mulai Belajar') }}</x-primary-button>
                            </a>
                        @endif
                    @endif
                @else
                    <a href="{{ route('login') }}" wire:navigate class="inline-block mt-6">
                        <x-primary-button>{{ __('Login untuk Mulai Belajar') }}</x-primary-button>
                    </a>
                @endauth
            </div>

            @foreach ($course->modules as $module)
                <div class="bg-surface border border-border rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-border">
                        <h3 class="font-display font-bold text-ink-primary">{{ $module->title }}</h3>
                    </div>
                    <ul class="divide-y divide-border">
                        @foreach ($module->lessons as $lesson)
                            @php $locked = $course->isLessonLockedFor($lesson, auth()->user(), $completedLessonIds); @endphp
                            <li class="px-6 py-3.5 flex items-center justify-between text-sm">
                                <div class="flex items-center gap-3">
                                    @if (in_array($lesson->id, $completedLessonIds))
                                        <span class="w-6 h-6 rounded-full bg-brand/10 text-brand flex items-center justify-center text-xs shrink-0" title="{{ __('Selesai') }}">✓</span>
                                    @elseif ($locked)
                                        <span class="w-6 h-6 rounded-full bg-ink-muted/10 text-ink-muted flex items-center justify-center text-xs shrink-0" title="{{ __('Terkunci') }}">🔒</span>
                                    @else
                                        <span class="w-6 h-6 rounded-full border-2 border-border shrink-0"></span>
                                    @endif

                                    @if ($locked)
                                        <span class="text-ink-muted">{{ $lesson->title }}</span>
                                    @else
                                        <a href="{{ route('lessons.show', [$course, $lesson]) }}" wire:navigate class="font-medium text-ink-secondary hover:text-brand motion-safe:transition-colors duration-150">
                                            {{ $lesson->title }}
                                        </a>
                                    @endif
                                </div>
                                <x-badge color="muted" class="uppercase">{{ $lesson->type }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</div>
