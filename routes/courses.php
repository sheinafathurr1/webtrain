<?php

use App\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('courses', 'pages.courses.index')->name('courses.index');

Volt::route('courses/{course:slug}', 'pages.courses.show')->name('courses.show');

Volt::route('courses/{course:slug}/lessons/{lesson:slug}', 'pages.lessons.show')
    ->middleware(['auth', 'verified'])
    ->name('lessons.show');

Route::get('courses/{course:slug}/certificate', [CertificateController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('courses.certificate');
