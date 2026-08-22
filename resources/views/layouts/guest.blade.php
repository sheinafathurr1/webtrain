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
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600|ibm-plex-sans-condensed:600,700|ibm-plex-mono:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink-primary antialiased bg-canvas">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-canvas">
            <div class="flex flex-col items-center gap-3">
                <a href="{{ route('home') }}" wire:navigate>
                    <x-application-logo class="text-xl" />
                </a>
                <p class="terminal-prompt"><span class="seg-user">guest@webtrain</span><span class="seg-sep">:~$</span> {{ str_replace('.', '-', request()->route()?->getName() ?? 'auth') }}</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-surface border border-border-subtle overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
