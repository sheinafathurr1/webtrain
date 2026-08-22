<?php

use App\Models\Track;

use function Livewire\Volt\{layout, state};

layout('layouts.app');

state([
    'track' => fn () => Track::where('is_published', true)
        ->with(['courses' => fn ($q) => $q->where('is_published', true)->withCount('modules')])
        ->orderBy('order')
        ->first(),
    'playgroundStarter' => fn () => <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
          <style>
            body { font-family: sans-serif; padding: 24px; color: #171715; }
            h1 { color: #0F766E; }
          </style>
        </head>
        <body>
          <h1>Halo, calon web developer! 👋</h1>
          <p>Ubah teks ini, lalu lihat hasilnya langsung di sini →</p>
        </body>
        </html>
        HTML,
]);

?>

<div>
    <!-- Hero -->
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute -top-32 -right-16 w-96 h-96 rounded-full bg-accent/10 blur-3xl"></div>
        <div class="pointer-events-none absolute top-40 -left-24 w-72 h-72 rounded-full bg-brand/10 blur-3xl"></div>
        <div class="pointer-events-none absolute top-24 right-1/4 w-40 h-40 rounded-full bg-gold/10 blur-2xl"></div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-14 relative">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gold/10 text-gold text-xs font-bold uppercase tracking-wide">
                🚀 {{ __('Belajar dari nol, 100% gratis') }}
            </span>

            <h1 class="font-display font-extrabold text-4xl sm:text-6xl text-ink-primary max-w-2xl mt-5 leading-tight">
                Belajar web development, <span class="text-accent">langsung praktik</span> di browser.
            </h1>
            <p class="mt-5 text-lg text-ink-secondary max-w-xl">
                HTML, CSS, dan JavaScript dari nol sampai bisa. Tiap lesson ada playground kode sungguhan —
                bukan cuma video yang ditonton.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" wire:navigate><x-primary-button class="px-7 py-3 text-base">{{ __('Daftar Gratis') }}</x-primary-button></a>
                <a href="{{ route('courses.index') }}" wire:navigate><x-secondary-button class="px-7 py-3 text-base">{{ __('Lihat Semua Course') }}</x-secondary-button></a>
            </div>

            <div class="mt-10 flex flex-wrap gap-2.5">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold" style="background-color: rgb(227 76 38 / 0.12); color: #E44D26;">HTML</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold" style="background-color: rgb(21 114 182 / 0.12); color: #1572B6;">CSS</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold" style="background-color: rgb(217 168 15 / 0.15); color: #B08B00;">JavaScript</span>
            </div>
        </div>
    </section>

    <!-- Live playground demo -->
    <section class="border-t border-border bg-surface">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <p class="font-bold text-xs uppercase tracking-widest text-brand mb-2">{{ __('Coba sekarang, tanpa daftar dulu') }}</p>
            <h2 class="font-display font-extrabold text-2xl sm:text-3xl text-ink-primary mb-6">{{ __('Ini playground yang sama seperti di dalam course.') }}</h2>

            <div
                wire:ignore
                data-playground
                x-data="codePlayground(@js($playgroundStarter), null)"
                class="rounded-2xl border border-border bg-canvas p-3 shadow-sm"
            >
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    <div x-ref="editor" class="border border-border rounded-xl overflow-auto text-sm" style="height: 20rem;"></div>
                    <iframe x-ref="preview" sandbox="allow-scripts" title="{{ __('Preview') }}" class="w-full border border-border rounded-xl bg-white" style="height: 20rem;"></iframe>
                </div>
                <button type="button" @click="resetCode()" class="mt-3 text-sm font-semibold text-brand hover:text-brand-dark motion-safe:transition-colors duration-150">{{ __('↺ Reset') }}</button>
            </div>
        </div>
    </section>

    <!-- Course highlight -->
    @if ($track && $track->courses->isNotEmpty())
        <section class="border-t border-border">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <p class="font-bold text-xs uppercase tracking-widest text-brand mb-2">{{ $track->title }}</p>
                <h2 class="font-display font-extrabold text-2xl sm:text-3xl text-ink-primary mb-6">{{ __('Mulai dari sini kalau masih nol.') }}</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($track->courses as $course)
                        <a href="{{ route('courses.show', $course) }}" wire:navigate class="card-lift block rounded-2xl p-6 bg-surface border border-border hover:border-brand hover:shadow-lg">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand to-accent flex items-center justify-center text-white text-lg mb-4">📘</div>
                            <p class="font-display font-bold text-lg text-ink-primary">{{ $course->title }}</p>
                            <p class="mt-2 text-sm text-ink-secondary line-clamp-2">{{ $course->description }}</p>
                            <p class="mt-4 text-xs font-bold text-ink-muted">{{ $course->modules_count }} module</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Closing -->
    <section class="px-4 sm:px-6 lg:px-8 py-16">
        <div class="max-w-5xl mx-auto rounded-3xl bg-gradient-to-br from-brand to-accent px-8 py-16 text-center relative overflow-hidden">
            <div class="pointer-events-none absolute -top-10 -left-10 w-40 h-40 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -bottom-16 -right-10 w-56 h-56 rounded-full bg-white/10"></div>
            <h2 class="font-display font-extrabold text-3xl sm:text-4xl text-white relative">{{ __('Siap mulai jadi web developer?') }}</h2>
            <p class="mt-3 text-white/90 relative">{{ __('Gratis, tanpa kartu kredit, langsung praktik hari ini.') }}</p>
            <div class="mt-8 relative">
                <a href="{{ route('register') }}" wire:navigate>
                    <button type="button" class="inline-flex items-center justify-center px-7 py-3 bg-white text-ink-primary rounded-full font-display font-bold text-base shadow-lg motion-safe:hover:-translate-y-0.5 motion-safe:transition-transform duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        {{ __('Buat Akun Gratis') }}
                    </button>
                </a>
            </div>
        </div>
    </section>
</div>
