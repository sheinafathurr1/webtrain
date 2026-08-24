<?php

use App\Models\Course;
use App\Models\Track;
use App\Models\User;

use function Livewire\Volt\layout;
use function Livewire\Volt\state;

layout('layouts.app');

state([
    'totalUsers' => fn () => User::count(),
    'totalTracks' => fn () => Track::count(),
    'totalCourses' => fn () => Course::count(),
]);

?>

<div>
    <x-slot:header>
        <h2 class="font-display font-extrabold text-2xl text-ink-primary">{{ __('Admin Panel') }}</h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-2xl bg-gradient-to-br from-brand to-accent px-6 py-8 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-white font-display font-bold text-lg">{{ __('Welcome to the WebTrain admin panel.') }}</p>
                    <p class="mt-1 text-sm text-white/85">
                        {{ __('Kelola jalur belajar, course, module, dan lesson di sini.') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.analytics') }}" wire:navigate>
                        <button type="button" class="inline-flex items-center justify-center px-5 py-2.5 bg-white/15 text-white border border-white/40 rounded-full font-display font-bold text-sm motion-safe:hover:-translate-y-0.5 motion-safe:transition-transform duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            {{ __('Analytics') }}
                        </button>
                    </a>
                    <a href="{{ route('admin.tracks.index') }}" wire:navigate>
                        <button type="button" class="inline-flex items-center justify-center px-5 py-2.5 bg-white text-ink-primary rounded-full font-display font-bold text-sm shadow-sm motion-safe:hover:-translate-y-0.5 motion-safe:transition-transform duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            {{ __('Kelola Konten') }}
                        </button>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface border border-border rounded-2xl p-6">
                    <p class="text-sm text-ink-secondary font-medium">{{ __('Total Users') }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold text-brand tabular-nums">{{ $totalUsers }}</p>
                </div>
                <div class="bg-surface border border-border rounded-2xl p-6">
                    <p class="text-sm text-ink-secondary font-medium">{{ __('Total Tracks') }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold text-accent tabular-nums">{{ $totalTracks }}</p>
                </div>
                <div class="bg-surface border border-border rounded-2xl p-6">
                    <p class="text-sm text-ink-secondary font-medium">{{ __('Total Courses') }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold text-gold tabular-nums">{{ $totalCourses }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
