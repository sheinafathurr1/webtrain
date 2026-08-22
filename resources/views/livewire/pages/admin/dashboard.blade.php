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
        <p class="terminal-prompt"><span class="seg-user">root@webtrain</span><span class="seg-sep">:~$</span> <span class="seg-cmd">sudo admin</span></p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-surface border border-border-subtle rounded p-6 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-ink-primary">{{ __('Welcome to the WebTrain admin panel.') }}</p>
                    <p class="mt-2 text-sm text-ink-secondary">
                        {{ __('Kelola jalur belajar, course, module, dan lesson di sini.') }}
                    </p>
                </div>
                <a href="{{ route('admin.tracks.index') }}" wire:navigate>
                    <x-primary-button>{{ __('Kelola Konten') }}</x-primary-button>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface border border-border-subtle rounded p-6">
                    <p class="text-sm text-ink-secondary">{{ __('Total Users') }}</p>
                    <p class="mt-1 font-mono text-3xl font-semibold text-ink-primary tabular-nums">{{ $totalUsers }}</p>
                </div>
                <div class="bg-surface border border-border-subtle rounded p-6">
                    <p class="text-sm text-ink-secondary">{{ __('Total Tracks') }}</p>
                    <p class="mt-1 font-mono text-3xl font-semibold text-ink-primary tabular-nums">{{ $totalTracks }}</p>
                </div>
                <div class="bg-surface border border-border-subtle rounded p-6">
                    <p class="text-sm text-ink-secondary">{{ __('Total Courses') }}</p>
                    <p class="mt-1 font-mono text-3xl font-semibold text-ink-primary tabular-nums">{{ $totalCourses }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
