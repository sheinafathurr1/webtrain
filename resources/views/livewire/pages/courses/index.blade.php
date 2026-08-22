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
        <h2 class="font-display font-extrabold text-2xl text-ink-primary">{{ __('Semua Course') }}</h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">
            @forelse ($tracks as $track)
                <div>
                    <h3 class="font-display font-bold text-lg text-ink-primary mb-1">{{ $track->title }}</h3>
                    @if ($track->description)
                        <p class="text-sm text-ink-secondary mb-4">{{ $track->description }}</p>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($track->courses as $course)
                            <a href="{{ route('courses.show', $course) }}" wire:navigate
                               class="card-lift block bg-surface border border-border rounded-2xl p-6 hover:border-brand hover:shadow-lg">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand to-accent flex items-center justify-center text-white text-lg mb-4">📘</div>
                                <h4 class="font-display font-bold text-ink-primary">{{ $course->title }}</h4>
                                <p class="mt-2 text-sm text-ink-secondary line-clamp-3">{{ $course->description }}</p>
                                <p class="mt-4 text-xs font-bold text-ink-muted">{{ $course->modules_count }} module</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-surface border border-border rounded-2xl p-6 text-center text-ink-secondary">
                    {{ __('Belum ada course yang dipublikasikan.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
