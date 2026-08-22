<?php

use App\Models\Lesson;
use App\Models\LessonExercise;
use App\Models\Module;
use Illuminate\Validation\Rule;

use function Livewire\Volt\{layout, mount, state, usesPagination, with};

layout('layouts.app');
usesPagination();

state([
    'module' => null,
    'showModal' => false,
    'editingId' => null,
    'title' => '',
    'slug' => '',
    'type' => Lesson::TYPE_TEXT,
    'order' => 0,
    'is_published' => false,
    'content' => '',
    'video_url' => '',
    'exercise_language' => 'html',
    'exercise_instructions' => '',
    'exercise_starter_code' => '',
    'exercise_expected_output' => '',
    'exercise_solution_code' => '',
]);

mount(function (Module $module) {
    $this->module = $module;
});

with(fn () => [
    'lessons' => Lesson::where('module_id', $this->module->id)
        ->orderBy('order')
        ->orderBy('title')
        ->paginate(10),
    'types' => Lesson::TYPES,
]);

$resetForm = function () {
    $this->reset([
        'editingId', 'title', 'slug', 'order', 'is_published', 'content', 'video_url',
        'exercise_language', 'exercise_instructions', 'exercise_starter_code',
        'exercise_expected_output', 'exercise_solution_code',
    ]);
    $this->type = Lesson::TYPE_TEXT;
    $this->order = 0;
    $this->exercise_language = 'html';
};

$openCreate = function () {
    $this->resetForm();
    $this->showModal = true;
};

$openEdit = function (Lesson $lesson) {
    $lesson->loadMissing('exercise');

    $this->editingId = $lesson->id;
    $this->title = $lesson->title;
    $this->slug = $lesson->slug;
    $this->type = $lesson->type;
    $this->order = $lesson->order;
    $this->is_published = $lesson->is_published;
    $this->content = $lesson->content;
    $this->video_url = $lesson->video_url;

    $this->exercise_language = $lesson->exercise->language ?? 'html';
    $this->exercise_instructions = $lesson->exercise->instructions ?? '';
    $this->exercise_starter_code = $lesson->exercise->starter_code ?? '';
    $this->exercise_expected_output = $lesson->exercise->expected_output ?? '';
    $this->exercise_solution_code = $lesson->exercise->solution_code ?? '';

    $this->showModal = true;
};

$save = function () {
    $validated = $this->validate([
        'title' => ['required', 'string', 'max:255'],
        'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('lessons', 'slug')->ignore($this->editingId)],
        'type' => ['required', Rule::in(Lesson::TYPES)],
        'order' => ['integer', 'min:0'],
        'is_published' => ['boolean'],
        'content' => ['nullable', 'required_if:type,'.Lesson::TYPE_TEXT, 'string'],
        'video_url' => ['nullable', 'required_if:type,'.Lesson::TYPE_VIDEO, 'url'],
        'exercise_language' => ['nullable', 'required_if:type,'.Lesson::TYPE_EXERCISE, Rule::in(LessonExercise::LANGUAGES)],
        'exercise_instructions' => ['nullable', 'required_if:type,'.Lesson::TYPE_EXERCISE, 'string'],
        'exercise_starter_code' => ['nullable', 'string'],
        'exercise_expected_output' => ['nullable', 'string'],
        'exercise_solution_code' => ['nullable', 'string'],
    ]);

    $lessonData = [
        'title' => $validated['title'],
        'type' => $validated['type'],
        'order' => $validated['order'],
        'is_published' => $validated['is_published'],
        'content' => $validated['type'] === Lesson::TYPE_TEXT ? $validated['content'] : null,
        'video_url' => $validated['type'] === Lesson::TYPE_VIDEO ? $validated['video_url'] : null,
    ];

    if (! blank($validated['slug'])) {
        $lessonData['slug'] = $validated['slug'];
    }

    $lessonData['module_id'] = $this->module->id;

    $lesson = $this->editingId
        ? tap(Lesson::findOrFail($this->editingId))->update($lessonData)
        : Lesson::create($lessonData);

    if ($validated['type'] === Lesson::TYPE_EXERCISE) {
        $lesson->exercise()->updateOrCreate([], [
            'language' => $validated['exercise_language'],
            'instructions' => $validated['exercise_instructions'],
            'starter_code' => $validated['exercise_starter_code'],
            'expected_output' => $validated['exercise_expected_output'],
            'solution_code' => $validated['exercise_solution_code'],
        ]);
    } else {
        $lesson->exercise()->delete();
    }

    $this->showModal = false;
    $this->resetForm();
};

