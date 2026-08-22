<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'role:Admin'])
    ->group(function () {
        Volt::route('/', 'pages.admin.dashboard')->name('dashboard');
    });
