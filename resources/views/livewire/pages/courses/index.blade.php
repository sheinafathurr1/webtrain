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
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Semua Course') }}
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">
            @forelse ($tracks as $track)
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ $track->title }}</h3>
                    @if ($track->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $track->description }}</p>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($track->courses as $course)
                            <a href="{{ route('courses.show', $course) }}" wire:navigate
                               class="block bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ $course->title }}</h4>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 line-clamp-3">{{ $course->description }}</p>
                                <p class="mt-4 text-xs text-gray-400">{{ $course->modules_count }} module</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-center text-gray-500 dark:text-gray-400">
                    {{ __('Belum ada course yang dipublikasikan.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
