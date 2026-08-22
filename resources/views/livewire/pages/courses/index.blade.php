<?php

use App\Models\Track;

use function Livewire\Volt\{layout, state};

layout('layouts.app');

state([
    'tracks' => fn () => Track::where('is_published', true)
        ->with(['courses' => fn ($query) => $query->where('is_published', true)->withCount('modules')])
        ->orderBy('order')
        ->get()
        ->filter(fn (Track $track) => $track->courses->isNotEmpty()),
]);

?>

<div>
    <x-slot:header>
        <p class="terminal-prompt"><span class="seg-user">guest@webtrain</span><span class="seg-sep">:~$</span> <span class="seg-cmd">ls courses/</span></p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">
            @forelse ($tracks as $track)
                <div>
                    <h3 class="font-display font-semibold text-lg text-ink-primary mb-1">{{ $track->title }}</h3>
                    @if ($track->description)
                        <p class="text-sm text-ink-secondary mb-4">{{ $track->description }}</p>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($track->courses as $course)
                            <a href="{{ route('courses.show', $course) }}" wire:navigate
                               class="block bg-surface border border-border-subtle rounded p-6 hover:border-ink-primary hover:shadow-md transition-[border-color,box-shadow] duration-150">
                                <h4 class="font-semibold text-ink-primary">{{ $course->title }}</h4>
                                <p class="mt-2 text-sm text-ink-secondary line-clamp-3">{{ $course->description }}</p>
                                <p class="mt-4 font-mono text-xs text-ink-muted">{{ $course->modules_count }} module</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-surface border border-border-subtle rounded p-6 text-center text-ink-secondary">
                    {{ __('Belum ada course yang dipublikasikan.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
