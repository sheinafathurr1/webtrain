<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'role:Admin'])
    ->group(function () {
        Volt::route('/', 'pages.admin.dashboard')->name('dashboard');

        Volt::route('tracks', 'pages.admin.tracks.index')->name('tracks.index');
        Volt::route('tracks/{track}/courses', 'pages.admin.courses.index')->name('courses.index');
        Volt::route('courses/{course}/modules', 'pages.admin.modules.index')->name('modules.index');
        Volt::route('modules/{module}/lessons', 'pages.admin.lessons.index')->name('lessons.index');
        Volt::route('lessons/{lesson}/quiz', 'pages.admin.quizzes.builder')->name('quizzes.builder');
    });
