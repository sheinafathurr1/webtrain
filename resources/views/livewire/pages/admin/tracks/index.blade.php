<?php

use App\Models\Track;
use Illuminate\Validation\Rule;

use function Livewire\Volt\{layout, state, usesPagination, with};

layout('layouts.app');
usesPagination();

state([
    'showModal' => false,
    'editingId' => null,
    'title' => '',
    'slug' => '',
    'description' => '',
    'order' => 0,
    'is_published' => false,
]);

with(fn () => [
    'tracks' => Track::withCount('courses')->orderBy('order')->orderBy('title')->paginate(10),
]);

$resetForm = function () {
    $this->reset(['editingId', 'title', 'slug', 'description', 'order', 'is_published']);
    $this->order = 0;
};

$openCreate = function () {
    $this->resetForm();
    $this->showModal = true;
};

$openEdit = function (Track $track) {
    $this->editingId = $track->id;
    $this->title = $track->title;
    $this->slug = $track->slug;
    $this->description = $track->description;
    $this->order = $track->order;
    $this->is_published = $track->is_published;
    $this->showModal = true;
};

$save = function () {
    $validated = $this->validate([
        'title' => ['required', 'string', 'max:255'],
        'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('tracks', 'slug')->ignore($this->editingId)],
        'description' => ['nullable', 'string'],
        'order' => ['integer', 'min:0'],
        'is_published' => ['boolean'],
    ]);

    if (blank($validated['slug'])) {
        unset($validated['slug']);
    }

    if ($this->editingId) {
        Track::findOrFail($this->editingId)->update($validated);
    } else {
        Track::create($validated);
    }

    $this->showModal = false;
    $this->resetForm();
};

$delete = function (Track $track) {
    $track->delete();
};

?>

<div>
    <x-slot:header>
        <p class="terminal-prompt"><span class="seg-user">root@webtrain</span><span class="seg-sep">:~$</span> <span class="seg-cmd">ls tracks/</span></p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <x-primary-button wire:click="openCreate">{{ __('+ Track Baru') }}</x-primary-button>
            </div>

            <div class="bg-surface border border-border-subtle rounded overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-subtle">
                        <thead class="bg-canvas">
                            <tr>
                                <th class="px-6 py-3 text-left font-mono text-xs font-medium text-ink-muted uppercase tracking-wide">{{ __('Judul') }}</th>
                                <th class="px-6 py-3 text-left font-mono text-xs font-medium text-ink-muted uppercase tracking-wide">{{ __('Course') }}</th>
                                <th class="px-6 py-3 text-left font-mono text-xs font-medium text-ink-muted uppercase tracking-wide">{{ __('Status') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            @forelse ($tracks as $track)
                                <tr wire:key="track-{{ $track->id }}">
                                    <td class="px-6 py-4 text-sm text-ink-primary">
                                        {{ $track->title }}
                                        <div class="font-mono text-xs text-ink-muted">{{ $track->slug }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-ink-secondary font-mono tabular-nums">
                                        {{ $track->courses_count }} course
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono">
                                        @if ($track->is_published)
                                            <span class="text-ink-primary">[published]</span>
                                        @else
                                            <span class="text-ink-muted">[draft]</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                        <a href="{{ route('admin.courses.index', $track) }}" wire:navigate class="text-ink-secondary hover:text-ink-primary underline transition-colors duration-150">{{ __('Kelola Course') }}</a>
                                        <button wire:click="openEdit({{ $track->id }})" class="text-ink-secondary hover:text-ink-primary underline transition-colors duration-150">{{ __('Edit') }}</button>
                                        <button wire:click="delete({{ $track->id }})" wire:confirm="{{ __('Hapus track ini beserta seluruh course di dalamnya?') }}" class="text-danger hover:opacity-75 underline transition-opacity duration-150">{{ __('Hapus') }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-ink-secondary">
                                        {{ __('Belum ada track. Buat track pertama untuk mulai menyusun jalur belajar.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $tracks->links() }}
        </div>
    </div>

    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-canvas opacity-75" wire:click="$set('showModal', false)"></div>

        <div class="relative bg-surface border border-border-subtle rounded shadow-xl max-w-lg mx-auto p-6 text-ink-primary">
            <h3 class="font-display text-lg font-medium mb-4">
                {{ $editingId ? __('Edit Track') : __('Track Baru') }}
            </h3>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <x-input-label for="title" :value="__('Judul')" />
                    <x-text-input wire:model="title" id="title" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="slug" :value="__('Slug (opsional, otomatis dari judul)')" />
                    <x-text-input wire:model="slug" id="slug" class="block mt-1 w-full font-mono" type="text" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" :value="__('Deskripsi')" />
                    <textarea wire:model="description" id="description" rows="3" class="border-border-interactive bg-surface text-ink-primary focus:border-ink-primary focus:ring-ink-primary rounded shadow-sm block mt-1 w-full"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="flex gap-4">
                    <div class="flex-1">
                        <x-input-label for="order" :value="__('Urutan')" />
                        <x-text-input wire:model="order" id="order" class="block mt-1 w-full" type="number" min="0" />
                        <x-input-error :messages="$errors->get('order')" class="mt-2" />
                    </div>

                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model="is_published" class="rounded-sm border-border-interactive text-ink-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-primary">
                            <span class="ms-2 text-sm text-ink-secondary">{{ __('Publikasikan') }}</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <x-secondary-button type="button" wire:click="$set('showModal', false)">{{ __('Batal') }}</x-secondary-button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
