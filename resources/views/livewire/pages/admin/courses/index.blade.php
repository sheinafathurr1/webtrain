<?php

use App\Models\Course;
use App\Models\Track;
use Illuminate\Validation\Rule;

use function Livewire\Volt\{layout, mount, state, usesPagination, with};

layout('layouts.app');
usesPagination();

state([
    'track' => null,
    'showModal' => false,
    'editingId' => null,
    'title' => '',
    'slug' => '',
    'description' => '',
    'order' => 0,
    'is_published' => false,
    'lock_lessons_sequentially' => true,
]);

mount(function (Track $track) {
    $this->track = $track;
});

with(fn () => [
    'courses' => Course::withCount('modules')
        ->where('track_id', $this->track->id)
        ->orderBy('order')
        ->orderBy('title')
        ->paginate(10),
]);

$resetForm = function () {
    $this->reset(['editingId', 'title', 'slug', 'description', 'order', 'is_published', 'lock_lessons_sequentially']);
    $this->order = 0;
    $this->lock_lessons_sequentially = true;
};

$openCreate = function () {
    $this->resetForm();
    $this->showModal = true;
};

$openEdit = function (Course $course) {
    $this->editingId = $course->id;
    $this->title = $course->title;
    $this->slug = $course->slug;
    $this->description = $course->description;
    $this->order = $course->order;
    $this->is_published = $course->is_published;
    $this->lock_lessons_sequentially = $course->lock_lessons_sequentially;
    $this->showModal = true;
};

$save = function () {
    $validated = $this->validate([
        'title' => ['required', 'string', 'max:255'],
        'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('courses', 'slug')->ignore($this->editingId)],
        'description' => ['nullable', 'string'],
        'order' => ['integer', 'min:0'],
        'is_published' => ['boolean'],
        'lock_lessons_sequentially' => ['boolean'],
    ]);

    if (blank($validated['slug'])) {
        unset($validated['slug']);
    }

    $validated['track_id'] = $this->track->id;

    if ($this->editingId) {
        Course::findOrFail($this->editingId)->update($validated);
    } else {
        Course::create($validated);
    }

    $this->showModal = false;
    $this->resetForm();
};

$delete = function (Course $course) {
    $course->delete();
};

?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <a href="{{ route('admin.tracks.index') }}" wire:navigate class="text-gray-400 hover:underline">{{ __('Tracks') }}</a>
            / {{ $track->title }}
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <x-primary-button wire:click="openCreate">{{ __('+ Course Baru') }}</x-primary-button>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Judul') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Module') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Status') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($courses as $course)
                            <tr wire:key="course-{{ $course->id }}">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $course->title }}
                                    <div class="text-xs text-gray-400">{{ $course->slug }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $course->modules_count }} module
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($course->is_published)
                                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('Published') }}</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ __('Draft') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                    <a href="{{ route('admin.modules.index', $course) }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Kelola Module') }}</a>
                                    <button wire:click="openEdit({{ $course->id }})" class="text-gray-600 dark:text-gray-300 hover:underline">{{ __('Edit') }}</button>
                                    <button wire:click="delete({{ $course->id }})" wire:confirm="{{ __('Hapus course ini beserta seluruh module & lesson di dalamnya?') }}" class="text-red-600 dark:text-red-400 hover:underline">{{ __('Hapus') }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Belum ada course di track ini.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $courses->links() }}
        </div>
    </div>

    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75" wire:click="$set('showModal', false)"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg mx-auto p-6 text-gray-900 dark:text-gray-100">
            <h3 class="text-lg font-medium mb-4">
                {{ $editingId ? __('Edit Course') : __('Course Baru') }}
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

                <div class="flex gap-4">
                    <div class="flex-1">
                        <x-input-label for="order" :value="__('Urutan')" />
                        <x-text-input wire:model="order" id="order" class="block mt-1 w-full" type="number" min="0" />
                        <x-input-error :messages="$errors->get('order')" class="mt-2" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="inline-flex items-center">
                        <input type="checkbox" wire:model="is_published" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Publikasikan') }}</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" wire:model="lock_lessons_sequentially" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Kunci urutan lesson (siswa harus selesaikan berurutan)') }}</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <x-secondary-button type="button" wire:click="$set('showModal', false)">{{ __('Batal') }}</x-secondary-button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
