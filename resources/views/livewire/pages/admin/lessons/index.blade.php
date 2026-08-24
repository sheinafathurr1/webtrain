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
    'exercise_checks' => [],
    'confirmingDeleteId' => null,
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
        'exercise_expected_output', 'exercise_solution_code', 'exercise_checks',
    ]);
    $this->type = Lesson::TYPE_TEXT;
    $this->order = 0;
    $this->is_published = false;
    $this->exercise_language = 'html';
    $this->exercise_checks = [];
};

$addCheck = function () {
    $this->exercise_checks[] = ['type' => 'text', 'selector' => '', 'property' => '', 'expected' => ''];
};

$removeCheck = function (int $index) {
    unset($this->exercise_checks[$index]);
    $this->exercise_checks = array_values($this->exercise_checks);
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
    $this->exercise_checks = $lesson->exercise->checks ?? [];

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
        $checks = collect($this->exercise_checks)
            ->filter(fn ($check) => filled($check['selector'] ?? null) && filled($check['expected'] ?? null))
            ->filter(fn ($check) => ($check['type'] ?? null) !== 'style' || filled($check['property'] ?? null))
            ->map(fn ($check) => [
                'type' => $check['type'],
                'selector' => $check['selector'],
                'property' => $check['type'] === 'style' ? $check['property'] : null,
                'expected' => $check['expected'],
            ])
            ->values()
            ->all();

        $lesson->exercise()->updateOrCreate([], [
            'language' => $validated['exercise_language'],
            'instructions' => $validated['exercise_instructions'],
            'starter_code' => $validated['exercise_starter_code'],
            'expected_output' => $validated['exercise_expected_output'],
            'solution_code' => $validated['exercise_solution_code'],
            'checks' => $checks ?: null,
        ]);
    } else {
        $lesson->exercise()->delete();
    }

    $this->showModal = false;
    $this->resetForm();
};

$delete = function () {
    if ($this->confirmingDeleteId) {
        Lesson::find($this->confirmingDeleteId)?->delete();
    }

    $this->confirmingDeleteId = null;
};

?>

