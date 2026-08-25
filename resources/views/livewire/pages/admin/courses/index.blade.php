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
    'confirmingDeleteId' => null,
    'search' => '',
    'status' => '',
    'reordering' => false,
]);

mount(function (Track $track) {
    $this->track = $track;
});

with(fn () => [
    'courses' => Course::withCount('modules')
        ->where('track_id', $this->track->id)
        ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($q) => $q
            ->where('title', 'like', '%'.trim($this->search).'%')
            ->orWhere('slug', 'like', '%'.trim($this->search).'%')
        ))
        ->when($this->status !== '', fn ($q) => $q->where('is_published', $this->status === 'published'))
        ->orderBy('order')
        ->orderBy('title')
        ->paginate(10),
]);

$updatedSearch = function () {
    $this->resetPage();
};

$updatedStatus = function () {
    $this->resetPage();
};

$resetFilters = function () {
    $this->search = '';
    $this->status = '';
    $this->resetPage();
};

$resetForm = function () {
    $this->reset(['editingId', 'title', 'slug', 'description', 'order', 'is_published', 'lock_lessons_sequentially']);
    $this->order = 0;
    $this->is_published = false;
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

$delete = function () {
    if ($this->confirmingDeleteId) {
        Course::find($this->confirmingDeleteId)?->delete();
    }

    $this->confirmingDeleteId = null;
};

$saveOrder = function (array $orderedIds) {
    $validIds = Course::where('track_id', $this->track->id)->pluck('id')->all();
    $orderedIds = array_values(array_intersect($orderedIds, $validIds));

    foreach ($orderedIds as $index => $id) {
        Course::where('id', $id)->update(['order' => $index]);
    }

    $this->reordering = false;
};

?>

<div>
    <x-slot:header>
        <p class="text-sm text-ink-muted">
            <a href="{{ route('admin.tracks.index') }}" wire:navigate class="hover:text-brand font-medium motion-safe:transition-colors duration-150">{{ __('Tracks') }}</a>
            <span class="mx-1">/</span>
            <span class="text-ink-primary font-semibold">{{ $track->title }}</span>
        </p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php $hasActiveFilters = $search !== '' || $status !== ''; @endphp
            @php $canReorder = ! $courses->hasPages() && ! $hasActiveFilters && $courses->isNotEmpty(); @endphp

            @if ($reordering)
                <div
                    x-data="{
                        items: @js($courses->map(fn ($c) => ['id' => $c->id, 'title' => $c->title, 'subtitle' => $c->slug])->values()->all()),
                        dragIndex: null,
                    }"
                    class="bg-surface border border-brand/30 rounded-2xl p-4"
                >
                    <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
                        <p class="text-sm text-ink-secondary">{{ __('Seret item untuk mengubah urutan, lalu klik Simpan Urutan.') }}</p>
                        <div class="flex gap-2 shrink-0">
                            <x-secondary-button type="button" wire:click="$set('reordering', false)">{{ __('Batal') }}</x-secondary-button>
                            <x-primary-button type="button" @click="$wire.saveOrder(items.map(i => i.id))">{{ __('Simpan Urutan') }}</x-primary-button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(item, index) in items" :key="item.id">
                            <div
                                draggable="true"
                                @dragstart="dragIndex = index"
                                @dragend="dragIndex = null"
                                @dragover.prevent="if (dragIndex !== null && dragIndex !== index) { items.splice(index, 0, items.splice(dragIndex, 1)[0]); dragIndex = index; }"
                                @drop.prevent
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border motion-safe:transition-all duration-150 cursor-grab active:cursor-grabbing select-none"
                                :class="dragIndex === index ? 'opacity-50 scale-[0.98] border-brand bg-brand/5 shadow-md' : 'border-border bg-canvas hover:border-brand/40'"
                            >
                                <x-drag-handle-icon />
                                <span class="text-sm font-medium text-ink-primary truncate" x-text="item.title"></span>
                                <span class="font-mono text-xs text-ink-muted truncate" x-text="item.subtitle"></span>
                            </div>
                        </template>
                    </div>
                </div>
            @else
                <div class="flex justify-between items-center gap-3 flex-wrap">
                    <div class="flex gap-3 flex-wrap flex-1">
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Cari judul atau slug...') }}"
                            class="w-64 max-w-full rounded-xl border-border bg-surface text-sm text-ink-primary placeholder:text-ink-muted focus:border-brand focus:ring-brand"
                        />
                        <select wire:model.live="status" class="rounded-xl border-border bg-surface text-sm text-ink-primary focus:border-brand focus:ring-brand">
                            <option value="">{{ __('Semua Status') }}</option>
                            <option value="published">{{ __('Published') }}</option>
                            <option value="draft">{{ __('Draft') }}</option>
                        </select>
                        @if ($hasActiveFilters)
                            <button type="button" wire:click="resetFilters" class="text-sm font-semibold text-ink-secondary hover:text-brand motion-safe:transition-colors duration-150">{{ __('Reset filter') }}</button>
                        @endif
                    </div>

                    <div class="flex gap-2 shrink-0">
                        @if ($canReorder)
                            <x-secondary-button type="button" wire:click="$set('reordering', true)">{{ __('Ubah Urutan') }}</x-secondary-button>
                        @endif
                        <x-primary-button wire:click="openCreate">{{ __('+ Course Baru') }}</x-primary-button>
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-canvas">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Judul') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Module') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-ink-muted uppercase tracking-wide">{{ __('Status') }}</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($courses as $course)
                                    <tr wire:key="course-{{ $course->id }}">
                                        <td class="px-6 py-4 text-sm text-ink-primary font-medium">
                                            {{ $course->title }}
                                            <div class="font-mono text-xs text-ink-muted">{{ $course->slug }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-ink-secondary tabular-nums">
                                            {{ $course->modules_count }} module
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <x-badge :color="$course->is_published ? 'brand' : 'muted'">{{ $course->is_published ? __('published') : __('draft') }}</x-badge>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                            <a href="{{ route('admin.modules.index', $course) }}" wire:navigate class="font-semibold text-brand hover:text-brand-dark motion-safe:transition-colors duration-150">{{ __('Kelola Module') }}</a>
                                            <button wire:click="openEdit({{ $course->id }})" class="font-semibold text-ink-secondary hover:text-ink-primary motion-safe:transition-colors duration-150">{{ __('Edit') }}</button>
                                            <button type="button" wire:click="confirmingDeleteId = {{ $course->id }}" class="font-semibold text-danger hover:opacity-75 motion-safe:transition-opacity duration-150">{{ __('Hapus') }}</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-ink-secondary">
                                            @if ($hasActiveFilters)
                                                {{ __('Tidak ada course yang cocok dengan pencarian/filter.') }}
                                            @else
                                                {{ __('Belum ada course di track ini.') }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{ $courses->links() }}
            @endif
        </div>
    </div>

    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>

        <div class="relative bg-surface border border-border rounded-2xl shadow-2xl max-w-lg mx-auto p-6 text-ink-primary">
            <h3 class="font-display text-lg font-bold mb-4">
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
                </div>

                <div class="space-y-2">
                    <label class="inline-flex items-center">
                        <input type="checkbox" wire:model="is_published" class="rounded-sm border-2 border-border text-brand focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        <span class="ms-2 text-sm text-ink-secondary">{{ __('Publikasikan') }}</span>
                    </label>
                    <x-input-error :messages="$errors->get('is_published')" class="mt-1" />

                    <label class="flex items-center">
                        <input type="checkbox" wire:model="lock_lessons_sequentially" class="rounded-sm border-2 border-border text-brand focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        <span class="ms-2 text-sm text-ink-secondary">{{ __('Kunci urutan lesson (siswa harus selesaikan berurutan)') }}</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <x-secondary-button type="button" wire:click="$set('showModal', false)">{{ __('Batal') }}</x-secondary-button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <x-confirm-delete-modal
        :title="__('Hapus Course?')"
        :message="__('Course ini beserta seluruh module & lesson di dalamnya akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.')"
    />
</div>
