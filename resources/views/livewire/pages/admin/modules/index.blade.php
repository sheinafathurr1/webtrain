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

$delete = function (Module $module) {
    $module->delete();
};

?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <a href="{{ route('admin.courses.index', $course->track_id) }}" wire:navigate class="text-gray-400 hover:underline">{{ __('Courses') }}</a>
            / {{ $course->title }}
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <x-primary-button wire:click="openCreate">{{ __('+ Module Baru') }}</x-primary-button>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Judul') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Lesson') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($modules as $module)
                            <tr wire:key="module-{{ $module->id }}">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $module->title }}
                                    <div class="text-xs text-gray-400">{{ $module->slug }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $module->lessons_count }} lesson
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                    <a href="{{ route('admin.lessons.index', $module) }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Kelola Lesson') }}</a>
                                    <button wire:click="openEdit({{ $module->id }})" class="text-gray-600 dark:text-gray-300 hover:underline">{{ __('Edit') }}</button>
                                    <button wire:click="delete({{ $module->id }})" wire:confirm="{{ __('Hapus module ini beserta seluruh lesson di dalamnya?') }}" class="text-red-600 dark:text-red-400 hover:underline">{{ __('Hapus') }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Belum ada module di course ini.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $modules->links() }}
        </div>
    </div>

    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75" wire:click="$set('showModal', false)"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg mx-auto p-6 text-gray-900 dark:text-gray-100">
            <h3 class="text-lg font-medium mb-4">
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
                    <x-text-input wire:model="slug" id="slug" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" :value="__('Deskripsi')" />
                    <textarea wire:model="description" id="description" rows="3" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
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
</div>
