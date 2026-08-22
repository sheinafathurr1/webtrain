<?php

use App\Models\Course;
use App\Models\Module;
use Illuminate\Validation\Rule;

use function Livewire\Volt\{layout, mount, state, usesPagination, with};

layout('layouts.app');
usesPagination();

state([
    'course' => null,
    'showModal' => false,
    'editingId' => null,
    'title' => '',
    'slug' => '',
    'description' => '',
    'order' => 0,
    'confirmingDeleteId' => null,
]);

mount(function (Course $course) {
    $this->course = $course;
});

with(fn () => [
    'modules' => Module::withCount('lessons')
        ->where('course_id', $this->course->id)
        ->orderBy('order')
        ->orderBy('title')
        ->paginate(10),
]);

$resetForm = function () {
    $this->reset(['editingId', 'title', 'slug', 'description', 'order']);
    $this->order = 0;
};

$openCreate = function () {
    $this->resetForm();
    $this->showModal = true;
};

$openEdit = function (Module $module) {
    $this->editingId = $module->id;
    $this->title = $module->title;
    $this->slug = $module->slug;
    $this->description = $module->description;
    $this->order = $module->order;
    $this->showModal = true;
};

$save = function () {
    $validated = $this->validate([
        'title' => ['required', 'string', 'max:255'],
        'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('modules', 'slug')->ignore($this->editingId)],
        'description' => ['nullable', 'string'],
        'order' => ['integer', 'min:0'],
    ]);

    if (blank($validated['slug'])) {
        unset($validated['slug']);
    }

    $validated['course_id'] = $this->course->id;

    if ($this->editingId) {
        Module::findOrFail($this->editingId)->update($validated);
    } else {
        Module::create($validated);
    }

    $this->showModal = false;
    $this->resetForm();
};

$delete = function () {
    if ($this->confirmingDeleteId) {
        Module::find($this->confirmingDeleteId)?->delete();
    }

    $this->confirmingDeleteId = null;
};

?>

<div>
    <x-slot:header>
        <p class="text-sm text-ink-muted">
            <a href="{{ route('admin.courses.index', $course->track_id) }}" wire:navigate class="hover:text-brand font-medium motion-safe:transition-colors duration-150">{{ __('Courses') }}</a>
            <span class="mx-1">/</span>
            <span class="text-ink-primary font-semibold">{{ $course->title }}</span>
        </p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <x-primary-button wire:click="openCreate">{{ __('+ Module Baru') }}</x-primary-button>
            </div>

            <div class="bg-surface border border-border rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-canvas">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Judul') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Lesson') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($modules as $module)
                                <tr wire:key="module-{{ $module->id }}">
                                    <td class="px-6 py-4 text-sm text-ink-primary font-medium">
                                        {{ $module->title }}
                                        <div class="font-mono text-xs text-ink-muted">{{ $module->slug }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-ink-secondary tabular-nums">
                                        {{ $module->lessons_count }} lesson
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                        <a href="{{ route('admin.lessons.index', $module) }}" wire:navigate class="font-semibold text-brand hover:text-brand-dark motion-safe:transition-colors duration-150">{{ __('Kelola Lesson') }}</a>
                                        <button wire:click="openEdit({{ $module->id }})" class="font-semibold text-ink-secondary hover:text-ink-primary motion-safe:transition-colors duration-150">{{ __('Edit') }}</button>
                                        <button type="button" wire:click="confirmingDeleteId = {{ $module->id }}" class="font-semibold text-danger hover:opacity-75 motion-safe:transition-opacity duration-150">{{ __('Hapus') }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-sm text-ink-secondary">
                                        {{ __('Belum ada module di course ini.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $modules->links() }}
        </div>
    </div>

    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>

        <div class="relative bg-surface border border-border rounded-2xl shadow-2xl max-w-lg mx-auto p-6 text-ink-primary">
            <h3 class="font-display text-lg font-bold mb-4">
                {{ $editingId ? __('Edit Module') : __('Module Baru') }}
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

                <div>
                    <x-input-label for="order" :value="__('Urutan')" />
                    <x-text-input wire:model="order" id="order" class="block mt-1 w-full" type="number" min="0" />
                    <x-input-error :messages="$errors->get('order')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <x-secondary-button type="button" wire:click="$set('showModal', false)">{{ __('Batal') }}</x-secondary-button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <x-confirm-delete-modal
        :title="__('Hapus Module?')"
        :message="__('Module ini beserta seluruh lesson di dalamnya akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.')"
    />
</div>
