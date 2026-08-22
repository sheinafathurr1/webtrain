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
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Admin Panel') }}
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100 flex items-center justify-between">
                <div>
                    <p>{{ __('Welcome to the WebTrain admin panel.') }}</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Kelola jalur belajar, course, module, dan lesson di sini.') }}
                    </p>
                </div>
                <a href="{{ route('admin.tracks.index') }}" wire:navigate>
                    <x-primary-button>{{ __('Kelola Konten') }}</x-primary-button>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Users') }}</p>
                    <p class="text-3xl font-semibold">{{ $totalUsers }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Tracks') }}</p>
                    <p class="text-3xl font-semibold">{{ $totalTracks }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Courses') }}</p>
                    <p class="text-3xl font-semibold">{{ $totalCourses }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
