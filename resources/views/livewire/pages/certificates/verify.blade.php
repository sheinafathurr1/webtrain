<?php

use App\Models\Certificate;

use function Livewire\Volt\{layout, mount, state};

layout('layouts.app');

state(['certificate' => null]);

mount(function (string $code) {
    $this->certificate = Certificate::with(['user', 'course'])->where('code', $code)->first();
});

?>

<div>
    <x-slot:header>
        <h2 class="font-display font-extrabold text-2xl text-ink-primary">{{ __('Verifikasi Sertifikat') }}</h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            @if ($certificate)
                <div class="bg-surface border border-brand/30 rounded-2xl p-8 text-center space-y-4">
                    <div class="w-14 h-14 mx-auto rounded-full bg-brand/10 text-brand flex items-center justify-center text-2xl">✓</div>

                    <div>
                        <p class="font-display text-lg font-bold text-brand">{{ __('Sertifikat Valid') }}</p>
                        <p class="text-sm text-ink-secondary mt-1">{{ __('Sertifikat ini benar diterbitkan oleh WebTrain.') }}</p>
                    </div>

                    <div class="border-t border-border pt-4 text-left space-y-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">{{ __('Diberikan kepada') }}</p>
                            <p class="text-ink-primary font-semibold">{{ $certificate->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">{{ __('Course') }}</p>
                            <p class="text-ink-primary font-semibold">{{ $certificate->course->title }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">{{ __('Diterbitkan') }}</p>
                            <p class="text-ink-primary font-semibold">{{ $certificate->issued_at->translatedFormat('d F Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-ink-muted">{{ __('Kode Sertifikat') }}</p>
                            <p class="font-mono text-ink-primary">{{ $certificate->code }}</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-surface border border-danger/30 rounded-2xl p-8 text-center space-y-3">
                    <div class="w-14 h-14 mx-auto rounded-full bg-danger/10 text-danger flex items-center justify-center text-2xl">✗</div>
                    <p class="font-display text-lg font-bold text-danger">{{ __('Kode Sertifikat Tidak Ditemukan') }}</p>
                    <p class="text-sm text-ink-secondary">{{ __('Pastikan kode yang kamu masukkan sesuai dengan yang tertera di sertifikat.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
