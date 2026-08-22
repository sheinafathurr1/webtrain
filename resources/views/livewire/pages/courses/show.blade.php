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
        <p class="terminal-prompt">
            <span class="seg-user">guest@webtrain</span><span class="seg-sep">:~$</span>
            <a href="{{ route('courses.index') }}" wire:navigate class="hover:text-ink-primary transition-colors duration-150">cd courses</a>/<span class="seg-cmd">{{ $course->slug }}</span>
        </p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-surface border border-border-subtle rounded p-6">
                <p class="font-mono text-xs uppercase tracking-widest text-ink-muted">{{ $course->track->title }}</p>
                <h1 class="font-display text-2xl font-bold mt-1 text-ink-primary">{{ $course->title }}</h1>
                <p class="mt-3 text-ink-secondary">{{ $course->description }}</p>

                @php $percent = $course->progressPercentFor(auth()->user()); @endphp
                <div class="mt-6">
                    <x-ascii-bar :percent="$percent" />
                </div>

                @auth
                    @if ($percent === 100)
                        <a href="{{ route('courses.certificate', $course) }}" class="inline-block mt-6">
                            <x-primary-button>{{ __('Download Sertifikat') }}</x-primary-button>
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
                <div class="bg-surface border border-border-subtle rounded overflow-hidden">
                    <div class="px-6 py-4 border-b border-border-subtle">
                        <h3 class="font-display font-semibold text-ink-primary">{{ $module->title }}</h3>
                    </div>
                    <ul class="divide-y divide-border-subtle">
                        @foreach ($module->lessons as $lesson)
                            @php $locked = $course->isLessonLockedFor($lesson, auth()->user()); @endphp
                            <li class="px-6 py-3 flex items-center justify-between text-sm">
                                <div class="flex items-center gap-3">
                                    @if (in_array($lesson->id, $completedLessonIds))
                                        <span class="font-mono text-xs text-ink-primary" title="{{ __('Selesai') }}">[done]</span>
                                    @elseif ($locked)
                                        <span class="font-mono text-xs text-ink-muted" title="{{ __('Terkunci') }}">[locked]</span>
                                    @else
                                        <span class="font-mono text-xs text-ink-muted">[ ]</span>
                                    @endif

                                    @if ($locked)
                                        <span class="text-ink-muted">{{ $lesson->title }}</span>
                                    @else
                                        <a href="{{ route('lessons.show', [$course, $lesson]) }}" wire:navigate class="text-ink-secondary hover:text-ink-primary transition-colors duration-150">
                                            {{ $lesson->title }}
                                        </a>
                                    @endif
                                </div>
                                <span class="font-mono text-xs text-ink-muted uppercase">{{ $lesson->type }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</div>
