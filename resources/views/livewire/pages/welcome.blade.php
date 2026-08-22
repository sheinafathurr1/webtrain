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
            h1 { color: #171715; }
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
    <section class="border-b border-border-subtle">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-14">
            <p class="terminal-prompt mb-6">
                <span class="seg-user">guest@webtrain</span><span class="seg-sep">:~$</span>
                <span class="seg-cmd" id="typedCmd"></span><span class="terminal-cursor" id="typedCursor"></span>
            </p>

            <h1 class="font-display font-bold text-4xl sm:text-5xl text-ink-primary max-w-2xl" style="text-wrap: balance;">
                Belajar web development, langsung praktik di browser.
            </h1>
            <p class="mt-5 text-lg text-ink-secondary max-w-xl">
                HTML, CSS, dan JavaScript dari nol sampai bisa. Tiap lesson ada playground kode sungguhan —
                bukan cuma video yang ditonton.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" wire:navigate><x-primary-button class="px-6 py-2.5">{{ __('Daftar Gratis') }}</x-primary-button></a>
                <a href="{{ route('courses.index') }}" wire:navigate><x-secondary-button class="px-6 py-2.5">{{ __('Lihat Semua Course') }}</x-secondary-button></a>
            </div>
        </div>
    </section>

    <!-- Live playground demo -->
    <section class="border-b border-border-subtle">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <p class="eyebrow font-mono text-xs uppercase tracking-widest text-ink-muted mb-2">{{ __('Coba sekarang, tanpa daftar dulu') }}</p>
            <h2 class="font-display font-bold text-2xl text-ink-primary mb-6">{{ __('Ini playground yang sama seperti di dalam course.') }}</h2>

            <div
                wire:ignore
                data-playground
                x-data="codePlayground(@js($playgroundStarter), null)"
            >
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    <div x-ref="editor" class="border border-border-subtle rounded overflow-auto text-sm" style="height: 20rem;"></div>
                    <iframe x-ref="preview" sandbox="allow-scripts" title="{{ __('Preview') }}" class="w-full border border-border-subtle rounded bg-white" style="height: 20rem;"></iframe>
                </div>
                <button type="button" @click="resetCode()" class="mt-3 text-sm text-ink-secondary hover:text-ink-primary transition-colors duration-150">{{ __('↺ Reset') }}</button>
            </div>
        </div>
    </section>

    <!-- Course highlight -->
    @if ($track && $track->courses->isNotEmpty())
        <section class="border-b border-border-subtle">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
                <p class="eyebrow font-mono text-xs uppercase tracking-widest text-ink-muted mb-2">{{ $track->title }}</p>
                <h2 class="font-display font-bold text-2xl text-ink-primary mb-6">{{ __('Mulai dari sini kalau masih nol.') }}</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($track->courses as $course)
                        <a href="{{ route('courses.show', $course) }}" wire:navigate class="block border border-border-subtle rounded p-5 hover:border-ink-primary transition-colors duration-150 bg-surface">
                            <p class="font-semibold text-ink-primary">{{ $course->title }}</p>
                            <p class="mt-2 text-sm text-ink-secondary line-clamp-2">{{ $course->description }}</p>
                            <p class="mt-4 font-mono text-xs text-ink-muted">{{ $course->modules_count }} module</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Closing -->
    <section>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <p class="terminal-prompt justify-center inline-flex">
                <span class="seg-user">guest@webtrain</span><span class="seg-sep">:~$</span>
                <span class="seg-cmd">echo "siap mulai?"</span>
            </p>
            <div class="mt-6">
                <a href="{{ route('register') }}" wire:navigate><x-primary-button class="px-6 py-2.5">{{ __('Buat Akun') }}</x-primary-button></a>
            </div>
        </div>
    </section>
</div>

@script
<script>
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var cmd = 'npx belajar-web-dev --dari-nol';
    var el = document.getElementById('typedCmd');

    if (el) {
        if (reduced) {
            el.textContent = cmd;
        } else {
            var i = 0;
            (function type() {
                if (i <= cmd.length) {
                    el.textContent = cmd.slice(0, i);
                    i++;
                    setTimeout(type, 40);
                }
            })();
        }
    }
</script>
@endscript