$delete = function (Lesson $lesson) {
    $lesson->delete();
};

?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <a href="{{ route('admin.modules.index', $module->course_id) }}" wire:navigate class="text-gray-400 hover:underline">{{ __('Modules') }}</a>
            / {{ $module->title }}
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <x-primary-button wire:click="openCreate">{{ __('+ Lesson Baru') }}</x-primary-button>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Judul') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Tipe') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Status') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($lessons as $lesson)
                            <tr wire:key="lesson-{{ $lesson->id }}">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $lesson->title }}
                                    <div class="text-xs text-gray-400">{{ $lesson->slug }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 capitalize">
                                    {{ $lesson->type }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($lesson->is_published)
                                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('Published') }}</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ __('Draft') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                    <button wire:click="openEdit({{ $lesson->id }})" class="text-gray-600 dark:text-gray-300 hover:underline">{{ __('Edit') }}</button>
                                    <button wire:click="delete({{ $lesson->id }})" wire:confirm="{{ __('Hapus lesson ini?') }}" class="text-red-600 dark:text-red-400 hover:underline">{{ __('Hapus') }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Belum ada lesson di module ini.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $lessons->links() }}
        </div>
    </div>

    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75" wire:click="$set('showModal', false)"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl mx-auto p-6 text-gray-900 dark:text-gray-100">
            <h3 class="text-lg font-medium mb-4">
                {{ $editingId ? __('Edit Lesson') : __('Lesson Baru') }}
            </h3>

            <form wire:submit="save" class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
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

                <div class="flex gap-4">
                    <div class="flex-1">
                        <x-input-label for="type" :value="__('Tipe Lesson')" />
                        <select wire:model.live="type" id="type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                            @foreach ($types as $t)
                                <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>

                    <div class="flex-1">
                        <x-input-label for="order" :value="__('Urutan')" />
                        <x-text-input wire:model="order" id="order" class="block mt-1 w-full" type="number" min="0" />
                    </div>
                </div>

                @if ($type === \App\Models\Lesson::TYPE_TEXT)
                    <div>
                        <x-input-label for="content" :value="__('Konten (Markdown)')" />
                        <textarea wire:model="content" id="content" rows="8" class="font-mono text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                        <x-input-error :messages="$errors->get('content')" class="mt-2" />
                    </div>
                @elseif ($type === \App\Models\Lesson::TYPE_VIDEO)
                    <div>
                        <x-input-label for="video_url" :value="__('URL Video YouTube')" />
                        <x-text-input wire:model="video_url" id="video_url" class="block mt-1 w-full" type="url" placeholder="https://www.youtube.com/watch?v=..." />
                        <x-input-error :messages="$errors->get('video_url')" class="mt-2" />
                    </div>
                @elseif ($type === \App\Models\Lesson::TYPE_EXERCISE)
                    <div class="space-y-4 border border-gray-200 dark:border-gray-700 rounded-md p-4">
                        <div>
                            <x-input-label for="exercise_language" :value="__('Bahasa')" />
                            <select wire:model="exercise_language" id="exercise_language" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="html">HTML</option>
                                <option value="css">CSS</option>
                                <option value="js">JavaScript</option>
                                <option value="mixed">HTML + CSS + JS</option>
                            </select>
                            <x-input-error :messages="$errors->get('exercise_language')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="exercise_instructions" :value="__('Instruksi')" />
                            <textarea wire:model="exercise_instructions" id="exercise_instructions" rows="3" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                            <x-input-error :messages="$errors->get('exercise_instructions')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="exercise_starter_code" :value="__('Starter Code')" />
                            <textarea wire:model="exercise_starter_code" id="exercise_starter_code" rows="5" class="font-mono text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                        </div>

                        <div>
                            <x-input-label for="exercise_solution_code" :value="__('Solution Code')" />
                            <textarea wire:model="exercise_solution_code" id="exercise_solution_code" rows="5" class="font-mono text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                        </div>

                        <div>
                            <x-input-label for="exercise_expected_output" :value="__('Expected Output (deskripsi/HTML hasil akhir)')" />
                            <textarea wire:model="exercise_expected_output" id="exercise_expected_output" rows="3" class="font-mono text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                        </div>
                    </div>
                @endif

                <label class="inline-flex items-center">
                    <input type="checkbox" wire:model="is_published" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Publikasikan') }}</span>
                </label>

                <div class="flex justify-end gap-3 pt-2 sticky bottom-0 bg-white dark:bg-gray-800">
                    <x-secondary-button type="button" wire:click="$set('showModal', false)">{{ __('Batal') }}</x-secondary-button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