<div>
    <x-slot:header>
        <p class="text-sm text-ink-muted">
            <a href="{{ route('admin.modules.index', $module->course_id) }}" wire:navigate class="hover:text-brand font-medium motion-safe:transition-colors duration-150">{{ __('Modules') }}</a>
            <span class="mx-1">/</span>
            <span class="text-ink-primary font-semibold">{{ $module->title }}</span>
        </p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <x-primary-button wire:click="openCreate">{{ __('+ Lesson Baru') }}</x-primary-button>
            </div>

            <div class="bg-surface border border-border rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-canvas">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Judul') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Tipe') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Status') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($lessons as $lesson)
                                <tr wire:key="lesson-{{ $lesson->id }}">
                                    <td class="px-6 py-4 text-sm text-ink-primary font-medium">
                                        {{ $lesson->title }}
                                        <div class="font-mono text-xs text-ink-muted">{{ $lesson->slug }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <x-badge color="accent">{{ $lesson->type }}</x-badge>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <x-badge :color="$lesson->is_published ? 'brand' : 'muted'">{{ $lesson->is_published ? __('published') : __('draft') }}</x-badge>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                        @if ($lesson->type === \App\Models\Lesson::TYPE_QUIZ)
                                            <a href="{{ route('admin.quizzes.builder', $lesson) }}" wire:navigate class="font-semibold text-brand hover:text-brand-dark motion-safe:transition-colors duration-150">{{ __('Kelola Soal Quiz') }}</a>
                                        @endif
                                        <button wire:click="openEdit({{ $lesson->id }})" class="font-semibold text-ink-secondary hover:text-ink-primary motion-safe:transition-colors duration-150">{{ __('Edit') }}</button>
                                        <button type="button" wire:click="confirmingDeleteId = {{ $lesson->id }}" class="font-semibold text-danger hover:opacity-75 motion-safe:transition-opacity duration-150">{{ __('Hapus') }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-ink-secondary">
                                        {{ __('Belum ada lesson di module ini.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $lessons->links() }}
        </div>
    </div>

    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>

        <div class="relative bg-surface border border-border rounded-2xl shadow-2xl max-w-2xl mx-auto p-6 text-ink-primary">
            <h3 class="font-display text-lg font-bold mb-4">
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
                    <x-text-input wire:model="slug" id="slug" class="block mt-1 w-full font-mono" type="text" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>

                <div class="flex gap-4">
                    <div class="flex-1">
                        <x-input-label for="type" :value="__('Tipe Lesson')" />
                        <select wire:model.live="type" id="type" class="border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full">
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
                        <textarea wire:model="content" id="content" rows="8" class="font-mono text-sm border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full"></textarea>
                        <x-input-error :messages="$errors->get('content')" class="mt-2" />
                    </div>
                @elseif ($type === \App\Models\Lesson::TYPE_VIDEO)
                    <div>
                        <x-input-label for="video_url" :value="__('URL Video YouTube')" />
                        <x-text-input wire:model="video_url" id="video_url" class="block mt-1 w-full" type="url" placeholder="https://www.youtube.com/watch?v=..." />
                        <x-input-error :messages="$errors->get('video_url')" class="mt-2" />
                    </div>
                @elseif ($type === \App\Models\Lesson::TYPE_EXERCISE)
                    <div class="space-y-4 border border-border rounded-xl p-4">
                        <div>
                            <x-input-label for="exercise_language" :value="__('Bahasa')" />
                            <select wire:model="exercise_language" id="exercise_language" class="border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full">
                                <option value="html">HTML</option>
                                <option value="css">CSS</option>
                                <option value="js">JavaScript</option>
                                <option value="mixed">HTML + CSS + JS</option>
                            </select>
                            <x-input-error :messages="$errors->get('exercise_language')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="exercise_instructions" :value="__('Instruksi')" />
                            <textarea wire:model="exercise_instructions" id="exercise_instructions" rows="3" class="border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full"></textarea>
                            <x-input-error :messages="$errors->get('exercise_instructions')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="exercise_starter_code" :value="__('Starter Code')" />
                            <textarea wire:model="exercise_starter_code" id="exercise_starter_code" rows="5" class="font-mono text-sm border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full"></textarea>
                        </div>

                        <div>
                            <x-input-label for="exercise_solution_code" :value="__('Solution Code')" />
                            <textarea wire:model="exercise_solution_code" id="exercise_solution_code" rows="5" class="font-mono text-sm border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full"></textarea>
                        </div>

                        <div>
                            <x-input-label for="exercise_expected_output" :value="__('Expected Output (deskripsi/HTML hasil akhir)')" />
                            <textarea wire:model="exercise_expected_output" id="exercise_expected_output" rows="3" class="font-mono text-sm border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full"></textarea>
                        </div>

                        <div class="space-y-2 border-t border-border pt-4">
                            <x-input-label :value="__('Auto-grading Checks (opsional)')" />
                            <p class="text-xs text-ink-muted">{{ __('Jika diisi, siswa harus klik "Cek Jawaban" dan lolos semua check ini agar lesson otomatis ditandai selesai.') }}</p>

                            @foreach ($exercise_checks as $index => $check)
                                <div class="border border-border rounded-xl p-3 space-y-2" wire:key="check-{{ $index }}">
                                    <div class="flex gap-2">
                                        <select wire:model.live="exercise_checks.{{ $index }}.type" class="border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm text-sm flex-1">
                                            <option value="text">{{ __('Teks elemen berisi...') }}</option>
                                            <option value="style">{{ __('Gaya CSS elemen adalah...') }}</option>
                                            <option value="alert">{{ __('Alert saat elemen diklik berisi...') }}</option>
                                        </select>
                                        <button type="button" wire:click="removeCheck({{ $index }})" class="text-danger text-sm px-2 hover:opacity-75 transition-opacity duration-150">&times;</button>
                                    </div>

                                    <x-text-input wire:model="exercise_checks.{{ $index }}.selector" class="block w-full font-mono text-sm" type="text" placeholder="{{ __('CSS selector, mis. h1 atau #tombol') }}" />

                                    @if (($check['type'] ?? 'text') === 'style')
                                        <x-text-input wire:model="exercise_checks.{{ $index }}.property" class="block w-full font-mono text-sm" type="text" placeholder="{{ __('Properti CSS, mis. color atau backgroundColor') }}" />
                                    @endif

                                    <x-text-input wire:model="exercise_checks.{{ $index }}.expected" class="block w-full text-sm" type="text" placeholder="{{ __('Nilai yang diharapkan, mis. Halo, Dunia! atau blue') }}" />
                                </div>
                            @endforeach

                            <button type="button" wire:click="addCheck" class="text-sm font-semibold text-brand hover:text-brand-dark motion-safe:transition-colors duration-150">{{ __('+ Tambah Check') }}</button>
                        </div>
                    </div>
                @elseif ($type === \App\Models\Lesson::TYPE_QUIZ)
                    <p class="text-sm text-ink-secondary">
                        {{ __('Simpan lesson ini dulu, lalu kelola soal quiz lewat tombol "Kelola Soal Quiz" di daftar lesson.') }}
                    </p>
                @endif

                <label class="inline-flex items-center">
                    <input type="checkbox" wire:model="is_published" class="rounded-sm border-2 border-border text-brand focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    <span class="ms-2 text-sm text-ink-secondary">{{ __('Publikasikan') }}</span>
                </label>
                <x-input-error :messages="$errors->get('is_published')" class="mt-1" />

                <div class="flex justify-end gap-3 pt-2 sticky bottom-0 bg-surface">
                    <x-secondary-button type="button" wire:click="$set('showModal', false)">{{ __('Batal') }}</x-secondary-button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <x-confirm-delete-modal
        :title="__('Hapus Lesson?')"
        :message="__('Lesson ini akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.')"
    />
</div>
