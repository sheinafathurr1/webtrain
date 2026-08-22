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
    'confirmingDeleteId' => null,
]);

with(fn () => [
    'tracks' => Track::withCount('courses')->orderBy('order')->orderBy('title')->paginate(10),
]);

$resetForm = function () {
    $this->reset(['editingId', 'title', 'slug', 'description', 'order', 'is_published']);
    $this->order = 0;
    $this->is_published = false;
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

$delete = function () {
    if ($this->confirmingDeleteId) {
        Track::find($this->confirmingDeleteId)?->delete();
    }

    $this->confirmingDeleteId = null;
};

?>

<div>
    <x-slot:header>
        <h2 class="font-display font-extrabold text-2xl text-ink-primary">{{ __('Tracks') }}</h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <x-primary-button wire:click="openCreate">{{ __('+ Track Baru') }}</x-primary-button>
            </div>

            <div class="bg-surface border border-border rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-canvas">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Judul') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Course') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Status') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($tracks as $track)
                                <tr wire:key="track-{{ $track->id }}">
                                    <td class="px-6 py-4 text-sm text-ink-primary font-medium">
                                        {{ $track->title }}
                                        <div class="font-mono text-xs text-ink-muted">{{ $track->slug }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-ink-secondary tabular-nums">
                                        {{ $track->courses_count }} course
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <x-badge :color="$track->is_published ? 'brand' : 'muted'">{{ $track->is_published ? __('published') : __('draft') }}</x-badge>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                        <a href="{{ route('admin.courses.index', $track) }}" wire:navigate class="font-semibold text-brand hover:text-brand-dark motion-safe:transition-colors duration-150">{{ __('Kelola Course') }}</a>
                                        <button wire:click="openEdit({{ $track->id }})" class="font-semibold text-ink-secondary hover:text-ink-primary motion-safe:transition-colors duration-150">{{ __('Edit') }}</button>
                                        <button type="button" wire:click="confirmingDeleteId = {{ $track->id }}" class="font-semibold text-danger hover:opacity-75 motion-safe:transition-opacity duration-150">{{ __('Hapus') }}</button>
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
        <div class="fixed inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>

        <div class="relative bg-surface border border-border rounded-2xl shadow-2xl max-w-lg mx-auto p-6 text-ink-primary">
            <h3 class="font-display text-lg font-bold mb-4">
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
                    <textarea wire:model="description" id="description" rows="3" class="border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full"></textarea>
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
                            <input type="checkbox" wire:model="is_published" class="rounded-sm border-2 border-border text-brand focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            <span class="ms-2 text-sm text-ink-secondary">{{ __('Publikasikan') }}</span>
                        </label>
                        <x-input-error :messages="$errors->get('is_published')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <x-secondary-button type="button" wire:click="$set('showModal', false)">{{ __('Batal') }}</x-secondary-button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <x-confirm-delete-modal
        :title="__('Hapus Track?')"
        :message="__('Track ini beserta seluruh course di dalamnya akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.')"
    />
</div>
