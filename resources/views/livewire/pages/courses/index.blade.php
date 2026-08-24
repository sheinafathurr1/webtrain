<?php

use App\Models\Track;

use function Livewire\Volt\{computed, layout, state};

layout('layouts.app');

state([
    'search' => '',
    'trackId' => null,
]);

$allTracks = computed(fn () => Track::where('is_published', true)
    ->whereHas('courses', fn ($query) => $query->where('is_published', true))
    ->orderBy('order')
    ->get());

$tracks = computed(function () {
    $search = trim($this->search);

    return Track::where('is_published', true)
        ->when($this->trackId, fn ($query) => $query->where('id', $this->trackId))
        ->with(['courses' => function ($query) use ($search) {
            $query->where('is_published', true)
                ->withCount('modules')
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                ));
        }])
        ->orderBy('order')
        ->get()
        ->filter(fn (Track $track) => $track->courses->isNotEmpty());
});

$resetFilters = function () {
    $this->search = '';
    $this->trackId = null;
};

?>

<div>
    <x-slot:header>
        <h2 class="font-display font-extrabold text-2xl text-ink-primary">{{ __('Semua Course') }}</h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-ink-muted" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Cari course...') }}"
                        class="w-full rounded-xl border-border bg-surface pl-10 pr-4 py-2.5 text-sm text-ink-primary placeholder:text-ink-muted focus:border-brand focus:ring-brand"
                    />
                </div>

                <select
                    wire:model.live="trackId"
                    class="rounded-xl border-border bg-surface pl-4 pr-9 py-2.5 text-sm text-ink-primary focus:border-brand focus:ring-brand"
                >
                    <option value="">{{ __('Semua Track') }}</option>
                    @foreach ($this->allTracks as $track)
                        <option value="{{ $track->id }}">{{ $track->title }}</option>
                    @endforeach
                </select>

                @if ($search !== '' || $trackId)
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="text-sm font-semibold text-ink-secondary hover:text-brand px-3 py-2.5 whitespace-nowrap"
                    >
                        {{ __('Reset filter') }}
                    </button>
                @endif
            </div>

            @forelse ($this->tracks as $track)
                <div>
                    <h3 class="font-display font-bold text-lg text-ink-primary mb-1">{{ $track->title }}</h3>
                    @if ($track->description)
                        <p class="text-sm text-ink-secondary mb-4">{{ $track->description }}</p>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($track->courses as $course)
                            <a href="{{ route('courses.show', $course) }}" wire:navigate
                               class="card-lift block bg-surface border border-border rounded-2xl p-6 hover:border-brand hover:shadow-lg">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand to-accent flex items-center justify-center text-white text-lg mb-4">📘</div>
                                <h4 class="font-display font-bold text-ink-primary">{{ $course->title }}</h4>
                                <p class="mt-2 text-sm text-ink-secondary line-clamp-3">{{ $course->description }}</p>
                                <p class="mt-4 text-xs font-bold text-ink-muted">{{ $course->modules_count }} module</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-surface border border-border rounded-2xl p-6 text-center text-ink-secondary">
                    @if ($search !== '' || $trackId)
                        {{ __('Tidak ada course yang cocok dengan pencarian/filter kamu.') }}
                    @else
                        {{ __('Belum ada course yang dipublikasikan.') }}
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</div>
