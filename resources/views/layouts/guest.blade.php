<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('layouts.partials.theme-init')

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=baloo-2:500,600,700,800|plus-jakarta-sans:400,500,600,700|fira-code:400,500&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink-primary antialiased bg-canvas">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-canvas relative overflow-hidden">
            <div class="pointer-events-none absolute -top-24 -left-24 w-72 h-72 rounded-full bg-brand/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-24 w-72 h-72 rounded-full bg-accent/10 blur-3xl"></div>

            <div class="flex flex-col items-center gap-2 relative">
                <a href="{{ route('home') }}" wire:navigate>
                    <x-application-logo class="text-3xl" />
                </a>
                <p class="text-sm text-ink-secondary">{{ __('Belajar web development, satu langkah kecil setiap hari.') }}</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-surface border border-border overflow-hidden rounded-2xl shadow-sm relative">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
